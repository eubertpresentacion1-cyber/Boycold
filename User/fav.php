<?php
session_start();
require_once 'config/db_config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];
$stmt = $connect->prepare("SELECT firstname, lastname, email, avatar FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$email    = htmlspecialchars($user['email']);
$avatar   = $user['avatar'] ? htmlspecialchars($user['avatar']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/favorites.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>BoyCold - Favorites</title>
</head>
<body>
    <!-- same sidebar and nav HTML as menu.php (copy from menu.php) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    <div class="sidebar" id="sidebar">...</div>
    <nav id="mainNav">...</nav>

    <main>
        <div class="favorites-header">
            <h1>Your Favorites</h1>
            <p>Items you've loved</p>
        </div>
        <div class="product-grid" id="favGrid"></div>
        <div id="favEmpty" style="display:none; text-align:center; padding:60px;">No favorites yet.</div>
    </main>

    <footer>...</footer>

    <script src="favorites.js"></script>
</body>
</html>