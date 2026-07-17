<?php
session_name('POS_SESSION');
session_start();
require_once __DIR__ . '/../config/db_config.php';

// Check if user is already logged in - redirect to login for PIN verification
if (isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

// Always redirect to signup first
$redirectPage = 'signup.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="auth-css/flashscreen.css">
    <link rel="icon" href="/img/LOGO 2.png">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold Cafe</title>
</head>
<body>
    <div class="flashscreen">
        <img src="/img/ChatGPT Image Jun 23, 2026, 09_22_57 PM 1.png" alt="BoyCold Cafe">
        <div class="middle-part">
            <h1>BoyCold Cafe</h1>
            <p>Every sip, a moment for you.</p>
        </div>
    </div>
    <script>
        setTimeout(() => {
            window.location.href = "<?= $redirectPage ?>";
        }, 4300);
    </script>
</body>
</html>