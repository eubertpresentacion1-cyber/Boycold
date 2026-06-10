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
    <link rel="stylesheet" href="footer-css/faqs.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - FAQs</title>
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
            <div class="hero-label">FAQs</div>
            <h1 class="hero-title">
                Got Questions?<br>
                We've got answers.
            </h1>
            <p class="hero-desc">
                Tap a question below<br class="hero-br">
                to see the answers.
            </p>

            <!-- FAQ CHAT SECTION -->
            <div class="faq-section">
                <div class="faq-accordion" id="faqAccordion">

                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            Where are you located?
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Our store location is available through the button below. Simply tap Find a Store to view our exact location and directions!</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            What are your store hours?
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>We are open daily from 2:00 PM to 1:00 AM.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            Do you offer delivery orders?
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Yes! You can place delivery orders through our website.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            Can I customize my drink?
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Absolutely! We love making your drink just the way you like it. Ask our staff about available customizations when you order.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            Do you have free Wi-Fi?
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Yes! We offer free Wi-Fi for all our customers. Just ask our staff for the password when you visit.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            What payment methods do you accept?
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>We accept cash and major e-wallets like GCash. Card payments are currently unavailable.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            Can I get my drink hot or iced?
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Yes, we offer hot and iced options for coffee drinks on our menu. Just let our staff know your preference when ordering.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            What sizes are available?
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>We serve our drinks in one standard size to ensure the best quality and flavor. Hot drinks may vary in size.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">
                            Do you have a loyalty card?
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p>Yes! We have a loyalty program where you earn points for every purchase.</p>
                        </div>
                    </div>

                </div>
            </div>
            
        </section>
        <!-- STICKY BAR -->
        <div class="faq-footer-bar">
            <div class="faq-footer-left">
                <div class="faq-footer-cup">
                    <img src="/public/assets/icons/ChatGPT Image May 27, 2026, 04_01_28 PM 1.png" alt="cup"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <i class="fa-solid fa-mug-hot faq-footer-cup-icon" style="display:none;"></i>
                </div>
                <div class="faq-footer-text">
                    <strong>Still have questions?</strong>
                    <span>Visit us in-store. We'd love to help!</span>
                </div>
            </div>
            <a href="../store/store.php" class="faq-footer-btn">
                <i class="fa-solid fa-location-dot"></i>
                Find a store
            </a>
        </div>
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

            // Instantly close all without animation
            document.querySelectorAll('.faq-answer').forEach(a => {
                a.style.transition = 'max-height 0s ease';
                a.classList.remove('open');
            });
            document.querySelectorAll('.faq-question').forEach(q => q.classList.remove('open'));

            // Open clicked one with animation if it wasn't already open
            if (!isOpen) {
                answer.style.transition = 'max-height 0.8s ease';
                btn.classList.add('open');
                answer.classList.add('open');
            }
        }

        // Nav avatar dropdown
        function toggleAvatarDropdown() {
            document.getElementById('avatarDropdown').classList.toggle('open');
        }
        document.addEventListener('click', function (e) {
            const wrap = document.querySelector('.avatar-dropdown-wrap');
            if (wrap && !wrap.contains(e.target)) {
                const dd = document.getElementById('avatarDropdown');
                if (dd) dd.classList.remove('open');
            }
        });
    </script>
</body>
</html>>