<?php
session_start();
require_once './config/db_config.php';

$error = '';
$verified = isset($_GET['verified']);
$reset    = isset($_GET['reset']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $connect->prepare("SELECT id, firstname, lastname, user_name, password FROM users WHERE email=? AND is_verified=1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name']  = $user['user_name'];

            if ($remember) {
                setcookie('remember_email', $email, time() + (86400 * 30), '/');
            } else {
                setcookie('remember_email', '', time() - 3600, '/');
            }

            header('Location: User/home.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$savedEmail = $_COOKIE['remember_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BoyCold Café</title>
    <link rel="stylesheet" href="styles/login.css">
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

    <h1 class="font">Log in Now</h1>
    <h2 class="p1">Please Log in to continue using our app</h2>

    <?php if ($verified): ?>
        <p class="form-message success">
            ✅ Account verified! You can now log in.
        </p>
    <?php endif; ?>

    <?php if ($reset): ?>
        <p class="form-message success">
            ✅ Password reset successfully! Please log in.
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="form-message error">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <label for="email"></label>
        <input type="email" id="email" name="email" placeholder="*Email" value="<?= htmlspecialchars($savedEmail) ?>" required><br><br>

        <label for="password"></label>
        <div class="password-container">
            <input type="password" id="password" name="password" placeholder="*Password" required>
            <img src="picture/eye-close.png" alt="Hide Icon" class="hide-icon">
        </div>

        <div class="remember-row">
            <label class="remember-label" for="Remember">
                <input type="checkbox" id="Remember" name="remember" <?= $savedEmail ? 'checked' : '' ?> required>
                Remember me</label>
            <a href="forgotpass.php" class="forgot">Forgot Password?</a>
        </div>

        <div class="terms">
            <button type="submit">Log In</button>
            <p>Don't have an account? <a href="register.php">Create an Account</a></p>
        </div>
    </form>

    <script>
        const passwordInput = document.getElementById('password');
        const hideIcon = document.querySelector('.hide-icon');
        hideIcon.addEventListener('click', () => {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                hideIcon.src = 'picture/eye-open.png';
            } else {
                passwordInput.type = 'password';
                hideIcon.src = 'picture/eye-close.png';
            }
        });
    </script>
</body>

</html>