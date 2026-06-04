<?php
session_start();
require_once './config/db_config.php';
require_once './config/mailer.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['Firstname'] ?? '');
    $lastname  = trim($_POST['Lastname']  ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = $_POST['password'] ?? '';

    if (!$firstname || !$lastname || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8 || strlen($password) > 25
           || !preg_match('/[A-Z]/', $password)
           || !preg_match('/[a-z]/', $password)
           || !preg_match('/[0-9]/', $password)) {
        $error = 'Password does not meet the requirements.';
    } else {
        $chk = $connect->prepare("SELECT id FROM users WHERE email=? AND is_verified=1");
        $chk->bind_param("s", $email); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'This email is already registered. Please log in.';
        } else {
            $exp = $connect->prepare("UPDATE otp SET status='expired' WHERE email=? AND type='register' AND status='pending'");
            $exp->bind_param("s", $email); $exp->execute();

            $otp        = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            $ip         = $_SERVER['REMOTE_ADDR'];

            $ins = $connect->prepare(
                "INSERT INTO otp (firstname, lastname, email, password, otp, type, status, otp_sent, ip)
                 VALUES (?, ?, ?, ?, ?, 'register', 'pending', NOW(), ?)"
            );
            $ins->bind_param("ssssss", $firstname, $lastname, $email, $hashedPass, $otp, $ip);

            if ($ins->execute()) {
                if (sendOTPEmail($email, "$firstname $lastname", $otp, 'register')) {
                    $_SESSION['otp_email'] = $email;
                    $_SESSION['otp_type']  = 'register';
                    header('Location: createotp.php');
                    exit;
                } else {
                    $error = 'Could not send OTP email. Please try again.';
                }
            } else {
                $error = 'Database error. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoyCold Café</title>
    <link rel="stylesheet" href="styles/register.css">
    <link rel="icon" type="image/png" href="picture/icon.png">
</head>
<header>
    <img src="picture/LOGO.png" alt="BoyCold CAFE Logo" width="50px">
</header>
<body>
    <div class="pic1">
        <img src="picture/Mask group.png">
    </div>

    <div class="hero-banner">
        <img src="picture/Mask group.png" alt="BoyCold Café hero">
    </div>

    <div id="registerSection">
        <h1 class="font">Create an Account</h1>
        <p class="p1">Please create an account for continue using our app</p>
        <p class="p2">* Indicates a required field</p>

        <?php if ($error): ?>
            <p class="error-msg" style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="register.php" method="post">
            <h4>Personal Information</h4>
            <input type="text" name="Firstname" id="Firstname" placeholder="*First Name" required><br><br>
            <input type="text" name="Lastname"  id="Lastname"  placeholder="*Last Name"  required><br><br>

            <h4>Account Security</h4>
            <input type="email" name="email" id="email" placeholder="*Email" required><br><br>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="*Password" required>
                <img src="picture/eye-close.png" alt="Hide Icon" class="hide-icon">
            </div><br>

            <div class="password-rules">
                <p id="length"    class="invalid">✘ 8–25 characters</p>
                <p id="uppercase" class="invalid">✘ At least 1 uppercase letter</p>
                <p id="lowercase" class="invalid">✘ At least 1 lowercase letter</p>
                <p id="number"    class="invalid">✘ At least 1 number</p>
            </div>

            <div class="terms">
                <h4>TERMS AND CONDITION</h4>
                <label>
                    <input type="checkbox" id="Remember" name="remember" required>
                    <span>
                        * I agree to the <a href="#">BoyCold Cafe Terms</a> and have
                        <br>read the <a href="#">BoyCold Cafe Privacy</a> Statement.
                    </span>
                </label><br>
                <button type="submit">Register Account</button>
                <p>You have account Already? <a href="login.php">Log In</a></p>
            </div>
        </form>
    </div>

    <script src="scr/script.js"></script>
</body>
</html>