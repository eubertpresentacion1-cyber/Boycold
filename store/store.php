<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$stmt = $connect->prepare("SELECT Firstname, Lastname, email, avatar FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$fullName  = htmlspecialchars($user['Firstname'] . ' ' . $user['Lastname']);
$userEmail = htmlspecialchars($user['email']);
$avatar    = $user['avatar'] ? htmlspecialchars($user['avatar']) : '';

$_SESSION['user_name']  = $user['Firstname'] . ' ' . $user['Lastname'];
$_SESSION['user_email'] = $user['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="location.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <title>BoyCold - Stores</title>

</head>
<body>

    <!-- BACKGROUND IMAGE -->
    <div class="background"></div>

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR DRAWER -->
    <div class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li><a href="../User/home.php">HOME</a></li>
                <li><a href="../User/Menu.php">MENU</a></li>
                <li><a href="store.php">STORES</a></li>
                <li class="sidebar-nav-only"><a href="../User/status.php">ORDERS</a></li>
                <li class="sidebar-nav-only"><a href="../User/favorites.php">FAVORITES</a></li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <div class="sidebar-avatar" id="sidebarAvatarWrap">
                <?php if ($avatar): ?>
                    <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar">
                <?php else: ?>
                    <i class="fa-solid fa-user" id="sidebarAvatarIcon"></i>
                    <img id="sidebarAvatarImg" src="" alt="avatar" style="display:none;">
                <?php endif; ?>
            </div>
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
                <li><a href="../User/home.php">HOME</a></li>
                <li><a href="../User/Menu.php">MENU</a></li>
                <li><a href="../User/status.php">ORDERS</a></li>
                <li><a href="../User/favorites.php">FAVORITES</a></li>
            </ul>
        </div>
        <div class="logo">
            <img src="../picture/Boycold Logo 2.png" alt="BoyCold logo">
        </div>
    </nav>

    <!-- STORE SELECTOR SECTION -->
    <main class="store-main">
        <div class="store-card">

            <!-- LEFT PANEL -->
            <div class="store-left">
                <h1 class="store-title">BoyCold Cafe</h1>

                <!-- Dropdown 1: City -->
                <div class="form-group">
                    <label for="citySelect">Choose your city</label>
                    <div class="select-wrapper">
                        <select id="citySelect" onchange="onCityChange()">
                            <option value="baliwag">Baliwag</option>
                            <option value="bustos">Bustos</option>
                        </select>
                        <i class="fa-solid fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <!-- Dropdown 2: Shop -->
                <div class="form-group">
                    <label for="shopSelect">Choose shop address</label>
                    <div class="select-wrapper">
                        <select id="shopSelect" onchange="onShopChange()">
                            <option value="0">BoyCold Cafe Baliwag</option>
                        </select>
                        <i class="fa-solid fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <!-- GPS link -->
                <a href="#" class="gps-link" onclick="determineLocation(); return false;">
                    <i class="fa-solid fa-location-crosshairs"></i>
                    Determine your location?
                </a>

                <div class="store-divider"></div>

                <!-- Address info -->
                <div class="store-info-block">
                    <span class="info-label">Store address</span>
                    <div class="info-row">
                        <i class="fa-solid fa-location-dot pin-icon"></i>
                        <span id="shopAddress" class="info-text">40 Calle Rizal, Baliwag, 3006 Bulacan</span>
                    </div>
                </div>

                <!-- Hours -->
                <div class="store-info-block">
                    <span class="info-label">Store opening hours</span>
                    <div class="info-row hours-row">
                        <i class="fa-regular fa-clock clock-icon"></i>
                        <span class="open-badge">Open</span>
                        <span id="storeHours" class="info-text">14:00 – 1:00</span>
                        <div class="day-select-wrapper">
                            <select id="daySelect" class="day-select">
                                <option>Today</option>
                                <option>Mon</option>
                                <option>Tue</option>
                                <option>Wed</option>
                                <option>Thu</option>
                                <option>Fri</option>
                                <option>Sat</option>
                                <option>Sun</option>
                            </select>
                            <i class="fa-solid fa-chevron-down day-arrow"></i>
                        </div>
                    </div>
                </div>

                <!-- Phone -->
                <div class="store-info-block">
                    <span class="info-label">Phone number:</span>
                    <div class="info-row">
                        <span id="phoneNum" class="info-text">0911-222-3333</span>
                        <i class="fa-solid fa-pencil edit-icon"></i>
                    </div>
                </div>

                <div class="store-spacer"></div>

                <!-- Choose button -->
                <button class="choose-btn" onclick="chooseStore()">Choose</button>
            </div>

            <!-- RIGHT: MAP -->
            <div class="store-map-container">
                <div id="mapContainer"></div>
            </div>

        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-logo">
               <img src="../picture/icon2.png" alt="BoyCold logo">
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="location.js"></script>
</body>
</html>