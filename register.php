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

        <form action="register.php" method="post" id="registerForm">
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
                    <!-- Hidden; auto-checked by JS when user accepts via overlay -->
                    <input type="checkbox" id="Remember" name="remember" style="display:none;">
                    <span>
                        * I agree to the
                        <a href="#" id="openTcLink">BoyCold Cafe Terms</a>
                        and have read the
                        <a href="#" id="openTcLink2">BoyCold Cafe Privacy</a> Statement.
                    </span>
                </label><br>
                <button type="button" id="registerBtn">Register Account</button>
                <p>You have account Already? <a href="login.php">Log In</a></p>
            </div>
        </form>
    </div>

    <!-- ── Terms & Conditions  ── -->
    <div id="tcOverlay" role="dialog" aria-modal="true" aria-labelledby="tcTitle">
        <div class="tc-modal">

            <div class="tc-header">
                <h2 id="tcTitle">☕ Terms &amp; Conditions</h2>
                <button class="tc-close" id="tcCloseBtn" aria-label="Close">&times;</button>
            </div>

            <div class="tc-body" id="tcBody">
                <h3>Welcome to Boycold Cafe. By accessing our website, mobile app, or visiting our café, you agree to the following Terms & Conditions. Please read them carefully before using our services.</h3>

                <h3>1. Acceptance of Terms</h3>
                <p>By using Boycold Cafe’s services, placing an order, or accessing our website or app, you agree to comply with these Terms & Conditions and all applicable laws and regulations.</p>

                <h3>2. ORDERS & PAYMENTS</h3>
                <p>All orders are subject to availability and confirmation. Prices displayed on our menu, website, or app may change without prior notice. Payments must be completed before orders are processed unless stated otherwise. Boycold Cafe reserves the right to refuse or cancel orders in cases of suspected fraud, incorrect pricing, or unavailable items.</p>

                <h3>3. REFUNDS & CANCELLATIONS</h3>
                <p>Refunds or cancellations may only be granted under specific circumstances, such as incorrect orders, unavailable items, or quality concerns. Requests must be reported within a reasonable period after receiving the order. Approved refunds will be processed through the original payment method whenever possible.</p>

                <h3>4. CUSTOMER RESPONSIBILITIES</h3>
                <p>Customers are expected to provide accurate information when placing orders, including contact details and delivery information. Boycold Cafe is not responsible for delays or issues caused by incorrect customer information. Customers must also use our website, app, and services respectfully and lawfully.</p>

                <br><br>
                <h3>PRIVACY AND SAFETY</h3>

                <h3>At Boycold Cafe, your trust means everything to us. We are committed to protecting your personal information and creating a safe, respectful experience—both online and in our cafés.</h3>
                <br><br>

                <h3>1. INFORMATION WE COLLECT</h3>
                <p>We collect information you provide to us, such as your name, email address, phone number, and order details when you place an order, sign up for our newsletter, or interact with our website, app, or in-store services. We may also collect information automatically, including device and browsing data, to help us improve your experience.   </p>
                
                <h3>2. HOW WE USE YOUR INFORMATION</h3>
                <p>We use your information to process orders, personalize your experience, provide customer support, send updates and promotions (with your consent), and improve our products and services. We may also use your data for analytics to understand how our customers interact with Boycold Cafe.</p>

                <h3>3. HOW WE PROTECT YOUR INFORMATION</h3>
                <p>We take your privacy seriously. We use industry-standard security measures to protect your information from unauthorized access, disclosure, alteration, or destruction. Our systems are regularly monitored, and access to personal data is limited to authorized team members only.</p>

                <h3>4. SHARING YOUR INFORMATION</h3>
            <p>We do not sell your personal information. We may share your information with trusted service providers who help us operate our business—such as payment processors, delivery partners, and marketing platforms—but only to the extent necessary and under strict confidentiality obligations.</p>            
        </div>

            <p class="tc-scroll-hint" id="tcScrollHint">↓ Scroll down to read all terms</p>

            <div class="tc-footer">
                <label class="tc-accept-label">
                    <input type="checkbox" id="tcAcceptCheck">
                    I have read and agree to the BoyCold Café Terms &amp; Conditions and Privacy Statement.
                </label>
                <div class="tc-actions">
                    <button type="button" class="tc-btn-cancel" id="tcCancelBtn">Cancel</button>
                    <button type="button" class="tc-btn-confirm" id="tcConfirmBtn" disabled>I agree</button>
                </div>
            </div>

        </div>
    </div>

    <script src="scr/script.js"></script>
</body>
</html>