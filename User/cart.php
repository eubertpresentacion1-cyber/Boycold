<?php
session_start();
require_once '../config/db_config.php';

// Session guard
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Check if the user has any items in their cart (direct DB query)
$stmt = $connect->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($cartItemCount);
$stmt->fetch();
$stmt->close();

// If cart has items, redirect to the detailed cart page (addtocart.php)
if ($cartItemCount > 0) {
    header('Location: addtocart.php');
    exit;
}

// No items – fetch user data for sidebar display
$stmt = $connect->prepare("SELECT firstname, lastname, email, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$email    = htmlspecialchars($user['email']);
$avatar   = $user['avatar'] ?? '';

// Keep session data in sync
if ($avatar) $_SESSION['user_avatar'] = $avatar;
$_SESSION['user_name']  = $user['firstname'] . ' ' . $user['lastname'];
$_SESSION['user_email'] = $user['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="icon" href="/picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Your Cart</title>
</head>
<body>

    <div class="background"></div>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    
    <div class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li class="sidebar-nav-only"><a href="home.php">HOME</a></li>
                <li class="sidebar-nav-only"><a href="menu.php">MENU</a></li>
                <li class="sidebar-nav-only"><a href="favorites.php">FAVORITES</a></li>
                <li class="sidebar-nav-only"><a href="orderstatus.php">ORDERS</a></li>
                <li class="sidebar-nav-only"><a href="stores.php">FIND A STORE</a></li>
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
                <li><a href="favorites.php">FAVORITES</a></li>
                <li><a href="orderstatus.php">ORDERS</a></li>
            </ul>
        </div>
        <div class="logo">
            <img src="/picture/BoyCold Logo 2.png" alt="BoyCold">
        </div>
        <div class="nav-right-group">
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

    <main class="order-main">
        <div class="order-header">
            <h1>Your Cart</h1>
            <p>Review your items before placing your order</p>
        </div>

        <!-- UNIFIED CART BOX -->
        <div class="cart-box">

            <!-- LEFT: CART ITEMS (empty state) -->
            <div class="cart-left">
                <h2 class="panel-title">CART ITEMS</h2>
                <div class="empty-cart-state">
                    <div class="empty-cart-icon-wrap">
                        <i class="fa-solid fa-cart-shopping empty-cart-icon"></i>
                    </div>
                    <h3 class="empty-cart-title">Your cart is empty</h3>
                    <p class="empty-cart-desc">Browse our menu and find your next favorite drink!</p>
                    <a href="menu.php" class="empty-cart-cta">Browse Menu</a>
                </div>
                <div class="continue-shopping">
                    <a href="menu.php" class="continue-btn">
                        <i class="fa-solid fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            </div>

            <!-- RIGHT: ORDER SUMMARY (disabled) -->
            <div class="cart-right">
                <h2 class="panel-title">ORDER SUMMARY</h2>
                <div class="summary-divider"></div>
                <div class="summary-rows">
                    <div class="summary-row"><span>Subtotal</span><span>&#8369;0.00</span></div>
                    <div class="summary-row"><span>Delivery Fee</span><span>&#8369;0.00</span></div>
                    <div class="summary-row"><span>Taxes</span><span>&#8369;0.00</span></div>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-total">
                    <span>TOTAL</span>
                    <span>&#8369;0.00</span>
                </div>
                <button class="checkout-btn" disabled>Proceed to Checkout</button>
                <p class="summary-terms">
                    By placing your order, you agree to our <a href="#">BoyCold Cafe Terms</a>
                </p>
            </div>

        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="/picture/icon2.png" alt="BoyCold logo">
                <h1>BOYCOLD CAFE</h1>
                <p>&copy; 2024 BoyCold Cafe. All rights reserved.</p>
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

    <script>
        // No localStorage check – the PHP already redirects if cart has items.
        // This page is only shown when cart is truly empty.

        const nav = document.getElementById('mainNav');
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const isOpen = sidebar.classList.toggle('open');
            overlay.classList.toggle('open', isOpen);
            nav.classList.toggle('sidebar-open', isOpen);
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('open');
            nav.classList.remove('sidebar-open');
        }
        function toggleAvatarDropdown() {
            const dropdown = document.getElementById('avatarDropdown');
            dropdown.classList.toggle('open');
        }
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('avatarDropdown');
            const btn = document.getElementById('navAvatarBtn');
            if (dropdown && btn && !btn.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
    </script>

</body>
</html>