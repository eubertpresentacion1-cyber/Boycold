<?php
session_start();
require_once './config/db_config.php';
require_once './config/mailer.php';

$mode = $_GET['mode'] ?? ($_SESSION['otp_type'] ?? '');
$error = '';
$success = '';

function ensureLoyaltyCardNo(mysqli $connect, int $userId): string {
    $getStmt = $connect->prepare("SELECT card_no FROM users WHERE id = ?");
    $getStmt->bind_param("i", $userId);
    $getStmt->execute();
    $existing = $getStmt->get_result()->fetch_assoc();
    $getStmt->close();

    if (!empty($existing['card_no'])) {
        return $existing['card_no'];
    }

    $cardNo = 'BY-' . date('Y') . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);

    $checkStmt = $connect->prepare("SELECT id FROM users WHERE card_no = ? LIMIT 1");
    $checkStmt->bind_param("s", $cardNo);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();

    if ($exists) {
        return $cardNo;
    }

    $updateStmt = $connect->prepare("UPDATE users SET card_no = ? WHERE id = ?");
    $updateStmt->bind_param("si", $cardNo, $userId);
    $updateStmt->execute();
    $updateStmt->close();

    return $cardNo;
}

if ($mode === 'register') {
    if (empty($_SESSION['otp_email'])) {
        header('Location: register.php');
        exit;
    }
    $email = $_SESSION['otp_email'];
    $type = 'register';
    $redirectOnSuccess = 'login.php?verified=1';
} elseif ($mode === 'reset') {
    if (empty($_SESSION['otp_email'])) {
        header('Location: forgotpass.php');
        exit;
    }
    $email = $_SESSION['otp_email'];
    $type = 'reset';
    $redirectOnSuccess = 'newpassword.php';
} else {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'verify') {
        $otp = trim($_POST['otp'] ?? '');
        if (!preg_match('/^\d{6}$/', $otp)) {
            $error = 'Please enter a valid 6-digit OTP.';
        } else {
            $stmt = $connect->prepare(
                "SELECT id, otp, expires_at, attempts FROM otp WHERE email=? AND type=? AND status='pending' ORDER BY id DESC LIMIT 1"
            );
            $stmt->bind_param("ss", $email, $type);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if (!$row) {
                $error = 'No active OTP found. Please request a new one.';
            } elseif (new DateTime() > new DateTime($row['expires_at'])) {
                $u = $connect->prepare("UPDATE otp SET status='expired' WHERE id=?");
                $u->bind_param("i", $row['id']);
                $u->execute();
                $error = 'OTP has expired. Please click Resend OTP.';
            } elseif ($row['attempts'] >= 5) {
                $error = 'Too many failed attempts. Please request a new OTP.';
            } elseif ($otp !== $row['otp']) {
                $u = $connect->prepare("UPDATE otp SET attempts=attempts+1 WHERE id=?");
                $u->bind_param("i", $row['id']);
                $u->execute();
                $left = 5 - ($row['attempts'] + 1);
                $error = "Incorrect OTP. $left attempt(s) remaining.";
            } else {
                $u = $connect->prepare("UPDATE otp SET status='verified' WHERE id=?");
                $u->bind_param("i", $row['id']);
                $u->execute();

                if ($type === 'register') {
                    $userData = $connect->prepare("SELECT firstname, lastname, password FROM otp WHERE email=? AND type='register' ORDER BY id DESC LIMIT 1");
                    $userData->bind_param("s", $email);
                    $userData->execute();
                    $user = $userData->get_result()->fetch_assoc();
                    $ins = $connect->prepare("INSERT INTO users (firstname, lastname, email, password, is_verified, created_at) VALUES (?, ?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE is_verified=1, password=VALUES(password)");
                    $ins->bind_param("ssss", $user['firstname'], $user['lastname'], $email, $user['password']);
                    $ins->execute();

                    // ── Assign loyalty card number ────────────────────────
                    $uid = $connect->insert_id;
                    if (!$uid) {
                        $eid = $connect->prepare("SELECT id FROM users WHERE email=?");
                        $eid->bind_param("s", $email);
                        $eid->execute();
                        $uid = $eid->get_result()->fetch_assoc()['id'];
                    }

                    ensureLoyaltyCardNo($connect, (int) $uid);
                    // ─────────────────────────────────────────────────────

                    unset($_SESSION['otp_email'], $_SESSION['otp_type']);
                } else {
                    $_SESSION['reset_email'] = $email;
                    unset($_SESSION['otp_email'], $_SESSION['otp_type']);
                }
                header("Location: $redirectOnSuccess");
                exit;
            }
        }
    } elseif ($action === 'resend') {
        $chk = $connect->prepare("SELECT otp_sent FROM otp WHERE email=? AND type=? AND status='pending' AND otp_sent >= NOW() - INTERVAL 60 SECOND ORDER BY id DESC LIMIT 1");
        $chk->bind_param("ss", $email, $type);
        $chk->execute();
        $last = $chk->get_result()->fetch_assoc();
        $wait = $last ? max(0, 60 - (time() - strtotime($last['otp_sent']))) : 0;

        if ($wait > 0) {
            $error = "Please wait $wait second(s) before resending.";
        } else {
            $exp = $connect->prepare("UPDATE otp SET status='expired' WHERE email=? AND type=? AND status='pending'");
            $exp->bind_param("ss", $email, $type);
            $exp->execute();

            $otp = str_pad(rand(0, 60), 6, '0', STR_PAD_LEFT);
            $ip = $_SERVER['REMOTE_ADDR'];
            $fullName = $email;

            if ($type === 'register') {
                $nq = $connect->prepare("SELECT firstname, lastname, password FROM otp WHERE email=? ORDER BY id DESC LIMIT 1");
                $nq->bind_param("s", $email);
                $nq->execute();
                $nr = $nq->get_result()->fetch_assoc();
                $fn = $nr['firstname'] ?? '';
                $ln = $nr['lastname'] ?? '';
                $hp = $nr['password'] ?? '';
                $fullName = "$fn $ln";
                $ins = $connect->prepare("INSERT INTO otp (firstname, lastname, email, password, otp, type, status, otp_sent, ip) VALUES (?, ?, ?, ?, ?, 'register', 'pending', NOW(), ?)");
                $ins->bind_param("ssssss", $fn, $ln, $email, $hp, $otp, $ip);
            } else {
                $nq = $connect->prepare("SELECT firstname, lastname FROM users WHERE email=?");
                $nq->bind_param("s", $email);
                $nq->execute();
                $nr = $nq->get_result()->fetch_assoc();
                $fullName = $nr ? $nr['firstname'] . ' ' . $nr['lastname'] : $email;
                $ins = $connect->prepare("INSERT INTO otp (email, otp, type, status, otp_sent, ip) VALUES (?, ?, 'reset', 'pending', NOW(), ?)");
                $ins->bind_param("sss", $email, $otp, $ip);
            }
            $ins->execute();
            sendOTPEmail($email, $fullName, $otp, $type);
            $success = 'A new OTP has been sent to your email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="styles/code.css">
    <link rel="icon" type="image/png" href="picture/icon.png">
</head>
<header>
    <img src="picture/LOGO.png" width="50px">
</header>

<body>
    <div class="pic1">
        <img src="picture/Mask group.png" width="750px">
    </div>
    <div class="hero-banner">
        <img src="picture/Mask group.png" alt="BoyCold Café hero">
    </div>
    <div class="otp-container">
        <h1 class="font">OTP Verification</h1>
        <p class="p1">Enter the 6-digit verification code sent to your email</p>

        <?php if ($error): ?>
            <p style="color:red;font-size:14px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color:green;font-size:14px;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="post" action="otp.php?mode=<?= $mode ?>" id="otpForm">
            <input type="hidden" name="action" value="verify">
            <div class="otp-inputs">
                <input type="text" name="o1" maxlength="1" required>
                <input type="text" name="o2" maxlength="1" required>
                <input type="text" name="o3" maxlength="1" required>
                <input type="text" name="o4" maxlength="1" required>
                <input type="text" name="o5" maxlength="1" required>
                <input type="text" name="o6" maxlength="1" required>
                <br><br>
            </div>
            <input type="hidden" name="otp" id="otpHidden">
            <div class="terms" style="padding-left: 135px;">
                <button type="submit">Log In</button>
            </div>
        </form>

        <form method="post" action="otp.php?mode=<?= $mode ?>" class="resend-form" id="resendForm">
            <input type="hidden" name="action" value="resend">
            <p class="resend-text" style="padding-left: 140px;">Didn't receive code?
                <span id="resendLink" class="resend-link" onclick="handleResend()">Resend OTP</span>
                <span id="countdown" style="display:none; font-size:20px; color:#6F4E37; font-family:'Afacad',sans-serif;"></span>
            </p>
        </form>

        <script>
            const digits = document.querySelectorAll('.otp-inputs input');
            digits.forEach((inp, i) => {
                inp.addEventListener('input', () => {
                    inp.value = inp.value.replace(/\D/g, '');
                    if (inp.value && i < digits.length - 1) digits[i + 1].focus();
                });
                inp.addEventListener('keydown', e => {
                    if (e.key === 'Backspace' && !inp.value && i > 0) digits[i - 1].focus();
                });
                inp.addEventListener('paste', e => {
                    e.preventDefault();
                    const p = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                    [...p].forEach((ch, j) => {
                        if (digits[j]) digits[j].value = ch;
                    });
                });
            });
            document.getElementById('otpForm').addEventListener('submit', function() {
                let fullOtp = '';
                digits.forEach(d => fullOtp += d.value);
                document.getElementById('otpHidden').value = fullOtp;
            });

            function handleResend() {
                const link = document.getElementById('resendLink');
                const countdown = document.getElementById('countdown');

                let seconds = 60;

                localStorage.setItem('resendTime', Date.now());

                link.style.display = 'none';
                countdown.style.display = 'inline';

                const timer = setInterval(() => {
                    const elapsed = Math.floor((Date.now() - localStorage.getItem('resendTime')) / 1000);
                    const remaining = 60 - elapsed;

                    if (remaining <= 0) {
                        clearInterval(timer);
                        countdown.style.display = 'none';
                        link.style.display = 'inline';
                        localStorage.removeItem('resendTime');
                    } else {
                        countdown.textContent = `Resend in ${remaining}s`;
                    }
                }, 1000);

                // Submit AFTER UI updates (small delay avoids glitch)
                setTimeout(() => {
                    document.getElementById('resendForm').submit();
                }, 300);
            }
            window.onload = function() {
                const saved = localStorage.getItem('resendTime');
                const link = document.getElementById('resendLink');
                const countdown = document.getElementById('countdown');

                if (saved) {
                    let seconds = 60;

                    link.style.display = 'none';
                    countdown.style.display = 'inline';

                    const timer = setInterval(() => {
                        const elapsed = Math.floor((Date.now() - saved) / 1000);
                        const remaining = 60 - elapsed;

                        if (remaining <= 0) {
                            clearInterval(timer);
                            countdown.style.display = 'none';
                            link.style.display = 'inline';
                            localStorage.removeItem('resendTime');
                        } else {
                            countdown.textContent = `Resend in ${remaining}s`;
                        }
                    }, 1000);
                }
            };
        </script>
</body>

</html>