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
$avatarRaw = $user['avatar'] ?? '';
if ($avatarRaw !== '' && !preg_match('#^(https?://|/)#', $avatarRaw)) {
    $avatarRaw = '/User/' . $avatarRaw;
}
$avatar = $avatarRaw !== '' ? htmlspecialchars($avatarRaw) : '';

// Keep session in sync
$_SESSION['user_name']  = $user['Firstname'] . ' ' . $user['Lastname'];
$_SESSION['user_email'] = $user['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="footer-css/about.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - About Us</title>
</head>
<body>

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR DRAWER -->
    <div class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li><a href="/User/home.php">HOME</a></li>
                <li><a href="/User/Menu.php">MENU</a></li>
                <li><a href="/User/status.php">ORDER</a></li>
                <li><a href="/store/store.php">STORES</a></li>
                <li class="sidebar-nav-only-not"><a href="/User/status.php">ORDERS</a></li>
                <li class="sidebar-nav-only"><a href="/User/favorites.php">FAVORITES</a></li>
                <li><a href="/User/order/cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i> CART
                    </a></li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <a href="/User/account.php" class="sidebar-avatar-link">
                <div class="sidebar-avatar" id="sidebarAvatarWrap">
                    <?php if ($avatar): ?>
                        <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar" style="display:block;" onerror="this.style.display='none'; const icon=this.parentElement.querySelector('.fa-user'); if(icon) icon.style.display='';">
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
                <li><a href="/User/home.php">HOME</a></li>
                <li><a href="/User/Menu.php">MENU</a></li>
                <li><a href="/User/status.php">ORDERS</a></li>
                <li><a href="/User/favorites.php">FAVORITES</a></li>
            </ul>
        </div>
        <div class="logo">
            <img src="../picture/Boycold Logo 2.png" alt="BoyCold logo">
        </div>
        <div class="nav-right-group">
            <a href="/User/cart.php" class="cart-link">
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
                    <a href="/User/account.php"><i class="fa-solid fa-user"></i> Account</a>
                    <hr>
                    <a href="../logout.php" class="dropdown-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
                </div>
            </div>
        </div>
    </nav>
    <!-- ABOUT MAIN -->
    <main class="about-main">

        <!-- ── HERO SECTION ── -->
        <section class="about-hero">

            <!-- LEFT: text content -->
            <div class="about-hero-left">
                <p class="about-label">ABOUT US</p>
                <h1 class="about-headline">
                    GOOD COFFEE.<br>
                    COOL PEOPLE.<br>
                    EVERY DAY.
                </h1>
                <div class="about-body">
                    <p>Boycold Cafe was born from a simple idea: <span>great coffee should be approachable, consistent, and made for everyone.</span></p>
                    <p>We built a space where quality meets comfort, whether you're starting your day, taking a break, or catching up with friends.</p>
                    <p>From our beans to our playlists, every detail is curated to keep things real and refreshing. We believe in slowing down, staying curious, and serving our community with heart.</p>
                    <p>Thank you for being part of the Boycold experience.</p>
                    <p>See you at the cafe!</p>
                </div>
            </div>

            <!-- RIGHT: image panel -->
            <div class="about-hero-right">
                <!-- Replace src with your actual cafe/drink photo -->
                <div class="about-img-placeholder">
                    <img src="/picture/ChatGPT Image May 21, 2026, 04_29_15 PM 1.png">
                </div>
            </div>

        </section>

        <!-- ── VALUES STRIP ── -->
        <section class="about-values">

            <div class="about-value-col">
                <!-- Replace with your actual icon image -->
                <div class="value-icon-wrap">
                    <svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19.5659 40.1776C14.7448 40.8232 10.3777 39.5272 7.42528 36.5748C4.47282 33.6223 3.17687 29.2552 3.82251 24.4342C4.46816 19.6131 7.00251 14.7331 10.868 10.8675C14.7336 7.00202 19.6136 4.46767 24.4347 3.82203C29.2557 3.17638 33.6228 4.47234 36.5753 7.42479C39.5277 10.3772 40.8237 14.7443 40.178 19.5654C39.5324 24.3864 36.998 29.2665 33.1325 33.132C29.267 36.9976 24.3869 39.5319 19.5659 40.1776Z" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M36.3007 7.69971C36.6673 25.6664 7.33401 18.333 7.70067 36.2997" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="value-title">QUALITY FIRST</h3>
                <p class="value-desc">We source high-quality beans and craft every drink with care.</p>
            </div>

            <div class="about-value-divider"></div>

            <div class="about-value-col">
                <div class="value-icon-wrap">
                    <svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M29.333 20.1665C32.3763 20.1665 34.8147 17.7098 34.8147 14.6665C34.8147 11.6232 32.3763 9.1665 29.333 9.1665C26.2897 9.1665 23.833 11.6232 23.833 14.6665C23.833 17.7098 26.2897 20.1665 29.333 20.1665ZM14.6663 20.1665C17.7097 20.1665 20.148 17.7098 20.148 14.6665C20.148 11.6232 17.7097 9.1665 14.6663 9.1665C11.623 9.1665 9.16634 11.6232 9.16634 14.6665C9.16634 17.7098 11.623 20.1665 14.6663 20.1665ZM14.6663 23.8332C10.3947 23.8332 1.83301 25.9782 1.83301 30.2498V34.8332H27.4997V30.2498C27.4997 25.9782 18.938 23.8332 14.6663 23.8332ZM29.333 23.8332C28.8013 23.8332 28.1963 23.8698 27.5547 23.9248C29.6813 25.4648 31.1663 27.5365 31.1663 30.2498V34.8332H42.1663V30.2498C42.1663 25.9782 33.6047 23.8332 29.333 23.8332Z" fill="white"/>
                    </svg>
                </div>
                <h3 class="value-title">COMMUNITY DRIVEN</h3>
                <p class="value-desc">We're more than a cafe, we're a place to connect.</p>
            </div>

            <div class="about-value-divider"></div>

            <div class="about-value-col">
                <div class="value-icon-wrap">
                    <svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M32.7982 4.67484C32.6459 4.37249 32.4128 4.11824 32.1248 3.94026C31.8368 3.76228 31.5051 3.66753 31.1666 3.6665H12.8332C12.1366 3.6665 11.5132 4.0515 11.2016 4.67484L9.88156 7.33317H7.35156V10.9998H36.6849V7.33317H34.1549L32.8349 4.67484H32.7982ZM10.9999 38.6465C11.0732 39.5998 11.8616 40.3332 12.8332 40.3332H31.1666C32.1199 40.3332 32.9266 39.5998 32.9999 38.6465L34.9799 12.8332H9.0199L10.9999 38.6465ZM30.7266 20.1665L29.8832 31.1665H14.1166L13.2732 20.1665H30.7449H30.7266Z" fill="white"/>
                    </svg>
                </div>
                <h3 class="value-title">MADE FOR YOU</h3>
                <p class="value-desc">Simple, consistent, and made to brighten your day.</p>
            </div>

        </section>

    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="../picture/icon2.png" alt="BoyCold logo">
                    <h1>BOYCOLD CAFE</h1>
                    <p>&copy; <?php echo date("Y"); ?> BoyCold Café. All Rights Reserved.</p>
                </div>
            <div class="footer-links">
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="compinfo.php">Company Information</a></li>
                    <li><a href="faqs.php">FAQs</a></li>
                    <li><a href="privacy.php">Privacy and Safety</a></li>
                    <li><a href="terms.php">Terms and Conditions</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <script src="footer-js/footer.js"></script>
</body>
</html>