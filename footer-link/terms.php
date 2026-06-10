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


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="footer-css/terms.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Terms and Conditions</title>
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
                <li><a href="/User/home.php">HOME</a></li>
                <li><a href="/User/Menu.php">MENU</a></li>
                <li><a href="/User/status.php">ORDERS</a></li>
                <li><a href="/User/favorites.php">FAVORITES</a></li>
            </ul>
        </div>

        <!-- CENTER: logo -->
        <div class="logo">
            <img src="../picture/Boycold Logo 2.png" alt="BoyCold">
        </div>
        <div class="nav-right-group">
            <a href="/User/cart.php" class="cart-link">
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
                    <a href="../User/account.php"><i class="fa-solid fa-user"></i> Account</a>
                    <hr>
                    <a href="../logout.php" class="dropdown-logout"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="about-main">
        <section class="about-hero">
            <div class="terms-header">
                <div class="hero-label">Terms and Conditions</div>
                <p class="hero-desc">
                    Welcome to Boycold Cafe. By accessing our website, mobile app, or
                    visiting our café, you agree to the following Terms & Conditions.<br class="hero-br">
                    Please read them carefully before using our services.
                </p>
            </div>

            <div class="terms-grid">

                <div class="terms-item">
                    <div class="terms-icon">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div class="terms-text">
                        <h3>Acceptance of Terms</h3>
                        <p>By using Boycold Cafe's services, placing an order, or accessing our website or app, you agree to comply with these Terms & Conditions and all applicable laws and regulations.</p>
                    </div>
                </div>

                <div class="terms-item">
                    <div class="terms-icon">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <div class="terms-text">
                        <h3>Orders & Payments</h3>
                        <p>All orders are subject to availability and confirmation. Prices displayed on our menu, website, or app may change without prior notice. Payments must be completed before orders are processed unless stated otherwise. Boycold Cafe reserves the right to refuse or cancel orders in cases of suspected fraud, incorrect pricing, or unavailable items.</p>
                    </div>
                </div>

                <div class="terms-item">
                    <div class="terms-icon">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="terms-text">
                        <h3>Refunds & Cancellations</h3>
                        <p>Refunds or cancellations may only be granted under specific circumstances, such as incorrect orders, unavailable items, or quality concerns. Requests must be reported within a reasonable period after receiving the order.<br>Approved refunds will be processed through the original payment method whenever possible.</p>
                    </div>
                </div>

                <div class="terms-item">
                    <div class="terms-icon">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="terms-text">
                        <h3>Customer Responsibilities</h3>
                        <p>Customers are expected to provide accurate information when placing orders, including contact details and delivery information. Boycold Cafe is not responsible for delays or issues caused by incorrect customer information. Customers must also use our website, app, and services respectfully and lawfully.</p>
                    </div>
                </div>

                <div class="terms-item">
                    <div class="terms-icon">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <div class="terms-text">
                        <h3>Intellectual Property</h3>
                        <p>All content on Boycold Cafe's website and branding—including logos, images, text, designs, and graphics—is owned by or licensed to Boycold Cafe and may not be copied, reproduced, or distributed without permission.</p>
                    </div>
                </div>

                <div class="terms-item">
                    <div class="terms-icon">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="terms-text">
                        <h3>Privacy</h3>
                        <p>Your privacy is important to us. Any personal information collected through our services is handled according to our Privacy & Safety Policy.</p>
                    </div>
                </div>

                <div class="terms-item">
                    <div class="terms-icon">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div class="terms-text">
                        <h3>Limitation of Liability</h3>
                        <p>Boycold Cafe is not liable for any indirect, incidental, or consequential damages resulting from the use of our services, website, products, or delays beyond our control. While we strive to provide accurate information and quality service, we cannot guarantee uninterrupted or error-free operation at all times.</p>
                    </div>
                </div>

                <div class="terms-item">
                    <div class="terms-icon">
                        <i class="fa-solid fa-file-pen"></i>
                    </div>
                    <div class="terms-text">
                        <h3>Changes to Terms</h3>
                        <p>Boycold Cafe may update these Terms & Conditions at any time without prior notice. Updated terms will be posted on this page and become effective immediately upon posting.</p>
                    </div>
                </div>

            </div>

            <div class="terms-notice">
                <span class="terms-notice-heart"><i class="fa-solid fa-heart"></i></span>
                <div>
                    <p>We may update this Terms and Conditions from time to time. Any changes will be posted on this page with the updated effective date.</p>
                    <p><strong>Thank you for being part of the Boycold Cafe community.</strong></p>
                </div>
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

    <script>
        /* ── LOCK BACKGROUND HEIGHT ON LOAD ── */
        (function () {
            const bg = document.createElement('div');
            bg.className = 'bg-panel';
            bg.style.height = document.documentElement.scrollHeight + 'px';
            document.querySelector('.about-main').prepend(bg);
        })();

        /* ── SIDEBAR ── */
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

        /* ── FAQ ACCORDION ── */
        function toggleFaq(btn) {
            const answer = btn.nextElementSibling;
            const isOpen = btn.classList.contains('open');

            // Close all
            document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('open'));
            document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));

            // Open clicked one if it wasn't already open
            if (!isOpen) {
                btn.classList.add('open');
                answer.classList.add('open');
            }
        }

        /* ── Nav Sidebar ── */
        function toggleAvatarDropdown() {
            document.getElementById('avatarDropdown').classList.toggle('open');
        }
        document.addEventListener('click', function(e) {
            const wrap = document.querySelector('.avatar-dropdown-wrap');
            if (wrap && !wrap.contains(e.target)) {
                const dd = document.getElementById('avatarDropdown');
                if (dd) dd.classList.remove('open');
            }
        });

        function toggleSearch() {
            const search = document.getElementById('navSearch');
            const btn = document.getElementById('searchIconBtn');
            if (!search || !btn) return;
            const isOpen = search.classList.toggle('open');
            btn.classList.toggle('active', isOpen);
            if (isOpen) setTimeout(() => search.querySelector('input').focus(), 420);
            else search.querySelector('input').value = '';
        }

        document.addEventListener('click', function(e) {
            const search = document.getElementById('navSearch');
            const btn = document.getElementById('searchIconBtn');
            if (!search || !btn) return;
            if (!search.contains(e.target) && !btn.contains(e.target)) {
                search.classList.remove('open');
                btn.classList.remove('active');
                search.querySelector('input').value = '';
            }
        });
    </script>
</body>
</html>>