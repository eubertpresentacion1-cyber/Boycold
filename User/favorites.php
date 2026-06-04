<?php
session_start();
require_once '../config/db_config.php';

// Session guard — redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch fresh user data from DB (same pattern as account.php)
$stmt = $connect->prepare("SELECT Firstname, Lastname, email, avatar FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$fullName  = htmlspecialchars($user['Firstname'] . ' ' . $user['Lastname']);
$userEmail = htmlspecialchars($user['email']);
$avatar    = $user['avatar'] ? htmlspecialchars($user['avatar']) : '';

// Keep session in sync
$_SESSION['user_name']  = $user['Firstname'] . ' ' . $user['Lastname'];
$_SESSION['user_email'] = $user['email'];
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/favorites.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Favorites</title>
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
                <li class="sidebar-nav-only"><a href="#">FAVORITES</a></li>
                <li><a href="../order/cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i> CART
                    </a></li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <a href="account.php" class="sidebar-avatar-link">
                <div class="sidebar-avatar" id="sidebarAvatarWrap">
                    <?php if ($avatar): ?>
                        <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar" style="display:block;">
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon" style="display:none;"></i>
                    <?php else: ?>
                        <img id="sidebarAvatarImg" src="" alt="avatar" style="display:none;">
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon"></i>
                    <?php endif; ?>
                </div>
            </a>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= $fullName ?></span>
                <span class="sidebar-user-email"><?= $userEmail ?></span>
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
                <li><a href="menu.php" class="active">MENU</a></li>
                <li><a href="../order/status.php">ORDERS</a></li>
                <li><a href="#">FAVORITES</a></li>
            </ul>

        </div>

        <!-- CENTER: logo -->
        <div class="logo">
            <img src="../picture/Boycold Logo 2.png" alt="BoyCold logo">
        </div>

        <div class="nav-right-group">
            <div class="nav-search" id="navSearch">
                <i class="fa-solid fa-magnifying-glass" id="searchIconBtn" onclick="toggleSearch()"></i>
                <input type="text" placeholder="Search coffee and more">
            </div>
            <a href="cart.php" class="cart-link">
                <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i>
            </a>
            <div class="avatar-dropdown-wrap">
                <div class="sidebar-avatar" id="navAvatarBtn" onclick="toggleAvatarDropdown()">
                    <?php if ($avatar): ?>
                        <img id="navAvatarImg" src="<?= $avatar ?>" alt="avatar" style="display:block;">
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
    <header>
        <div class="background"></div>
        <section class="fav-section">
            <div class="fav-content">

                <div class="fav-header">
                    <div class="fav-header-left">
                        <h1>My Favorites</h1>
                        <p>Your go-to drinks, all in one place</p>
                    </div>
                    <div class="fav-header-right">
                        <select class="fav-sort" id="favSort">
                            <option value="default">Sort by</option>
                            <option value="price-asc">Price: Low to High</option>
                            <option value="price-desc">Price: High to Low</option>
                            <option value="name-asc">Name: A–Z</option>
                        </select>
                    </div>
                </div>

                <div class="fav-count" id="favCount">
                    <i class="fa-solid fa-heart" style="color:#e53935;"></i>
                    <span id="favCountNum">0</span> Items
                </div>

                <!-- EMPTY STATE -->
                <div class="fav-empty" id="favEmpty" style="display:none;">
                    <div class="fav-empty-icon">
                        <i class="fa-solid fa-heart"></i>
                    </div>
                    <p class="fav-empty-title">No favorites yet</p>
                    <p class="fav-empty-desc">Heart any item on the menu and it will appear here.</p>
                    <a href="menu.php" class="fav-empty-cta">Browse Menu</a>
                </div>

                <!-- GRID -->
                <div class="product-grid" id="favGrid"></div>

            </div>
        </section>
    </header>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="/picture/icon2.png" alt="BoyCold logo">
                <h1>BOYCOLD CAFE</h1>
                <p>© 2024 BoyCold Cafe. All rights reserved.</p>
            </div>
            <div class="footer-links">
                <ul>
                    <li><a href="#">Contact Information</a></li>
                    <li><a href="#">Customer Links</a></li>
                    <li><a href="#">Company Information</a></li>
                    <li><a href="#">Legal Links</a></li>
                    <li><a href="#">Social Media Links</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <!-- CART TOAST -->
    <div id="cartToast" style="
        display:none; position:fixed; bottom:28px; left:50%; transform:translateX(-50%);
        background:#1e1e1e; color:#fff; padding:12px 24px; border-radius:30px;
        font-family:'Afacad',sans-serif; font-size:15px; font-weight:600;
        box-shadow:0 4px 20px rgba(0,0,0,0.35); z-index:9999; white-space:nowrap;
        align-items:center; gap:10px;">
        <i class="fa-solid fa-check" style="color:#6F4E37;"></i>
        <span id="cartToastMsg">Added to cart!</span>
    </div>

    <script src="/scr/favorites.js"></script>
</body>

</html>