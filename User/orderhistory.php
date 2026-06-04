<?php
session_start();
require_once '../config/db_config.php';

// Session guard
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$successMsg = '';
$errorMsg   = '';

// Handle AJAX POST (name / phone / address)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $field = $_POST['field'] ?? '';

    if ($field === 'name') {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname  = trim($_POST['lastname']  ?? '');
        if ($firstname === '' || $lastname === '') {
            echo json_encode(['success' => false, 'error' => 'First and last name cannot be empty.']);
            exit;
        }
        $stmt = $connect->prepare("UPDATE users SET firstname=?, lastname=? WHERE id=?");
        $stmt->bind_param("ssi", $firstname, $lastname, $userId);
        $stmt->execute();
        $_SESSION['user_name'] = $firstname . ' ' . $lastname;
        echo json_encode(['success' => true, 'fullname' => htmlspecialchars($firstname . ' ' . $lastname)]);
        exit;
    } elseif ($field === 'phone') {
        $value = trim($_POST['value'] ?? '');
        if ($value !== '' && !preg_match('/^09\d{9}$/', $value)) {
            echo json_encode(['success' => false, 'error' => 'Phone must be 11 digits starting with 09 (e.g. 09123456789).']);
            exit;
        }
        $stmt = $connect->prepare("UPDATE users SET phone=? WHERE id=?");
        $stmt->bind_param("si", $value, $userId);
        $stmt->execute();
        $_SESSION['user_phone'] = $value;
        echo json_encode(['success' => true, 'value' => htmlspecialchars($value)]);
        exit;
    } elseif ($field === 'address') {
        $value = trim($_POST['value'] ?? '');
        if ($value === '') {
            echo json_encode(['success' => false, 'error' => 'Address cannot be empty.']);
            exit;
        }
        $stmt = $connect->prepare("UPDATE users SET address=? WHERE id=?");
        $stmt->bind_param("si", $value, $userId);
        $stmt->execute();
        $_SESSION['user_address'] = $value;
        echo json_encode(['success' => true, 'value' => htmlspecialchars($value)]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown field.']);
    exit;
}

// Fetch latest user data
$stmt = $connect->prepare(
    "SELECT firstname, lastname, email, phone, address, avatar, card_no FROM users WHERE id = ?"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Guard: if user row is gone (e.g. DB reset), destroy session and redirect
if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$email    = htmlspecialchars($user['email']);
$phone    = $user['phone']   ? htmlspecialchars($user['phone'])   : '';
$address  = $user['address'] ? htmlspecialchars($user['address']) : '';
$avatar   = $user['avatar']  ? htmlspecialchars($user['avatar'])  : '';
$cardNo   = $user['card_no'] ? htmlspecialchars($user['card_no']) : '—';

// Keep session in sync
if ($avatar) $_SESSION['user_avatar'] = $avatar;
$_SESSION['user_name']  = $user['firstname'] . ' ' . $user['lastname'];
$_SESSION['user_email'] = $user['email'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/orderhistory.css">
    <link rel="icon" href="/picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Order History</title>
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
                <li class="sidebar-nav-only"><a href="favorites.php">FAVORITES</a></li>
                <li><a href="../store/store.php"><i class="fa-solid fa-location-dot"></i> FIND A STORE</a></li>
                <li><a href="cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i> CART
                    </a></li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <a href="account.php" class="sidebar-avatar-link">
                <div class="sidebar-avatar" id="sidebarAvatarWrap">
                    <?php if ($avatar): ?>
                        <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar">
                    <?php else: ?>
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon"></i>
                        <img id="sidebarAvatarImg" src="" alt="avatar" style="display:none;">
                    <?php endif; ?>
                </div>
            </a>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name" id="display-fullname"><?= $fullName ?></span>
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
                <li><a href="../order/status.php">ORDERS</a></li>
                <li><a href="favorites.php">FAVORITES</a></li>
            </ul>
        </div>
        <div class="logo">
            <img src="../picture/Boycold Logo 2.png" alt="BoyCold">
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
            <h1>Order Histories</h1>
            <p>Track your past orders and view details.</p>
        </div>

        <div class="history-box">

            <!-- TABS -->
            <div class="history-tabs">
                <button class="tab-btn active" data-tab="all">All Orders</button>
                <button class="tab-btn" data-tab="completed">Completed</button>
                <button class="tab-btn" data-tab="cancelled">Cancelled</button>
            </div>

            <!-- ORDER LIST -->
            <div class="history-list" id="historyList">
                <div class="no-orders-state">
                    <div class="no-orders-icon-wrap">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <p class="no-orders-title">No past orders yet</p>
                    <p class="no-orders-desc">When you place an order, it will appear here.</p>
                    <a href="menu.html" class="empty-cart-cta">Browse Menu</a>
                </div>
            </div>

        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="/picture/icon2.png" alt="BoyCold logo">
                <h1>BOYCOLD CAFE</h1>
                <p>&copy; 2026 BoyCold Cafe. All rights reserved.</p>
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

        function toggleSearch() {
            const search = document.getElementById('navSearch');
            const btn = document.getElementById('searchIconBtn');
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
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                // When you have real orders, filter by this.dataset.tab here
            });
        });

        // ── Nav avatar dropdown ────────────────────────────────────
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

        // ── Settings panel ─────────────────────────────────────────
        function expandSettings() {
            document.getElementById('settingsCard').classList.add('expanded');
        }

        function collapseSettings() {
            const card = document.getElementById('settingsCard');
            const expandedView = card.querySelector('.card-expanded-view');
            expandedView.style.display = 'none';
            card.classList.remove('expanded');
            setTimeout(() => {
                expandedView.style.display = '';
                document.querySelectorAll('.s-panel').forEach(p => p.classList.remove('s-active'));
                document.getElementById('s-panel-welcome').classList.add('s-active');
                document.querySelectorAll('.s-item').forEach(i => i.classList.remove('s-active'));
            }, 420);
        }

        function showSPanel(name, el) {
            document.querySelectorAll('.s-panel').forEach(p => p.classList.remove('s-active'));
            document.querySelectorAll('.s-item').forEach(i => i.classList.remove('s-active'));
            document.getElementById('s-panel-' + name).classList.add('s-active');
            el.classList.add('s-active');
        }

        function showMsg(panelKey, text, isError) {
            const el = document.getElementById('msg-' + panelKey);
            if (!el) return;
            el.textContent = text;
            el.style.color = isError ? '#c0392b' : '#27ae60';
            el.style.marginTop = '8px';
            el.style.fontSize = '0.85rem';
        }
    </script>

</body>

</html>