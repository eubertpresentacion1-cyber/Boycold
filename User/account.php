<?php
session_start();
require_once '../config/db_config.php';

// Session guard
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$successMsg = '';
$errorMsg   = '';

// Handle AJAX POST (name / phone / address)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $field = $_POST['field'] ?? '';

    if ($field === 'name') {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname  = trim($_POST['lastname']  ?? '');
        if ($firstname === '' || $lastname === '') {
            echo json_encode(['success' => false, 'error' => 'First and last name cannot be empty.']);
            exit;
        }
        $stmt = $connect->prepare("UPDATE users SET firstname=?, lastname=? WHERE id=?");
        $stmt->bind_param("ssi", $firstname, $lastname, $userId);
        $stmt->execute();
        $_SESSION['user_name'] = $firstname . ' ' . $lastname;
        echo json_encode(['success' => true, 'fullname' => htmlspecialchars($firstname . ' ' . $lastname)]);
        exit;
    } elseif ($field === 'phone') {
        $value = trim($_POST['value'] ?? '');
        if ($value !== '' && !preg_match('/^09\d{9}$/', $value)) {
            echo json_encode(['success' => false, 'error' => 'Phone must be 11 digits starting with 09 (e.g. 09123456789).']);
            exit;
        }
        $stmt = $connect->prepare("UPDATE users SET phone=? WHERE id=?");
        $stmt->bind_param("si", $value, $userId);
        $stmt->execute();
        $_SESSION['user_phone'] = $value;
        echo json_encode(['success' => true, 'value' => htmlspecialchars($value)]);
        exit;
    } elseif ($field === 'address') {
        $value = trim($_POST['value'] ?? '');
        if ($value === '') {
            echo json_encode(['success' => false, 'error' => 'Address cannot be empty.']);
            exit;
        }
        $stmt = $connect->prepare("UPDATE users SET address=? WHERE id=?");
        $stmt->bind_param("si", $value, $userId);
        $stmt->execute();
        $_SESSION['user_address'] = $value;
        echo json_encode(['success' => true, 'value' => htmlspecialchars($value)]);
        exit;
    } elseif ($field === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate current password
        $stmt = $connect->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if (!$result || !password_verify($currentPassword, $result['password'])) {
            echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
            exit;
        }

        // Validate new password
        if (!$newPassword || !$confirmPassword) {
            echo json_encode(['success' => false, 'error' => 'Both password fields are required.']);
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
            exit;
        }
        if (
            strlen($newPassword) < 8 || strlen($newPassword) > 25
            || !preg_match('/[A-Z]/', $newPassword)
            || !preg_match('/[a-z]/', $newPassword)
            || !preg_match('/[0-9]/', $newPassword)
        ) {
            echo json_encode(['success' => false, 'error' => 'Password does not meet the requirements.']);
            exit;
        }

        // Update password
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $upd = $connect->prepare("UPDATE users SET password=? WHERE id=?");
        $upd->bind_param("si", $hashed, $userId);
        if ($upd->execute()) {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update password.']);
            exit;
        }
    } elseif ($field === 'email_send_otp') {
        // ── Step 1: validate new email and send OTP ──────────────────
        try {
            require_once '../config/mailer.php';
            $newEmail = trim($_POST['new_email'] ?? '');

            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
                exit;
            }

            // Check if email already taken by another user
            $checkStmt = $connect->prepare("SELECT id FROM users WHERE email=? AND id!=?");
            $checkStmt->bind_param("si", $newEmail, $userId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'error' => 'Email taken, use a different email.']);
                exit;
            }

            // Cooldown: prevent spamming OTP to the same new email within 60s
            $chk = $connect->prepare(
                "SELECT otp_send FROM otp WHERE email=? AND type='email_change' AND status='pending'
                 AND otp_send >= NOW() - INTERVAL 60 SECOND ORDER BY id DESC LIMIT 1"
            );
            $chk->bind_param("s", $newEmail);
            $chk->execute();
            $lastRow = $chk->get_result()->fetch_assoc();
            if ($lastRow) {
                $wait = max(0, 60 - (time() - strtotime($lastRow['otp_send'])));
                echo json_encode(['success' => false, 'error' => "Please wait {$wait}s before requesting another OTP."]);
                exit;
            }

            // Expire any previous pending OTPs for this new email + type
            $exp = $connect->prepare("UPDATE otp SET status='expired' WHERE email=? AND type='email_change' AND status='pending'");
            $exp->bind_param("s", $newEmail);
            $exp->execute();

            // Get current user's name for the email body
            $nq = $connect->prepare("SELECT firstname, lastname FROM users WHERE id=?");
            $nq->bind_param("i", $userId);
            $nq->execute();
            $nr       = $nq->get_result()->fetch_assoc();
            $fullName = $nr ? $nr['firstname'] . ' ' . $nr['lastname'] : $newEmail;

            // Generate OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $ip  = $_SERVER['REMOTE_ADDR'];

            $ins = $connect->prepare(
                "INSERT INTO otp (email, otp, type, status, otp_send, ip, expires_at)
                 VALUES (?, ?, 'email_change', 'pending', NOW(), ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))"
            );
            if ($ins === false) {
                throw new Exception('Prepare failed for otp insert: ' . $connect->error);
            }
            $ins->bind_param("sss", $newEmail, $otp, $ip);
            if (!$ins->execute()) {
                throw new Exception('Insert failed for otp: ' . $ins->error);
            }

            // Re-use the existing mailer helper (type = 'email_change' will render as generic OTP)
            if (!sendOTPEmail($newEmail, $fullName, $otp, 'email_change')) {
                throw new Exception('Failed to send OTP email. Please check mail configuration.');
            }

            // Store the intended new email in session so the verify step knows what to update to
            $_SESSION['pending_email_change'] = $newEmail;

            echo json_encode(['success' => true, 'message' => "A 6-digit OTP has been sent to {$newEmail}."]);
            exit;
        } catch (\Throwable $e) {
            error_log('email_send_otp error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Could not send OTP. Please try again later.']);
            exit;
        }

    } elseif ($field === 'email_verify_otp') {
        // ── Step 2: verify OTP and commit the email change ───────────
        try {
            $otp      = trim($_POST['otp'] ?? '');
            $newEmail = $_SESSION['pending_email_change'] ?? '';

            if (!$newEmail) {
                echo json_encode(['success' => false, 'error' => 'Session expired. Please start over.']);
                exit;
            }
            if (!preg_match('/^\d{6}$/', $otp)) {
                echo json_encode(['success' => false, 'error' => 'Please enter a valid 6-digit OTP.']);
                exit;
            }

            $stmt = $connect->prepare(
                "SELECT id, otp, expires_at, attempts FROM otp
                 WHERE email=? AND type='email_change' AND status='pending'
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->bind_param("s", $newEmail);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            if (!$row) {
                echo json_encode(['success' => false, 'error' => 'No active OTP found. Please request a new one.']);
                exit;
            }
            if (new DateTime() > new DateTime($row['expires_at'])) {
                $u = $connect->prepare("UPDATE otp SET status='expired' WHERE id=?");
                $u->bind_param("i", $row['id']); $u->execute();
                echo json_encode(['success' => false, 'error' => 'OTP has expired. Please request a new one.']);
                exit;
            }
            if ($row['attempts'] >= 5) {
                echo json_encode(['success' => false, 'error' => 'Too many failed attempts. Please request a new OTP.']);
                exit;
            }
            if ($otp !== $row['otp']) {
                $u = $connect->prepare("UPDATE otp SET attempts=attempts+1 WHERE id=?");
                $u->bind_param("i", $row['id']); $u->execute();
                $left = 5 - ($row['attempts'] + 1);
                echo json_encode(['success' => false, 'error' => "Incorrect OTP. {$left} attempt(s) remaining."]);
                exit;
            }

            // OTP correct — mark verified
            $u = $connect->prepare("UPDATE otp SET status='verified' WHERE id=?");
            $u->bind_param("i", $row['id']); $u->execute();

            // Double-check the new email isn't taken (race condition guard)
            $chk2 = $connect->prepare("SELECT id FROM users WHERE email=? AND id!=?");
            $chk2->bind_param("si", $newEmail, $userId);
            $chk2->execute();
            if ($chk2->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'error' => 'Email was taken by another account. Please choose a different email.']);
                exit;
            }

            // Commit the email change
            $upd = $connect->prepare("UPDATE users SET email=? WHERE id=?");
            $upd->bind_param("si", $newEmail, $userId);
            if ($upd->execute()) {
                $_SESSION['user_email'] = $newEmail;
                unset($_SESSION['pending_email_change']);
                echo json_encode(['success' => true, 'new_email' => htmlspecialchars($newEmail), 'message' => 'Email updated successfully!']);
                exit;
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update email.']);
                exit;
            }
        } catch (\Throwable $e) {
            error_log('email_verify_otp error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Could not verify OTP. Please try again later.']);
            exit;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Unknown field.']);
    exit;
}

// Fetch latest user data
$stmt = $connect->prepare(
    "SELECT firstname, lastname, email, phone, address, avatar, card_no FROM users WHERE id = ?"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Guard: if user row is gone (e.g. DB reset), destroy session and redirect
if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$email    = htmlspecialchars($user['email']);
$phone    = $user['phone']   ? htmlspecialchars($user['phone'])   : '';
$address  = $user['address'] ? htmlspecialchars($user['address']) : '';
$avatar   = $user['avatar']  ? htmlspecialchars($user['avatar'])  : '';
$cardNo   = $user['card_no'] ? htmlspecialchars($user['card_no']) : '—';

// Fetch favorites count
$favStmt = $connect->prepare("SELECT COUNT(*) AS cnt FROM favorites WHERE user_id = ?");
$favStmt->bind_param("i", $userId);
$favStmt->execute();
$favCount = $favStmt->get_result()->fetch_assoc()['cnt'] ?? 0;

// Keep session in sync
if ($avatar) $_SESSION['user_avatar'] = $avatar;
$_SESSION['user_name']  = $user['firstname'] . ' ' . $user['lastname'];
$_SESSION['user_email'] = $user['email'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/account.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Account</title>
</head>

<body>

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR DRAWER -->
    <div class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li><a href="home.php">HOME</a></li>
                <li><a href="menu.php">MENU</a></li>
                <li><a href="../order/status.php">ORDER</a></li>
                <li><a href="../store/store.php">STORES</a></li>
                <li class="sidebar-nav-only-not"><a href="../order/status.php">ORDERS</a></li>
                <li class="sidebar-nav-only"><a href="favorites.php">FAVORITES</a></li>
                <li><a href="../store/store.php"><i class="fa-solid fa-location-dot"></i> FIND A STORE</a></li>
                <li><a href="cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i> CART
                    </a></li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <a href="account.php" class="sidebar-avatar-link">
                <div class="sidebar-avatar" id="sidebarAvatarWrap">
                    <?php if ($avatar): ?>
                        <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar" onerror="this.style.display='none'; const icon=this.parentElement.querySelector('.fa-user'); if(icon) icon.style.display='';">
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon" style="display:none;"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon"></i>
                        <img id="sidebarAvatarImg" src="" alt="avatar" style="display:none;">
                    <?php endif; ?>
                </div>
            </a>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name" id="display-fullname"><?= $fullName ?></span>
                <span class="sidebar-user-email"><?= $email ?></span>
            </div>
        </div>
    </div>

    <!-- MAIN NAV -->
    <nav id="mainNav">
        <div class="nav-box"></div>
        <div class="nav-left-group">
            <div class="hamburger" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </div>
            <ul class="nav-links">
                <li><a href="home.php">HOME</a></li>
                <li><a href="menu.php">MENU</a></li>
                <li><a href="../order/status.php">ORDERS</a></li>
            </ul>
        </div>
        <div class="logo">
            <img src="../picture/Boycold Logo 2.png" alt="BoyCold">
        </div>
        <div class="nav-right-group">
            <a href="cart.php" class="cart-link">
                <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i>
            </a>
            <div class="avatar-dropdown-wrap">
                <div class="sidebar-avatar" id="navAvatarBtn" onclick="toggleAvatarDropdown()">
                    <?php if ($avatar): ?>
                        <img id="navAvatarImg" src="<?= $avatar ?>" alt="avatar" style="display:block;" onerror="this.style.display='none'; const icon=this.parentElement.querySelector('.fa-user'); if(icon) icon.style.display='';">
                        <i class="fa-solid fa-user" id="navAvatarIcon" style="display:none;"></i>
                    <?php else: ?>
                        <img id="navAvatarImg" src="" alt="avatar" style="display:none;">
                        <i class="fa-solid fa-user" id="navAvatarIcon"></i>
                    <?php endif; ?>
                </div>
                <div class="avatar-dropdown" id="avatarDropdown">
                    <a href="account.php"><i class="fa-solid fa-user"></i> Account</a>
                    <hr>
                    <a href="../logout.php" class="dropdown-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ACCOUNT MAIN -->
    <main class="account-main">
        <div class="account-grid">

            <!-- CARD 1: Profile -->
            <div class="card card-profile">
                <div class="profile-avatar" id="profileAvatarWrap" onclick="openAvatarModal()" title="Click to change photo" style="cursor:pointer; position:relative;">
                    <?php if ($avatar): ?>
                        <img id="profileAvatarImg" src="<?= $avatar ?>" alt="avatar" style="width:110px;height:110px;object-fit:cover;border-radius:50%;display:block;" onerror="this.style.display='none'; const icon=this.parentElement.querySelector('.fa-user'); if(icon) icon.style.display='';">
                        <i class="fa-solid fa-user" id="profileAvatarIcon" style="display:none;font-size:2.5rem;"></i>
                    <?php else: ?>
                        <img id="profileAvatarImg" src="" alt="avatar" style="width:110px;height:110px;object-fit:cover;border-radius:50%;display:none;">
                        <i class="fa-solid fa-user" id="profileAvatarIcon" style="font-size:2.5rem;"></i>
                    <?php endif; ?>
                    <div id="avatarOverlay" style="position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;pointer-events:none;">
                        <i class="fa-solid fa-camera" style="color:#fff;font-size:1.4rem;"></i>
                    </div>
                    <input type="file" id="avatarFileInput" accept="image/*" style="display:none;">
                    <input type="file" id="avatarCameraInput" accept="image/*" capture="user" style="display:none;">
                </div>
                <div id="avatar-msg" style="font-size:.78rem;text-align:center;margin-top:4px;min-height:1.1em;"></div>
                <div class="profile-info">
                    <div class="profile-name"><?= $fullName ?></div>

                    <!-- Email (read-only) -->
                    <div class="profile-detail">
                        <i class="fa-solid fa-envelope"></i>
                        <span><?= $email ?></span>
                    </div>

                    <!-- Address -->
                    <div class="profile-detail" id="address-display">
                        <i class="fa-solid fa-location-dot"></i>
                        <?php if ($address): ?>
                            <strong id="address-val"><?= $address ?></strong>
                        <?php else: ?>
                            <span class="placeholder-text" id="address-val">* Add your address</span>
                        <?php endif; ?>
                    </div>
                    <div class="edit-inline" id="address-edit">
                        <input type="text" id="address-input" placeholder="e.g. 123 Mango St, BGC" maxlength="200"
                            value="<?= $address ?>">
                    </div>
                    <div id="address-msg"></div>

                    <!-- Phone -->
                    <div class="profile-detail" id="phone-display">
                        <i class="fa-solid fa-phone"></i>
                        <?php if ($phone): ?>
                            <span id="phone-val"><?= $phone ?></span>
                        <?php else: ?>
                            <span class="placeholder-text" id="phone-val">* Add your phone number</span>
                        <?php endif; ?>
                    </div>
                    <div class="edit-inline" id="phone-edit">
                        <input type="tel" id="phone-input" placeholder="09XXXXXXXXX" maxlength="11"
                            value="<?= $phone ?>">
                    </div>
                    <div id="phone-msg"></div>

                </div>
            </div>

            <!-- CARD 2: Store / Loyalty — portrait, spans 2 rows -->
            <div class="card card-store">
                <!-- TOP: background image + yellow overlay + logo + name -->
                <div class="store-banner">
                    <div class="store-banner-overlay"></div>
                    <div class="store-logo-circle">
                        <img src="../picture/icon2.png" alt="BoyCold">
                    </div>
                    <div class="store-name">Boycold<br>Cafe</div>
                </div>

                <!-- BOTTOM: loyalty info -->
                <div class="store-info">
                    <div class="loyalty">Loyalty Card</div>
                    <div class="loyalty-level">Level 1</div>
                    <div class="beans-row">
                        <!-- FILLED bean -->
                        <svg width="25" height="27" viewBox="0 0 25 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.51146 24.3952C9.97896 28.2235 15.9937 24.2871 19.8221 18.8196C23.6504 13.3521 25.2909 6.35484 19.8247 2.52652C14.3586 -1.3018 8.34245 2.6333 4.51413 8.10081C0.685812 13.5683 -0.95604 20.5669 4.51146 24.3952Z" fill="#6F4E37" stroke="black" stroke-width="2" />
                            <path d="M18.2922 4.71298C16.4327 5.04135 11.1828 7.88323 11.0734 12.6953C10.9626 17.5061 7.35316 21.4973 6.04102 22.2074C8.44773 22.2634 13.1504 19.0371 13.2598 14.225C13.3706 9.41429 16.9787 5.42312 18.2922 4.71298Z" fill="black" />
                        </svg>
                        <!-- EMPTY beans (4) -->
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <svg width="25" height="27" viewBox="0 0 25 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.51146 24.3952C9.97896 28.2235 15.9937 24.2871 19.8221 18.8196C23.6504 13.3521 25.2909 6.35484 19.8247 2.52652C14.3586 -1.3018 8.34245 2.6333 4.51413 8.10081C0.685812 13.5683 -0.95604 20.5669 4.51146 24.3952Z" stroke="black" stroke-width="2" />
                                <path d="M18.2931 4.71298C16.4337 5.04135 11.1838 7.88323 11.0743 12.6953C10.9635 17.5061 7.35414 21.4973 6.04199 22.2074C8.44871 22.2634 13.1513 19.0371 13.2608 14.225C13.3716 9.41429 16.9797 5.42312 18.2931 4.71298Z" fill="black" />
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <div class="beans-row-2">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <svg width="25" height="27" viewBox="0 0 25 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.51146 24.3952C9.97896 28.2235 15.9937 24.2871 19.8221 18.8196C23.6504 13.3521 25.2909 6.35484 19.8247 2.52652C14.3586 -1.3018 8.34245 2.6333 4.51413 8.10081C0.685812 13.5683 -0.95604 20.5669 4.51146 24.3952Z" stroke="black" stroke-width="2" />
                                <path d="M18.2931 4.71298C16.4337 5.04135 11.1838 7.88323 11.0743 12.6953C10.9635 17.5061 7.35414 21.4973 6.04199 22.2074C8.44871 22.2634 13.1513 19.0371 13.2608 14.225C13.3716 9.41429 16.9797 5.42312 18.2931 4.71298Z" fill="black" />
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <div class="card-no">Card no: <?= $cardNo ?></div>
                </div>
            </div>

            <!-- CARD 5: Favorites -->
            <div class="card card-small"onclick="window.location.href='favorites.php'">
                <div class="card-small-header">
                    <div class="card-small-title">Favorites</div>
                    <div class="card-small-sub">
                        <?= $favCount > 0
                            ? $favCount . ' saved ' . ($favCount === 1 ? 'item' : 'items')
                            : 'No saved items' ?>
                    </div>
                </div>
                <div class="card-small-icon">
                    <svg width="78" height="70" viewBox="0 0 78 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M77.3558 50.773C77.0979 47.1336 71.2665 47.5526 67.337 47.1013C66.8649 46.9602 66.3782 46.8737 65.8864 46.8435C64.584 43.9276 62.8829 41.2067 60.8319 38.7588C58.4465 35.5353 56.2545 36.6958 54.5493 40.1772L51.906 45.9763C51.906 46.2664 51.2645 46.75 51.1355 47.169C50.7388 47.1322 50.3395 47.1322 49.9428 47.169C47.1825 47.082 44.4273 47.4624 41.7937 48.2941C40.0208 49.2611 40.1497 51.5176 41.6325 53.0294C43.1186 54.2802 44.74 55.36 46.4678 56.253C47.5864 57.0427 48.8565 57.6036 50.2007 57.8937C50.4828 57.8937 50.7534 57.7817 50.9529 57.5822C51.1524 57.3827 51.2645 57.1121 51.2645 56.83C51.2645 56.5478 51.1524 56.2773 50.9529 56.0778C50.7534 55.8783 50.4828 55.7662 50.2007 55.7662L47.7508 54.4446C45.7232 52.9617 44.5337 52.8006 42.922 50.5151C42.922 50.5151 50.1362 50.096 51.5546 50.0638C53.7466 50.0638 53.6499 48.2264 57.9984 40.2094C59.2384 41.7911 60.3226 43.477 61.251 45.2672C61.8957 46.8338 62.6693 48.337 63.5719 49.7769C64.5358 50.8084 66.1765 50.4538 66.789 50.5505C68.9581 50.6321 71.1151 50.9126 73.2329 51.3886C71.1021 52.9359 68.8005 54.2318 66.3732 55.2504C66.0278 55.5021 65.7661 55.852 65.6224 56.2544C65.4786 56.6569 65.4594 57.0934 65.5673 57.5069C66.4248 60.1825 66.9857 62.9515 67.2435 65.7527C64.2203 64.3156 61.5144 62.2892 59.2846 59.7924C59.0011 59.5129 58.619 59.3561 58.2209 59.3561C57.8227 59.3561 57.4406 59.5129 57.1571 59.7924C55.0973 61.3719 51.2967 67.9737 48.2698 67.8126C48.4955 67.329 47.9475 67.1356 48.7856 64.9468C49.6237 62.7581 51.4901 60.3404 50.0105 59.7924C49.7796 59.7087 49.525 59.7193 49.3018 59.8219C49.0786 59.9244 48.9048 60.1107 48.8178 60.3404C48.2053 61.8877 47.4962 62.9192 46.9482 64.2087C46.3254 65.5711 46.0169 67.0562 46.0456 68.554C46.5291 70.4559 49.2691 70.2624 51.0743 69.2309C53.1766 67.8871 55.0379 66.1991 56.5801 64.2377C57.2119 63.8057 57.8136 63.3329 58.3853 62.8193C60.3802 64.9978 62.6798 66.8761 65.2127 68.396C66.9534 69.3599 68.6909 70.5526 69.9481 69.2309C71.2053 67.9093 70.4961 66.8777 70.5283 66.0074C70.4542 63.1736 70.0209 60.3606 69.2389 57.6359C72.0434 56.3787 77.5814 53.7999 77.3558 50.773Z" fill="black" />
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M36.8655 53.5774C29.2415 54.143 21.5758 53.5562 14.1267 51.8367C9.13345 50.515 7.68286 43.0107 6.87698 37.7917C5.39415 29.8683 5.84544 32.3182 5.74874 31.0932C10.7775 31.9636 48.0415 31.7702 48.3639 31.7702C47.9129 32.5676 47.5775 33.425 47.3678 34.3168C47.1422 35.474 46.6586 40.7574 46.6909 40.435C46.6748 40.7142 46.769 40.9885 46.9531 41.199C47.1373 41.4094 47.3967 41.5392 47.6755 41.5603C47.9543 41.5814 48.2303 41.4922 48.444 41.3119C48.6578 41.1316 48.7922 40.8746 48.8184 40.5962C51.1393 35.5675 51.4262 32.8017 51.0426 31.5768C51.999 31.5037 52.9424 31.3425 53.8729 31.0932C54.6408 30.7453 55.3051 30.204 55.8009 29.5221C56.2967 28.8403 56.6069 28.0414 56.7011 27.2037C56.7954 26.3659 56.6704 25.5181 56.3385 24.7432C56.0066 23.9682 55.4791 23.2928 54.8077 22.783C53.6757 22.2874 52.466 21.9929 51.2328 21.9126C51.7222 19.0883 51.4385 16.1842 50.4119 13.508C49.3854 10.8317 47.6541 8.48294 45.4014 6.71042C44.2121 5.75405 42.7664 5.17085 41.2463 5.03418C40.9884 1.16916 38.026 -0.120256 34.1932 0.00868569L22.1146 0.556687C21.8325 0.556687 21.5619 0.668762 21.3624 0.868257C21.1629 1.06775 21.0509 1.33833 21.0509 1.62045C21.0509 1.90258 21.1629 2.17316 21.3624 2.37265C21.5619 2.57215 21.8325 2.68422 22.1146 2.68422C33.6775 3.03881 35.4826 2.8454 36.8978 3.65128C37.1645 3.83382 37.3751 4.08702 37.5061 4.3825C37.637 4.67799 37.6831 5.00409 37.6392 5.3243V6.4203C36.7043 8.58007 30.9084 7.70971 28.3296 7.9676C26.6211 8.19324 20.793 9.32148 19.8904 7.9676C19.5316 7.33337 19.3431 6.61709 19.3431 5.88841C19.3431 5.15974 19.5316 4.44346 19.8904 3.80923C20.0657 3.6297 20.1624 3.38789 20.1594 3.13701C20.1564 2.88613 20.0538 2.64673 19.8743 2.47147C19.7854 2.38469 19.6803 2.31626 19.5649 2.27011C19.4496 2.22395 19.3263 2.20097 19.202 2.20247C18.9512 2.20549 18.7118 2.30805 18.5365 2.48758C17.8305 3.44981 17.4055 4.58911 17.3088 5.7786C17.212 6.9681 17.4473 8.1611 17.9885 9.22477C19.568 12.2162 25.3382 11.478 28.7164 11.3168C32.1011 11.1557 39.9601 12.9286 41.2463 7.19072C42.1167 7.54531 41.891 6.99731 43.6317 8.8992C46.666 12.5833 48.598 17.0496 49.2052 21.7837C48.9231 21.7837 48.6525 21.8957 48.453 22.0952C48.2535 22.2947 48.1415 22.5653 48.1415 22.8474C48.1415 23.1296 48.2535 23.4001 48.453 23.5996C48.6525 23.7991 48.9231 23.9112 49.2052 23.9112C50.8234 24.0015 52.4191 24.3399 53.9374 24.9105C54.8077 25.394 54.6143 28.3565 53.2604 28.6788C49.1752 29.1758 45.0447 29.1758 40.9594 28.6788C1.14553 27.9696 8.29533 27.8729 3.6889 26.8736C3.2376 26.3901 3.94678 25.0685 4.59149 24.7783C6.11438 24.1138 7.76222 23.784 9.42357 23.8113C10.8742 23.8113 32.2591 22.7475 44.5988 23.2633C44.726 23.2708 44.8533 23.2522 44.973 23.2086C45.0927 23.1651 45.2023 23.0976 45.295 23.0102C45.3877 22.9228 45.4615 22.8174 45.512 22.7004C45.5624 22.5835 45.5885 22.4574 45.5885 22.3301C45.5885 22.2027 45.5624 22.0766 45.512 21.9597C45.4615 21.8427 45.3877 21.7373 45.295 21.6499C45.2023 21.5625 45.0927 21.495 44.973 21.4515C44.8533 21.408 44.726 21.3894 44.5988 21.3968C33.5807 20.4943 8.45651 20.5265 8.19862 20.5265C9.6202 16.5794 11.713 12.9075 14.3846 9.67285C17.1891 7.54531 16.9634 7.48084 16.9634 7.09402C16.9677 6.95163 16.9434 6.80983 16.8919 6.67702C16.8404 6.54422 16.7627 6.42311 16.6635 6.32089C16.5643 6.21867 16.4455 6.13742 16.3143 6.08196C16.1831 6.0265 16.0421 5.99795 15.8997 5.99801C14.7392 5.99801 13.3531 7.06178 12.6761 7.60978C10.5733 9.07166 8.87819 11.0458 7.75117 13.3456C6.62415 15.6453 6.10247 18.1945 6.23549 20.7521C4.97791 20.7691 3.75274 21.1534 2.71089 21.8579C1.66903 22.5624 0.855979 23.5563 0.371881 24.7171C-0.0682465 25.6866 -0.119688 26.7881 0.228157 27.7944C0.576002 28.8008 1.29663 29.6354 2.24153 30.1262C2.66059 30.3411 3.10114 30.5023 3.56318 30.6097C3.58897 31.1405 3.65344 31.667 3.75659 32.1892C4.59149 41.4988 4.33683 52.8682 13.1919 55.2826C16.7488 55.9848 20.3573 56.3945 23.9811 56.5076C28.3651 56.614 32.7523 56.3432 37.0912 55.7017C37.3669 55.6641 37.6171 55.5205 37.7888 55.3015C37.9605 55.0825 38.04 54.8052 38.0107 54.5285C37.9813 54.2517 37.8454 53.9973 37.6316 53.8191C37.4178 53.641 37.143 53.5531 36.8655 53.5742" fill="black" />
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M15.1269 36.3121C12.3547 35.2161 15.6749 49.7768 18.3472 48.6486C18.5401 48.607 18.7176 48.5127 18.8599 48.3761C19.0022 48.2394 19.1038 48.0659 19.1531 47.8749C19.7011 46.8112 16.6098 35.6352 15.1269 36.3121ZM39.5742 36.3121C38.0914 35.6352 34.9968 46.8112 35.4803 47.8749C35.5416 48.044 35.6447 48.1947 35.78 48.3131C35.9153 48.4314 36.0783 48.5136 36.254 48.5519C39.0262 49.7768 42.3465 35.2161 39.5742 36.3121ZM27.1411 35.3451C26.9476 35.3451 26.6253 35.3451 26.4963 35.7964C25.3307 39.7212 25.1865 43.8787 26.0773 47.8749C26.0491 48.1936 26.1434 48.511 26.341 48.7626C26.5387 49.0141 26.8248 49.1809 27.1411 49.2288C29.5909 49.2288 28.5594 39.1778 28.0759 36.1509C27.7213 35.4418 27.3989 35.2484 27.1411 35.3451Z" fill="black" />
                    </svg>
                </div>
            </div>

            <!-- CARD 6: Order and Deliveries -->
            <div class="card card-small">
                <div class="card-small-header">
                    <div class="card-small-title">Order and Deliveries</div>
                    <div class="card-small-sub">No active orders</div>
                </div>
                <div class="card-small-icon">
                    <svg width="68" height="70" viewBox="0 0 68 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M34.9227 26.8252C35.8844 27.5252 37.0047 27.9759 38.1832 28.1371C39.3618 28.2982 40.5619 28.1648 41.6763 27.7488C42.7907 27.3327 43.7846 26.647 44.5692 25.7529C45.3537 24.8588 45.9045 23.7842 46.1722 22.6252C47.5243 24.1818 49.3826 25.2109 51.4194 25.5311C53.4562 25.8513 55.5406 25.4419 57.305 24.3752C56.4639 27.4628 56.5655 30.7315 57.5967 33.761C57.632 33.983 57.7508 34.1831 57.9288 34.3204C58.1068 34.4577 58.3306 34.5217 58.5543 34.4993C58.778 34.477 58.9847 34.3699 59.132 34.2001C59.2793 34.0303 59.3561 33.8106 59.3467 33.586C60.2628 29.9517 60.3624 26.1596 59.6384 22.4823C62.8729 18.6031 61.3009 13.5632 59.6384 9.16195C58.5015 5.93027 56.342 3.15738 53.4872 1.26367C50.9526 -0.719653 16.6207 -0.0196569 9.68201 1.00117C9.54477 1.00956 9.41077 1.0464 9.28853 1.10935C9.16628 1.17229 9.05847 1.25998 8.97192 1.36682C8.88538 1.47367 8.822 1.59735 8.78581 1.73C8.74963 1.86265 8.74142 2.00138 8.76172 2.13737C8.78202 2.27336 8.83037 2.40365 8.90372 2.51995C8.97706 2.63626 9.07379 2.73604 9.18776 2.81296C9.30173 2.88988 9.43046 2.94225 9.56575 2.96676C9.70105 2.99127 9.83997 2.98738 9.97368 2.95532C11.8403 2.72199 14.6082 2.54699 17.7553 2.45949C16.4342 6.01569 15.5812 9.72861 15.2178 13.5048C12.005 14.3354 8.67402 14.6116 5.36829 14.3215C4.58551 14.3365 3.80522 14.4145 3.03497 14.5548C4.1751 10.9267 5.609 7.39757 7.32244 4.0024C7.35389 3.79641 7.31321 3.58587 7.2073 3.40642C7.10139 3.22696 6.93674 3.08959 6.74121 3.01755C6.54568 2.94552 6.33126 2.94323 6.13424 3.01108C5.93721 3.07893 5.76967 3.21275 5.65995 3.3899C4.51305 5.30205 3.53705 7.31163 2.74331 9.39528C-5.0354 25.6293 9.15993 30.0306 14.9291 22.2198C16.7666 25.1364 17.5832 27.1752 21.2524 27.6681C23.6013 27.9369 25.969 27.3577 27.9286 26.0348C29.2545 25.194 30.347 24.0323 31.1048 22.6573C31.9845 24.3611 33.3024 25.7997 34.9227 26.8252ZM51.8859 3.50657C54.2118 5.07829 55.9537 7.37386 56.8413 10.0369C58.2909 12.8836 58.6204 16.1677 57.7746 19.2477C57.3716 19.9772 56.8123 20.6086 56.1367 21.0965C55.4611 21.5845 54.6859 21.9171 53.8667 22.0704C53.0476 22.2237 52.2046 22.1939 51.3983 21.9833C50.5919 21.7726 49.8421 21.3863 49.2026 20.8519C45.8222 16.8269 46.2889 16.8561 46.4347 15.9519C48.3597 15.9519 50.3138 15.7186 52.2651 15.6894C52.3953 15.6894 52.5243 15.6638 52.6446 15.6139C52.7649 15.5641 52.8742 15.491 52.9663 15.399C53.0584 15.3069 53.1314 15.1976 53.1813 15.0772C53.2311 14.9569 53.2567 14.828 53.2567 14.6978C53.2567 14.5675 53.2311 14.4386 53.1813 14.3183C53.1314 14.1979 53.0584 14.0886 52.9663 13.9965C52.8742 13.9045 52.7649 13.8314 52.6446 13.7816C52.5243 13.7317 52.3953 13.7061 52.2651 13.7061L46.4347 13.5311C46.2144 9.93401 45.6181 6.36987 44.6555 2.89699C47.018 2.89699 48.9722 3.18866 50.4888 3.36366C51.0693 3.45115 51.7401 3.85949 51.8859 3.65532V3.50657ZM42.5001 2.89407C42.4398 6.52176 42.6739 10.1484 43.2001 13.7382L32.6215 13.4465C32.9131 9.8182 32.9131 6.17239 32.6215 2.54699C36.1477 2.51783 39.5864 2.63158 42.5001 2.89407ZM19.9136 2.40116H30.5798C29.8798 5.94781 29.5006 9.54695 29.4423 13.1578C25.8379 12.6947 22.196 12.597 18.572 12.8661C19.1845 10.4453 19.9136 2.40116 19.9136 2.40116ZM12.8582 20.706C12.1352 21.8956 11.0267 22.8019 9.71714 23.2741C8.40759 23.7464 6.97584 23.7562 5.65995 23.3019C2.74331 22.1935 2.36414 19.4811 2.74331 16.4798C3.6183 16.3923 4.49329 16.3923 5.36829 16.4798C8.60014 17.0286 11.9014 17.0286 15.1332 16.4798C15.0749 18.1131 13.6749 19.3061 12.8582 20.706ZM26.1494 23.3048C23.9036 24.8798 20.319 25.2881 18.922 22.9839C17.8757 20.8 17.5001 18.355 17.8428 15.9577C21.7916 15.7729 25.7489 15.9094 29.6756 16.3661C29.6257 17.7091 29.2844 19.0251 28.6756 20.2232C28.0667 21.4213 27.2048 22.4727 26.1494 23.3048ZM32.184 16.4244C35.9727 16.4244 38.8572 16.4244 43.8418 16.1036C44.3668 18.5244 42.7626 24.2644 40.0793 24.6727C38.8749 24.6911 37.6896 24.3702 36.6591 23.7466C35.6285 23.123 34.7943 22.2219 34.2519 21.1465C33.3813 19.6989 32.6862 18.1529 32.181 16.5411L32.184 16.4244ZM67.1575 52.0338C66.9406 51.6207 66.6128 51.2763 66.2109 51.0393C65.809 50.8023 65.349 50.682 64.8825 50.6921C64.095 44.4855 63.395 39.358 57.7134 40.3468C57.7134 37.608 55.9051 37.258 53.1955 37.1414C44.7139 37.1414 42.7626 37.2872 41.3043 39.7663C39.6853 39.6207 38.071 40.0921 36.7848 41.0862C35.4987 42.0803 34.6356 43.5237 34.3685 45.1271C33.8968 47.0323 33.7489 49.0033 33.931 50.9575C33.6835 51.0123 33.4663 51.1598 33.3241 51.3698C33.182 51.5798 33.1256 51.8362 33.1667 52.0864C33.2078 52.3366 33.3432 52.5616 33.545 52.7151C33.7468 52.8686 33.9998 52.9389 34.2519 52.9117H39.9072C40.0531 54.0171 40.2864 55.5921 40.4906 57.6891C38.3478 57.6425 36.2099 57.7203 34.0769 57.9225C33.6958 56.4914 33.2194 55.0924 32.6477 53.7254C32.49 53.6137 32.3014 53.5536 32.1081 53.5536C31.9148 53.5536 31.7263 53.6137 31.5685 53.7254C30.8394 54.2213 31.0727 54.1338 34.106 65.3833C34.2519 66.6316 34.6777 67.8274 35.3602 68.8832C36.6727 70.2512 40.2864 69.8749 41.8001 69.9041C46.7896 70.0868 51.7731 70.0091 56.7509 69.6707C58.53 69.5249 62.7563 70.4845 63.9229 68.1541C65.2733 64.9137 66.2533 61.5304 66.8366 58.0683C67.0992 56.3475 68.2366 54.1017 67.1575 52.0338ZM44.1918 39.6788C47.6743 40.13 51.1713 40.3633 54.683 40.3788C54.6301 41.0151 54.5425 41.648 54.4205 42.2747C54.4205 42.2747 44.046 42.5663 43.4626 42.2747C42.8793 41.983 42.8501 42.0122 42.821 41.7497C42.7949 41.3027 42.9144 40.8593 43.1615 40.486C43.4087 40.1126 43.7701 39.8294 44.1918 39.6788ZM35.7977 50.7563C36.2255 49.3427 36.7407 47.9621 37.3435 46.6146C38.9447 42.5372 38.0698 43.2955 40.6918 41.983C40.7174 42.5272 40.8949 43.0533 41.2042 43.5018C41.5135 43.9503 41.9422 44.3032 42.4418 44.5205C43.9964 45.1038 45.6501 45.3809 47.3097 45.3371C53.1372 45.7455 57.0134 46.7634 57.5384 42.4205C57.8631 42.4399 58.1839 42.4885 58.5009 42.5663C58.7342 42.5663 60.63 42.3913 62.6104 50.2313C53.6711 49.8099 44.7137 49.9853 35.7977 50.7563ZM51.6526 52.7104C51.3901 54.3593 51.254 56.0199 51.2443 57.6921C47.7443 57.4296 46.8693 57.6921 43.8126 57.5462L43.2876 55.7379C41.8585 52.5325 42.0335 53.0867 41.9751 52.8242C45.9126 52.6783 48.1234 52.62 51.6526 52.7075M63.5146 57.2225C62.9896 59.1766 62.6688 61.1891 62.1729 63.0529C60.7729 68.3553 62.1729 65.707 56.3426 66.2583C49.9026 66.8708 43.4335 67.1333 36.9614 67.0458L34.5989 59.76C36.695 59.8416 38.7756 60.0652 40.8406 60.4308C41.2761 62.2391 41.8876 63.9872 42.6751 65.6749C44.3668 67.2208 44.221 61.0112 44.1918 60.7779C46.5504 60.912 48.9109 60.912 51.2734 60.7779C51.4484 62.382 51.5067 65.8237 53.0526 65.3279C54.5955 64.832 53.8401 64.5695 54.2746 60.7487C56.3029 60.8888 58.3404 60.7016 60.3092 60.1946C60.5625 60.1808 60.8009 60.0705 60.9754 59.8863C61.1499 59.7021 61.2471 59.458 61.2471 59.2043C61.2471 58.9507 61.1499 58.7066 60.9754 58.5224C60.8009 58.3382 60.5625 58.2279 60.3092 58.2141C58.3757 58.5748 56.3872 58.5151 54.4788 58.0392C54.4788 56.3767 54.2747 57.6308 54.0705 52.7921C57.5141 52.9865 60.9343 53.3754 64.3313 53.9588C64.1426 55.0671 63.8704 56.156 63.5146 57.2254M15.4832 46.4105C17.4374 46.4105 17.204 41.3384 15.6582 41.3384C13.0041 41.3676 13.9957 46.4105 15.4832 46.4105ZM22.5065 35.7151C22.7982 36.3568 23.4107 42.5051 23.819 46.9063C24.2273 51.3075 25.7994 50.7534 26.2661 46.9063C26.2661 44.2522 26.4702 41.3967 26.4702 41.2509C26.5767 38.7469 26.1697 36.2477 25.2744 33.9068C24.9857 33.4328 24.543 33.0724 24.0203 32.886C19.6946 32.4225 15.3388 32.3055 10.9945 32.536C10.2362 32.7401 9.71118 33.586 10.5278 34.286C10.7612 34.4901 21.544 35.7735 22.5065 35.7151ZM8.71952 46.9063C9.65285 49.0938 10.6445 48.9771 11.6362 46.9063C11.6362 45.5646 11.6362 44.3105 11.8112 43.9897V41.0759C10.9653 35.2485 11.257 35.7443 10.5862 35.4235C10.4621 35.3733 10.3282 35.3523 10.1947 35.362C10.0612 35.3717 9.93172 35.4119 9.81621 35.4795C9.70071 35.5472 9.60226 35.6404 9.52843 35.752C9.4546 35.8636 9.40736 35.9907 9.39035 36.1235C8.86998 39.0082 8.60645 41.9334 8.60285 44.8646C8.48619 45.3021 8.60285 46.0896 8.71952 46.9063Z" fill="black" />
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M27.2278 51.5088C25.4195 51.5088 7.87296 50.5463 4.72589 50.2546C4.19205 50.2113 3.65558 50.2113 3.12174 50.2546C2.59674 28.4848 2.50924 46.9063 2.45091 24.8127C2.43802 24.5898 2.34037 24.3802 2.17797 24.2269C2.01556 24.0736 1.80069 23.9882 1.57737 23.9882C1.35405 23.9882 1.13918 24.0736 0.976777 24.2269C0.814372 24.3802 0.716724 24.5898 0.703836 24.8127C-0.037544 33.4586 -0.193454 42.1448 0.237173 50.8117C0.412172 52.2992 0.878835 53.1159 2.30799 53.3492C10.6059 53.8473 18.9247 53.896 27.2278 53.495C27.4908 53.495 27.7431 53.3906 27.929 53.2046C28.115 53.0186 28.2195 52.7664 28.2195 52.5034C28.2195 52.2404 28.115 51.9881 27.929 51.8022C27.7431 51.6162 27.4908 51.5088 27.2278 51.5088Z" fill="black" />
                    </svg>
                </div>
            </div>

            <!-- CARD 7: Settings — expandable modal -->
            <div class="card card-small card-settings" id="settingsCard">

                <div class="card-collapsed-view" onclick="expandSettings()">
                    <div class="card-small-header">
                        <div class="card-small-title">Settings</div>
                        <div class="card-small-sub">Preferences &amp; security</div>
                    </div>
                    <div class="card-small-icon">
                        <svg width="70" height="70" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M25.1393 31.3131C26.0889 30.7674 26.9189 30.0331 27.5792 29.1544C28.2394 28.2758 28.7162 27.2711 28.9805 26.2012C29.2479 25.1796 29.3083 24.114 29.1582 23.0682C29.008 22.0224 28.6504 21.018 28.1066 20.1149C27.5629 19.2118 26.8442 18.4287 25.9935 17.8123C25.1429 17.196 24.1778 16.7591 23.156 16.5278C21.9363 16.1878 20.6481 16.1878 19.4285 16.5278C18.1427 16.8643 16.9498 17.4919 15.9401 18.3628C14.9304 19.2337 14.1305 20.3251 13.601 21.5543C13.0286 22.8811 12.8535 24.3478 13.097 25.7738C13.3406 27.1999 13.9923 28.5228 14.9718 29.5797C16.288 30.8889 17.9442 31.7959 19.7493 32.196C21.5926 32.5668 23.5089 32.2548 25.1393 31.3131ZM16.3952 22.4961C16.6896 21.6287 17.1716 20.8384 17.8062 20.1823C18.4408 19.5262 19.2122 19.0207 20.0643 18.7026C21.0725 18.3339 22.1769 18.3339 23.1851 18.7026C23.8993 18.8267 24.5753 19.1161 25.1601 19.5481C25.7448 19.9801 26.2223 20.543 26.5552 21.1925C26.8881 21.8421 27.0673 22.5606 27.0787 23.2918C27.0901 24.0229 26.9334 24.7468 26.6209 25.4066C26.3996 26.1353 26.0242 26.8068 25.5206 27.3748C25.0171 27.9429 24.3974 28.3939 23.7043 28.6968C22.6543 29.0706 21.5051 29.0794 20.4435 28.7262C19.2768 28.432 18.2064 27.8257 17.3577 26.9634C16.7999 26.3774 16.4209 25.6419 16.2658 24.8447C16.1107 24.0474 16.1859 23.2219 16.4827 22.4666L16.3952 22.4961Z" fill="black" />
                            <path d="M22.4285 50.2156C23.1518 50.3431 23.881 50.4216 24.616 50.451C25.0651 50.4804 25.5201 50.4304 25.9547 50.3039C26.5584 50.1332 27.0951 49.7859 27.5005 49.3032C28.0722 48.5528 28.4893 47.6935 28.7226 46.7723C29.0726 45.5981 29.0726 44.3061 29.4226 43.1584C29.5167 42.7135 29.7649 42.317 30.1226 42.0401C30.283 41.8943 30.4724 41.7848 30.6781 41.7188C30.8838 41.6528 31.1011 41.6319 31.3155 41.6575C32.6634 42.2398 34.0696 42.674 35.5096 42.9524C36.3205 42.9318 37.108 42.6758 37.7817 42.2167C38.7734 41.4633 39.6863 40.6216 40.5204 39.6916C41.0863 39.1737 41.6113 38.6145 42.0954 38.0141C42.4367 37.558 42.6671 37.0224 42.7633 36.4573C42.8423 35.9187 42.7716 35.3686 42.5592 34.8681C42.2443 34.1268 41.8107 33.4428 41.2759 32.8434C40.6214 32.0528 40.0369 31.2059 39.5288 30.3125C39.3403 30.0541 39.2133 29.7553 39.1576 29.4394C39.102 29.1235 39.1192 28.7989 39.208 28.4908C39.4879 27.9228 39.8846 27.4225 40.3746 27.0223C40.9288 26.5651 41.5315 26.1826 42.1829 25.8745C42.6467 25.6391 43.2883 25.5214 43.8133 25.2565C44.2579 25.0621 44.6405 24.7474 44.9187 24.3471C45.2862 23.8262 45.5167 23.22 45.5896 22.5814C45.6771 21.877 45.6771 21.1727 45.5896 20.4684L45.1812 17.9669C45.1812 17.3812 45.0062 16.6455 44.8604 16.001C44.7879 15.6118 44.6603 15.2351 44.4813 14.8827C44.176 14.2945 43.6826 13.8278 43.0813 13.5583C42.2138 13.2336 41.2995 13.0546 40.3746 13.0286C39.7221 12.9695 39.0876 12.7804 38.508 12.4724C37.9835 12.2756 37.5211 11.9402 37.1692 11.5012C36.9816 11.0624 36.9125 10.5813 36.9689 10.1068C37.0252 9.63229 37.205 9.18129 37.49 8.79961C38.0675 7.90495 38.473 6.9073 38.6859 5.85667C38.7666 5.11791 38.6122 4.3724 38.2452 3.728C37.8782 3.0836 37.3175 2.57374 36.6442 2.27217C35.5409 1.59972 34.3696 1.04822 33.1501 0.62707C32.4533 0.358046 31.6967 0.286798 30.9626 0.421064C28.8393 1.06851 27.7893 3.36106 26.0422 4.41757C25.5105 4.74794 24.8821 4.88307 24.263 4.80016C23.7724 4.74901 23.3072 4.55471 22.9243 4.241C22.5159 3.88442 22.1878 3.44394 21.9618 2.94905C21.9618 2.5959 21.6993 1.86016 21.466 1.38929C21.3678 1.08254 21.1882 0.808761 20.9468 0.597641C20.3371 0.160864 19.5954 -0.0473203 18.8497 0.00905327C17.1424 0.253238 15.4821 0.759386 13.9264 1.50995C13.3031 1.72813 12.7537 2.11962 12.3413 2.63927C11.929 3.15893 11.6707 3.78559 11.596 4.447C11.5727 5.70364 11.6981 6.95733 11.9752 8.18159C12.1026 8.59854 12.1294 9.04019 12.0533 9.4697C11.9772 9.89921 11.8004 10.3041 11.5377 10.6507C10.6919 11.563 9.72938 11.2982 8.79897 11.0039C7.86565 10.6419 6.88274 10.4153 5.88524 10.327C5.42802 10.3229 4.97624 10.4272 4.56636 10.6317C4.15649 10.8362 3.8 11.1351 3.52567 11.5042C2.41112 12.7821 1.57717 14.2835 1.07861 15.9097C0.847172 16.5582 0.751025 17.2479 0.796196 17.9357C0.841367 18.6234 1.02688 19.2943 1.3411 19.9063C2.33276 21.4071 3.90775 22.1988 4.92567 23.5496C5.229 23.9557 5.42733 24.4325 5.509 24.9328C5.56524 25.2504 5.54988 25.5767 5.46404 25.8875C5.3782 26.1982 5.2241 26.4855 5.01316 26.728C4.10706 27.6815 3.13582 28.5624 2.09943 29.3707C1.09626 30.1659 0.379087 31.271 0.057779 32.5167C-0.0725731 33.2615 0.0187732 34.0286 0.320277 34.721C0.90069 35.8128 1.64735 36.8046 2.53693 37.661C3.44692 38.8774 4.41817 40.0428 5.45066 41.1572C6.14191 41.8458 7.02565 42.3049 7.98523 42.4815C9.69261 42.3148 11.3797 41.9797 13.0223 41.4809C13.3631 41.3665 13.7315 41.3665 14.0723 41.4809C14.2559 41.584 14.4142 41.7275 14.5353 41.9007C14.6565 42.0739 14.7375 42.2724 14.7723 42.4815C14.8714 43.9648 15.1748 45.4303 15.6764 46.8312C16.2918 47.8465 17.2223 48.6323 18.3247 49.0649C19.6372 49.624 21.0197 50.0066 22.4314 50.2126M17.8873 41.0395C17.4657 40.0723 16.7245 39.2828 15.7902 38.8058C14.8958 38.3356 13.8746 38.1704 12.8794 38.3349C11.421 38.5409 9.96272 39.5121 8.50731 39.3944C8.12815 39.3944 7.74898 38.9529 7.34065 38.5409C6.32274 37.5403 5.36316 36.219 4.809 35.6009C4.31725 35.1248 3.85918 34.6144 3.43817 34.0736C3.20484 33.7204 2.94234 33.3672 3.02984 33.0141C3.24859 32.1812 3.75609 31.4485 4.459 30.957C5.52066 30.183 6.50066 29.2972 7.37273 28.3113C7.80688 27.7714 8.11384 27.1392 8.27046 26.4621C8.42709 25.7851 8.42929 25.0811 8.27689 24.4031C8.1219 23.475 7.72985 22.6037 7.1394 21.8751C5.88524 20.6685 4.34234 19.9622 3.38276 18.526C2.94526 17.908 3.20776 16.878 3.55776 15.8774C3.91067 14.8091 4.45317 13.8173 5.159 12.9403C5.62566 12.44 6.20899 12.5872 6.82149 12.7343C7.84231 13.0953 8.89036 13.3406 9.96563 13.47C10.5443 13.4992 11.1217 13.392 11.6523 13.1571C12.1828 12.9221 12.6518 12.5658 13.0223 12.1163C13.4356 11.6332 13.7387 11.0644 13.91 10.4501C14.0814 9.83574 14.1169 9.19092 14.0139 8.56123C13.7018 7.28911 13.5162 5.98877 13.4598 4.67949C13.4935 4.34037 13.6206 4.01754 13.8268 3.74759C14.033 3.47763 14.31 3.27135 14.6264 3.15211C15.9124 2.67244 17.2378 2.30806 18.5872 2.06322C18.9116 1.97152 19.2546 1.97152 19.5789 2.06322C19.6227 2.58412 19.7831 3.08442 20.0456 3.53469C20.3752 4.35283 20.8914 5.07679 21.5593 5.65066C22.2447 6.25691 23.0993 6.6336 24.0064 6.73955C25.1165 6.92826 26.2565 6.69777 27.2089 6.0921C28.7839 5.12093 29.688 3.00496 31.6101 2.47523C31.9601 2.47523 32.6017 2.71067 33.2697 3.00496C33.9891 3.3228 34.6784 3.69557 35.3376 4.12328C35.9792 4.56472 36.2709 4.88844 36.2126 5.32988C35.8937 6.36384 35.4562 7.34285 34.9001 8.26693C34.5258 8.96149 34.3298 9.73949 34.3298 10.5301C34.3298 11.3206 34.5258 12.0986 34.9001 12.7932C35.3463 13.5583 35.9646 14.1999 36.7084 14.6767C37.7292 15.3035 38.8696 15.7126 40.0567 15.8803L41.9233 16.204L42.3608 18.6437L42.6496 20.848C42.6788 21.26 42.6788 21.672 42.6496 22.084C42.6326 22.2895 42.5729 22.489 42.4746 22.6697L40.8413 23.2583C39.7732 23.7412 38.7901 24.3962 37.9305 25.1977C37.3008 25.7989 36.7779 26.5046 36.3846 27.2842C36.1188 27.8956 35.9815 28.5559 35.9815 29.2236C35.9815 29.8912 36.1188 30.5516 36.3846 31.163C36.9884 32.5462 37.7846 33.8293 38.7471 34.9859L39.5054 36.163L38.2805 37.455C37.8138 37.8964 37.2305 38.5733 36.5888 39.1295C35.9471 39.6887 35.9471 39.7475 35.568 39.7475C34.8463 39.6654 34.1513 39.4243 33.5322 39.0412C32.7256 38.6658 31.8532 38.4557 30.9655 38.4232C29.9068 38.4556 28.883 38.8352 28.0547 39.5121C27.3372 40.0477 26.7714 40.7687 26.4214 41.5986C26.013 43.0269 25.6932 44.4768 25.4618 45.9483C25.3121 46.366 25.1267 46.7697 24.9076 47.1549L22.8951 46.7723C21.997 46.6789 21.1095 46.5016 20.2439 46.2426C19.6681 46.1378 19.1503 45.8237 18.7885 45.3597C18.5279 44.695 18.3896 43.9879 18.3802 43.2732C18.3117 42.5101 18.1479 41.7592 17.8873 41.0395ZM45.995 36.0747C47.0719 36.8195 47.9444 37.8273 48.5309 39.0042C49.1174 40.1812 49.3989 41.4888 49.3491 42.8052C49.3491 43.8088 49.1712 44.8064 48.8241 45.7482C48.4118 47.1159 47.5202 48.2854 46.3158 49.0384C45.0181 49.8172 43.5078 50.1574 42.005 50.0096C41.293 50.0142 40.5928 49.8254 39.978 49.463C39.3632 49.1005 38.8564 48.5777 38.5109 47.9495C38.4147 47.7055 38.2287 47.5086 37.9918 47.4001C37.7548 47.2916 37.4855 47.2799 37.2402 47.3675C36.9949 47.4551 36.7927 47.6351 36.6762 47.8698C36.5596 48.1045 36.5377 48.3757 36.6151 48.6264C36.9217 49.4894 37.4239 50.2683 38.0817 50.9009C38.7395 51.5334 39.5347 52.0022 40.4038 52.2697C42.8793 53.1053 45.5774 52.958 47.9491 51.8577C49.5728 50.9531 50.8606 49.54 51.6183 47.8318C52.364 46.1976 52.666 44.3928 52.4933 42.6022C52.3303 40.851 51.7033 39.1764 50.6779 37.754C49.6526 36.3315 48.2668 35.2138 46.6658 34.5179C46.5632 34.4467 46.4466 34.3983 46.324 34.3762C46.2013 34.354 46.0754 34.3585 45.9546 34.3894C45.8338 34.4204 45.721 34.4769 45.6236 34.5554C45.5263 34.6339 45.4467 34.7324 45.3901 34.8445C45.3335 34.9565 45.3013 35.0794 45.2957 35.205C45.29 35.3306 45.311 35.456 45.3572 35.5727C45.4034 35.6895 45.4738 35.7949 45.5637 35.882C45.6536 35.9691 45.7608 36.0358 45.8783 36.0777L45.995 36.0747Z" fill="black" />
                            <path d="M64.8171 47.1842C66.1399 46.5728 67.4067 45.845 68.603 45.0094C69.1096 44.4623 69.4941 43.812 69.7306 43.1023C69.967 42.3926 70.05 41.64 69.9738 40.8952C69.5776 38.0597 68.583 35.343 67.0571 32.9287C66.6395 32.1555 65.9873 31.5377 65.1963 31.1658C64.112 30.7369 62.9443 30.5657 61.7838 30.6655C61.2278 30.7502 60.66 30.7039 60.1247 30.5303C59.5894 30.3567 59.1011 30.0605 58.698 29.6649C58.5293 29.4707 58.4073 29.2398 58.3415 28.9902C58.2757 28.7406 58.268 28.479 58.3189 28.2258C58.6008 26.9663 58.8147 25.693 58.9605 24.4059C58.9716 23.5142 58.7712 22.6328 58.3759 21.8352C57.9807 21.0376 57.4021 20.3469 56.6885 19.8208C54.6954 18.5301 52.4104 17.7707 50.0472 17.6136C49.9322 17.5971 49.815 17.6038 49.7026 17.6335C49.5901 17.6632 49.4847 17.7151 49.3924 17.7864C49.3001 17.8576 49.2227 17.9467 49.1649 18.0484C49.1071 18.1501 49.0699 18.2624 49.0556 18.3788C49.0392 18.4949 49.0459 18.6131 49.0753 18.7265C49.1047 18.84 49.1562 18.9464 49.2268 19.0395C49.2974 19.1327 49.3857 19.2107 49.4865 19.2691C49.5873 19.3274 49.6986 19.3649 49.8139 19.3794C51.8332 19.5429 53.7782 20.2214 55.4664 21.3511C55.9088 21.698 56.2653 22.1439 56.5078 22.6536C56.7502 23.1633 56.872 23.7229 56.8635 24.2882C56.8635 25.7008 56.0468 27.2282 56.076 28.6379C56.076 29.5355 56.3968 30.4036 56.9801 31.0805C57.4643 31.7103 58.1089 32.1988 58.8468 32.4902C59.9084 32.8786 61.0343 33.067 62.1659 33.0493C63.1867 33.0493 64.2047 33.0493 64.8171 34.1353C65.873 35.9481 66.6313 37.9258 67.0601 39.9858C67.2351 41.1041 67.2934 42.1901 66.6226 42.9229C65.9517 43.6586 63.2713 44.3943 62.0201 45.4244C61.3894 45.9137 60.9462 46.6084 60.7659 47.3903C60.6378 47.9497 60.6423 48.5319 60.7792 49.0893C60.916 49.6467 61.1813 50.1636 61.5534 50.5981C62.1523 51.2612 62.8134 51.8576 63.5367 52.3874C63.9422 52.6817 64.3797 52.9171 64.4963 53.2997L63.8255 54.8594C63.0283 56.2053 62.164 57.5081 61.2326 58.7677C60.7728 59.4383 60.2231 60.0415 59.5993 60.5599L58.6105 60.2362C57.1541 59.3533 55.6394 58.5891 54.0664 57.9436C53.5071 57.7663 52.9152 57.7192 52.3353 57.8058C51.7554 57.8925 51.2024 58.1107 50.7181 58.4439C49.9539 58.9619 49.3472 59.6829 48.9681 60.5305C48.5515 61.7897 48.2679 63.0899 48.1223 64.4093C48.1223 65.0861 47.9181 65.7336 47.221 65.9396C46.4627 66.1397 45.6956 66.2869 44.9198 66.381C43.6656 66.5282 42.3852 66.5576 41.1048 66.6753L39.5006 66.9108C39.0838 66.3247 38.7774 65.6663 38.5965 64.9684C38.2568 63.806 37.7571 62.6975 37.1119 61.6753C36.7494 61.1538 36.2869 60.7109 35.7519 60.3727C35.2169 60.0345 34.6202 59.808 33.9969 59.7064C33.1072 59.5361 32.1864 59.6927 31.4011 60.1479C30.4636 60.7853 29.6623 61.6059 29.0445 62.5611C28.4903 63.2645 27.9945 64.0855 26.8861 63.647C26.2984 63.3807 25.7336 63.0657 25.1974 62.7053C23.7712 61.7341 22.4878 60.5305 21.0908 59.5887C20.6824 59.3239 20.0116 58.9707 19.4574 58.5881C19.2452 58.4576 19.0584 58.289 18.9062 58.0908C18.6437 57.4728 19.1979 56.6193 19.752 55.7982C20.3149 54.9095 21.047 54.1384 21.9074 53.5322C22.0504 53.4496 22.1707 53.3325 22.2576 53.1913C22.3444 53.0501 22.3951 52.8893 22.4049 52.7234C22.4147 52.5575 22.3835 52.3918 22.3139 52.2411C22.2444 52.0905 22.1388 51.9597 22.0066 51.8606C21.8521 51.7404 21.6651 51.6704 21.4702 51.6599C21.2754 51.6494 21.0821 51.6988 20.9158 51.8017C19.5254 52.7109 18.3681 53.9392 17.5383 55.3862C17.15 55.9094 16.8851 56.5151 16.7638 57.1571C16.6425 57.7992 16.668 58.4607 16.8383 59.0914C17.0599 59.5387 17.3779 59.9301 17.7716 60.2391C18.3841 60.7659 19.2562 61.2368 19.7228 61.587C21.1228 62.5582 22.3449 63.8236 23.742 64.7948C24.4012 65.2892 25.1109 65.7002 25.8711 66.0279C26.6616 66.4281 27.5774 66.5135 28.432 66.2633C29.6103 65.8278 30.6282 65.0361 31.3457 63.9972C31.929 63.2321 32.454 62.2933 33.5594 62.4993C33.8329 62.5511 34.0929 62.6587 34.3236 62.8156C34.5544 62.9725 34.7509 63.1753 34.9011 63.4116C35.4465 64.4328 35.8665 65.5187 36.1523 66.643C36.4644 67.6936 37.0244 68.653 37.7827 69.4387C38.1362 69.7466 38.5731 69.9403 39.0369 69.995C39.8186 70.0185 40.5954 69.9596 41.3673 69.8184C42.706 69.8184 43.9864 69.8184 45.2989 69.6124C46.2809 69.5339 47.2521 69.3769 48.2127 69.1415C49.1927 68.9178 50.056 68.3322 50.6306 67.4964C51.2285 66.4105 51.5872 65.2068 51.6806 63.9678C51.8264 62.9407 51.8556 61.8224 52.7568 61.2073C53.0776 60.9719 53.4568 61.2073 53.8651 61.3545C55.2039 61.9137 56.5718 62.9702 57.5343 63.3822C58.2343 63.7 58.9809 63.9001 59.748 63.9708C60.2742 63.9617 60.7882 63.8092 61.2355 63.5293C62.2768 62.77 63.1897 61.8489 63.9422 60.7924C64.9921 59.3239 65.9255 57.6199 66.6226 56.4457C67.13 55.6364 67.4859 54.7388 67.6725 53.797C67.7845 53.1982 67.7341 52.58 67.5267 52.0077C67.3936 51.6414 67.196 51.3025 66.9434 51.0071C66.3268 50.3187 65.6317 49.7062 64.8726 49.1825C64.5616 48.9873 64.2781 48.7507 64.0297 48.4791C63.9543 48.4138 63.8943 48.3324 63.8539 48.2408C63.8136 48.1491 63.7939 48.0496 63.7963 47.9494C63.8838 47.5668 64.3797 47.4197 64.8171 47.1842Z" fill="black" />
                        </svg>
                    </div>
                </div>

                <div class="card-expanded-view">
                    <div class="s-left">
                        <div class="s-item" onclick="showSPanel('username', this)">
                            <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.75 17.0833C0.75 15.8456 1.24167 14.6587 2.11683 13.7835C2.992 12.9083 4.17899 12.4167 5.41667 12.4167H14.75C15.9877 12.4167 17.1747 12.9083 18.0498 13.7835C18.925 14.6587 19.4167 15.8456 19.4167 17.0833C19.4167 17.7022 19.1708 18.2957 18.7332 18.7332C18.2957 19.1708 17.7022 19.4167 17.0833 19.4167H3.08333C2.46449 19.4167 1.871 19.1708 1.43342 18.7332C0.995833 18.2957 0.75 17.7022 0.75 17.0833Z" stroke="black" stroke-width="1.5" stroke-linejoin="round" />
                                <path d="M10.0833 7.75C12.0162 7.75 13.5833 6.183 13.5833 4.25C13.5833 2.317 12.0162 0.75 10.0833 0.75C8.15026 0.75 6.58325 2.317 6.58325 4.25C6.58325 6.183 8.15026 7.75 10.0833 7.75Z" stroke="black" stroke-width="1.5" />
                            </svg>
                            <span>Change User Name</span><i class="fa-solid fa-chevron-right s-arr"></i>
                        </div>
                        <div class="s-item" onclick="showSPanel('password', this)">
                            <i class="fa-solid fa-lock s-lock"></i>
                            <span>Change Password</span>
                            <i class="fa-solid fa-chevron-right s-arr"></i>
                        </div>
                        <div class="s-item" onclick="showSPanel('email', this)">
                            <i class="fa-regular fa-envelope"></i><span>Change Email</span><i class="fa-solid fa-chevron-right s-arr"></i>
                        </div>
                        <div class="s-item" onclick="showSPanel('address', this)">
                            <i class="fa-solid fa-location-dot"></i><span>Saved Addresses</span><i class="fa-solid fa-chevron-right s-arr"></i>
                        </div>
                        <div class="s-item" onclick="showSPanel('phone', this)">
                            <i class="fa-solid fa-phone"></i><span>Phone Number</span><i class="fa-solid fa-chevron-right s-arr"></i>
                        </div>
                        <div class="s-item" onclick="showSPanel('payment', this)">
                            <i class="fa-regular fa-credit-card"></i><span>Payment Method</span><i class="fa-solid fa-chevron-right s-arr"></i>
                        </div>
                        <div class="s-item s-logout" onclick="showSPanel('logout', this)">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i><span>Log Out</span><i class="fa-solid fa-chevron-right s-arr"></i>
                        </div>
                    </div>

                    <div class="s-right">
                        <!-- Mobile back button (hidden on desktop via CSS) -->
                        <button class="s-back-btn" id="s-mobile-back" onclick="closeMobilePanel()" style="display:none;">
                            <i class="fa-solid fa-chevron-left"></i> Settings
                        </button>
                        <button class="s-close-btn" onclick="collapseSettings()" aria-label="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <div class="s-panel s-active" id="s-panel-welcome">
                            <h2 class="settings-header">Settings</h2>
                            <p class="settings-sub">Manage your account, security, and preferences.</p>
                            <div class="s-welcome-icon"><img src="../picture/ChatGPT Image May 13, 2026, 01_30_04 PM 1.png" alt=""></div>
                            <h2>Welcome to your settings</h2>
                            <p>Update your account information, manage your preferences, and keep your account secure.</p>
                        </div>

                        <div class="s-panel" id="s-panel-username">
                            <h2>Change User Name</h2>
                            <p>Update your display name.</p>
                            <div class="s-fg"><label>New First Name</label><input type="text" id="inp-firstname" placeholder="Enter new First Name" value="<?= htmlspecialchars($user['firstname']) ?>" /></div>
                            <div class="s-fg"><label>New Last Name</label><input type="text" id="inp-lastname" placeholder="Enter new Last Name" value="<?= htmlspecialchars($user['lastname']) ?>" /></div>
                            <div class="s-msg" id="msg-username"></div>
                            <button class="s-save-btn" onclick="saveField('name')">Save Changes</button>
                        </div>

                        <div class="s-panel" id="s-panel-password">
                            <h2>Change Password</h2>
                            <p>Keep your account secure with a strong password.</p>
                            <div class="s-fg"><label>Current Password</label>
                                <div style="position:relative;">
                                    <input type="password" id="inp-current-password" placeholder="Current password" style="width:100%;padding-right:40px;" />
                                    <img src="../picture/eye-close.png" alt="Toggle" class="password-eye" data-input="inp-current-password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;width:15px;height:15px;">
                                </div>
                            </div>
                            <div class="s-fg"><label>New Password</label>
                                <div style="position:relative;">
                                    <input type="password" id="inp-new-password" placeholder="New password" style="width:100%;padding-right:40px;" />
                                    <img src="../picture/eye-close.png" alt="Toggle" class="password-eye" data-input="inp-new-password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;width:15px;height:15px;">
                                </div>
                            </div>
                            <div class="s-fg"><label>Confirm Password</label>
                                <div style="position:relative;">
                                    <input type="password" id="inp-confirm-password" placeholder="Confirm new password" style="width:100%;padding-right:40px;" />
                                    <img src="../picture/eye-close.png" alt="Toggle" class="password-eye" data-input="inp-confirm-password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;width:15px;height:15px;">
                                </div>
                            </div>
                            <div class="password-rules" id="password-rules-panel" style="font-size:0.85rem;margin:12px 0;display:none;">
                                <p id="s-length" class="invalid" style="margin:4px 0;">✘ 8–25 characters</p15                                <p id="s-uppercase" class="invalid" style="margin:4px 0;">✘ At least 1 uppercase letter</p>
                                <p id="s-lowercase" class="invalid" style="margin:4px 0;">✘ At least 1 lowercase letter</p>
                                <p id="s-number" class="invalid" style="margin:4px 0;">✘ At least 1 number</p>
                            </div>
                            <div class="s-msg" id="msg-password"></div>
                            <button class="s-save-btn" onclick="savePassword()">Save Changes</button>
                        </div>

                        <div class="s-panel" id="s-panel-email">
                            <h2>Change Email</h2>
                            <p>Update the email linked to your account.</p>
                            <div class="s-fg"><label>New Email</label><input type="email" id="inp-new-email" placeholder="Enter new email" /></div>
                            <div class="s-msg" id="msg-email"></div>
                            <button class="s-save-btn" onclick="saveEmail()">Send OTP</button>
                        </div>

                        <div class="s-panel" id="s-panel-address">
                            <h2>Saved Addresses</h2>
                            <p>Manage your saved delivery addresses.</p>
                            <div class="s-fg"><label>Address</label><input type="text" id="inp-address" placeholder="Enter your address" value="<?= htmlspecialchars($address ?? '') ?>" /></div>
                            <div class="s-msg" id="msg-address"></div>
                            <button class="s-save-btn" onclick="saveField('address')">Save Address</button>
                        </div>

                        <div class="s-panel" id="s-panel-phone">
                            <h2>Phone Number</h2>
                            <p>Update the mobile number linked to your account.</p>
                            <div class="s-fg"><label>Phone Number</label><input type="text" id="inp-phone" placeholder="09XXXXXXXXX" value="<?= htmlspecialchars($phone ?? '') ?>" maxlength="11" /></div>
                            <div class="s-msg" id="msg-phone"></div>
                            <button class="s-save-btn" onclick="saveField('phone')">Save Number</button>
                        </div>

                        <div class="s-panel" id="s-panel-payment">
                            <h2>Payment Method</h2>
                            <p>Manage your saved payment methods.</p>
                            <div class="s-fg"><label>Card Number</label><input type="text" placeholder="XXXX XXXX XXXX XXXX" /></div>
                            <div class="s-frow">
                                <div class="s-fg"><label>Expiry</label><input type="text" placeholder="MM/YY" /></div>
                                <div class="s-fg"><label>CVV</label><input type="text" placeholder="CVV" /></div>
                            </div>
                            <button class="s-save-btn">Save Card</button>
                        </div>

                        <div class="s-panel" id="s-panel-logout">
                            <div class="s-welcome-icon s-logout-icon"><img src="../picture/ChatGPT Image May 16, 2026, 09_14_38 PM 1.png" alt=""></div>
                            <h2>Log Out</h2>
                            <p>Are you sure you want to log out of your account?</p>
                            <button class="s-save-btn s-logout-btn" onclick="window.location.href='../logout.php'">Yes, Log Out</button>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- AVATAR CHOICE MODAL -->
    <div id="avatarModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:16px;padding:28px 32px;min-width:260px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.25);">
            <h3 style="margin:0 0 6px;font-size:1.1rem;">Change Profile Photo</h3>
            <p style="margin:0 0 20px;font-size:.85rem;color:#666;">Choose how you'd like to upload</p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <button onclick="triggerCamera()" style="display:flex;align-items:center;gap:10px;padding:12px 18px;border:1.5px solid #ddd;border-radius:10px;background:#fff;cursor:pointer;font-size:.95rem;">
                    <i class="fa-solid fa-camera" style="font-size:1.1rem;color:#555;"></i> Take a Photo
                </button>
                <button onclick="triggerFilePicker()" style="display:flex;align-items:center;gap:10px;padding:12px 18px;border:1.5px solid #ddd;border-radius:10px;background:#fff;cursor:pointer;font-size:.95rem;">
                    <i class="fa-solid fa-image" style="font-size:1.1rem;color:#555;"></i> Choose from Gallery
                </button>
                <button onclick="closeAvatarModal()" style="padding:10px;border:none;background:none;cursor:pointer;color:#888;font-size:.9rem;margin-top:4px;">Cancel</button>
            </div>
        </div>
    </div>

    <script src="../scr/account.js"></script>

</body>

</html>