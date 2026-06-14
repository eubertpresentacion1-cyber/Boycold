<?php
session_start();
require_once './config/db_config.php';
require_once './config/mailer.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $chk = $connect->prepare("SELECT id, firstname, lastname FROM users WHERE email=? AND is_verified=1");
        $chk->bind_param("s", $email); $chk->execute();
        $user = $chk->get_result()->fetch_assoc();

        if ($user) {
            $exp = $connect->prepare("UPDATE otp SET status='expired' WHERE email=? AND type='reset' AND status='pending'");
            $exp->bind_param("s", $email); $exp->execute();

            $otp      = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $ip       = $_SERVER['REMOTE_ADDR'];
            $fullName = $user['firstname'] . ' ' . $user['lastname'];

            $ins = $connect->prepare("INSERT INTO otp (email, otp, type, status, otp_sent, ip) VALUES (?, ?, 'reset', 'pending', NOW(), ?)");
            $ins->bind_param("sss", $email, $otp, $ip); $ins->execute();
            sendOTPEmail($email, $fullName, $otp, 'reset');
        }

        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_type']  = 'reset';
        header('Location: otp.php?mode=reset');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoyCold Café</title>
    <link rel="stylesheet" href="styles/forgot.css">
    <link rel="icon" type="image/png" href="picture/icon.png">
</head>
<header>
    <img src="picture/LOGO.png" alt="BoyCold CAFE Logo" width="50px">
</header>
<body>
    <div class="pic1">
        <img src="picture/Mask group.png" alt="Sign Up Image" width="690px">
    </div>

    <div class="hero-banner">
        <img src="picture/Mask group.png" alt="BoyCold Café hero">
    </div>
    
    <h1 class="font">Forgot Password?</h1>
    <p class="p1">Just need to confirm your email to send you 
        <br> instructions to reset your password.</p><br>
    <p class="p2">* Indicates a required field</p>

    <?php if ($error): ?>
        <p style="color:red;font-size:14px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="forgotpass.php" method="post">
        <label for="email"></label>
        <input type="email" id="email" name="email" placeholder="*Email" required><br><br>
        <div class="terms">
            <button type="submit">Send OTP Verification</button>
            <p>Don't have an account? <a href="register.php">Create an Account</a></p>
        </div>
    </form>
</body>
</html>