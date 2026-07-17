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
$stmt = $connect->prepare("SELECT Firstname, Lastname, user_name, email, avatar FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$fullName  = htmlspecialchars($user['Firstname'] . ' ' . $user['Lastname']);
$userEmail = htmlspecialchars($user['email']);
$avatar    = $user['avatar'] ? htmlspecialchars($user['avatar']) : '';
$userName  = $user['user_name'];

// Keep session in sync
$_SESSION['user_name']  = $user['Firstname'] . ' ' . $user['Lastname'];
$_SESSION['user_email'] = $user['email'];

// Set default branch if not set
if (!isset($_SESSION['branch_id'])) {
    $_SESSION['branch_id'] = 1; // Default to Baliuag
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/home.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold Café</title>
</head>

<body>

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR DRAWER -->
    <div class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li><a href="Home.php">HOME</a></li>
                <li><a href="Menu.php">MENU</a></li>
                <li><a href="status.php">ORDER</a></li>
                <li><a href="../store/store.php">STORES</a></li>
                <li class="sidebar-nav-only-not"><a href="status.php">ORDERS</a></li>
                <li class="sidebar-nav-only"><a href="favorites.php">FAVORITES</a></li>
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
                <li><a href="Home.php">HOME</a></li>
                <li><a href="Menu.php">MENU</a></li>
                <li><a href="status.php">ORDERS</a></li>
                <li><a href="favorites.php">FAVORITES</a></li>
            </ul>
        </div>

        <!-- CENTER: logo -->
        <div class="logo">
            <img src="../picture/Boycold Logo 2.png" alt="BoyCold logo">
        </div>

        <!-- RIGHT: avatar with dropdown -->
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

    <header>
        <div class="background">
            <div class="box">
                <p>Ready to sweeten your day?</p>
                <button class="btn" onclick="location.href='menu.php'">Start an order</button>
            </div>
            <h1>BOYCOLD<br>CAFE</h1>
            <div class="tag-line">
                <p>
                    Fresh brews, cozy vibes, and pastries to sweeten your day.
                    <span>At BoyCold Cafe, we believe great coffee is best enjoyed at your own pace.</span>
                    <span>Whether you're starting your morning, taking a break, or winding down,</span>
                    our space is made for comfort, connection, and calm.
                </p>
            </div>
        </div>
    </header>

    <section>
        <div class="hero-section">
            <div class="top-rectangle">
                <div class="top-content">
                    <h2>Made especially for banana pudding lovers.</h2>
                    <p>Show off your love for this creamy classic with a treat inspired by layers of sweet bananas, smooth pudding, and comforting homemade goodness.</p>
                    <button class="hero-btn" onclick="location.href='menu.php'">View menu</button>
                </div>
            </div>
            <div class="mid-rectangle">
                <img src="../picture/Layer 2 1.png" alt="">
                <img class="img2" src="../picture/dasdasd 1.png" alt="">
            </div>
            <div class="bottom-rectangle">
                <img src="../picture/Rectangle 24.png" alt="">
                <div class="bottom-content">
                    <h2>Your favorites just got even better.</h2>
                    <p>Introducing our 4 signature pasta flavors and our Mango Sticky Rice with 3 delightful new twists. Comfort food made the BoyCold way, crafted to satisfy every craving.</p>
                    <button class="hero-btn" onclick="location.href='../store/store.php'">Find a store</button>
                </div>
            </div>
        </div>
    </section>

    <footer>
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="../picture/icon2.png" alt="BoyCold logo">
                    <h1>BOYCOLD CAFE</h1>
                    <p>&copy; <?php echo date("Y"); ?> BoyCold Café. All Rights Reserved.</p>
                </div>
                <div class="footer-links">
                    <ul>
                        <li><a href="../footer-link/about.php">About Us</a></li>
                        <li><a href="../footer-link/compinfo.php">Company Information</a></li>
                        <li><a href="../footer-link/faqs.php">FAQs</a></li>
                        <li><a href="../footer-link/privacy.php">Privacy and Safety</a></li>
                        <li><a href="../footer-link/terms.php">Terms and Conditions</a></li>
                    </ul>
                </div>
            </div>
        </footer>

    <script src="../scr/account.js"></script>
</body>

</html>