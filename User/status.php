<?php
session_start();
require_once '../config/db_config.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
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
    <link rel="stylesheet" href="css/status.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Order Status</title>
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
                <li><a href="home.php">HOME</a></li>
                <li><a href="menu.php">MENU</a></li>
                <li><a href="status.php">ORDER</a></li>
                <li><a href="../store/store.php">STORES</a></li>
                <li class="sidebar-nav-only-not"><a href="status.php">ORDERS</a></li>
                <li class="sidebar-nav-only"><a href="favorites.php">FAVORITES</a></li>
                <li><a href="../order/cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i> CART
                    </a></li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <a href="../User/account.php" class="sidebar-avatar-link">
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
                <li><a href="home.php">HOME</a></li>
                <li><a href="menu.php">MENU</a></li>
                <li><a href="status.php">ORDERS</a></li>
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

    <!-- ORDER MAIN -->
    <main class="order-main">

        <div class="order-header">
            <h1>Order Status</h1>
        </div>

        <div class="order-card">

            <!-- Order info -->
            <div class="no-order-banner">
                <div class="no-order-banner-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <div class="no-order-banner-text">
                    <strong>No Active Order</strong>
                    <span>You haven't placed an order yet. Browse our menu and start your order!</span>
                </div>
                <a href="../user/Menu.php" class="no-order-banner-btn">Order Now</a>
            </div>

            <div class="progress-tracker">
                <div class="progress-line-fill"></div>

                <!-- Step 1: Order Confirmed — active (current step) -->
                <div class="progress-step">
                    <div class="step-circle active">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div class="step-label">Order Confirmed</div>
                    <div class="step-time">01:30 PM</div>
                </div>

                <!-- Step 2: Payment Confirmed — inactive -->
                <div class="progress-step">
                    <div class="step-circle step-circle-dark">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <div class="step-label">Payment Confirmed</div>
                </div>

                <!-- Step 3: Preparing — inactive -->
                <div class="progress-step">
                    <div class="step-circle">
                        <svg width="16" height="20" viewBox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7.58301 0.25C9.33013 0.25 10.9073 0.349852 12.043 0.509766C12.6125 0.589978 13.0608 0.684219 13.3613 0.78418C13.513 0.834659 13.6124 0.882113 13.6689 0.920898C13.6805 0.928817 13.6878 0.936592 13.6934 0.941406L13.6973 0.954102L14.0059 2.23047L14.2354 2.20215L14.1621 2.4043C14.4438 2.50776 14.6461 2.61313 14.7734 2.71191C14.9042 2.81339 14.9168 2.8758 14.917 2.89355V3.87012C14.917 3.88364 14.9108 3.92585 14.8379 3.99707C14.7645 4.06862 14.6428 4.14983 14.4639 4.23438C14.1069 4.40299 13.5743 4.56077 12.8965 4.69629C11.5446 4.96656 9.66549 5.13574 7.58301 5.13574C5.50071 5.13572 3.62233 4.96645 2.27051 4.69629C1.59272 4.56083 1.06013 4.40288 0.703125 4.23438C0.524147 4.14987 0.402468 4.06857 0.329102 3.99707C0.256273 3.92602 0.250021 3.88452 0.25 3.87109V2.89453C0.25008 2.87718 0.261394 2.81467 0.392578 2.71289C0.519971 2.61409 0.722937 2.50921 1.00488 2.40527L1.12988 2.35938L1.16113 2.22949L1.46973 0.955078L1.47266 0.941406C1.47824 0.936621 1.48578 0.929648 1.49707 0.921875C1.5535 0.883047 1.65296 0.835735 1.80469 0.785156C2.1051 0.685052 2.55353 0.591153 3.12305 0.510742C4.25862 0.350436 5.83586 0.250012 7.58301 0.25ZM2.81641 18.5537L2.81445 18.5381L1.19336 5.73633C3.29346 6.32886 6.34612 6.39551 7.58496 6.39551C8.28019 6.39551 9.52701 6.37491 10.8545 6.25586C12.0148 6.15179 13.2546 5.97085 14.2441 5.65625L12.3535 18.5332L12.3506 18.5508V18.5693C12.3506 18.6204 12.3081 18.7324 12.0723 18.8818C11.8487 19.0234 11.5086 19.1598 11.0664 19.2783C10.185 19.5146 8.95312 19.6641 7.58301 19.6641C6.21311 19.664 4.98189 19.5146 4.10059 19.2783C3.65807 19.1597 3.31732 19.0235 3.09375 18.8818C2.85809 18.7325 2.81641 18.6204 2.81641 18.5693V18.5537Z" fill="black" stroke="#483121" stroke-width="0.5" />
                        </svg>
                    </div>
                    <div class="step-label">Preparing</div>
                </div>

                <!-- Step 4: Out for Delivery — inactive -->
                <div class="progress-step">
                    <div class="step-circle">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18.26 13.47C18.26 13.47 18.19 13.43 18.16 13.42C17.76 13.2 17.32 13.1 16.87 13.05L15.79 6H17.01C17.56 6 18.01 5.55 18.01 5V3C18.01 2.45 17.56 2 17.01 2H15.12C14.71 0.81 13.59 0 12.29 0H10.01V2H12.29C12.5294 1.99969 12.7611 2.0853 12.9427 2.24128C13.1244 2.39727 13.2441 2.61326 13.28 2.85L14.9 13.39C14.44 13.63 14.03 13.96 13.71 14.39C13.57 14.58 13.46 14.78 13.35 14.99H11V5C11 4.45 10.55 4 10 4H1C0.45 4 0 4.45 0 5V9C0 9.55 0.45 10 1 10H1.03C0.4 10.84 0 11.87 0 13V16C0 16.55 0.45 17 1 17H2.05C2.3 18.69 3.74 20 5.5 20C7.26 20 8.7 18.69 8.95 17H13.05C13.3 18.69 14.74 20 16.5 20C18.43 20 20 18.43 20 16.5C20 15.26 19.33 14.1 18.26 13.47ZM2 6H9V8H2V6ZM5.5 18C5.27846 18.0001 5.05965 17.9511 4.85934 17.8564C4.65903 17.7618 4.48221 17.6239 4.34161 17.4527C4.20101 17.2815 4.10013 17.0812 4.04625 16.8663C3.99237 16.6515 3.98682 16.4273 4.03 16.21C4.08 15.98 4.17 15.76 4.3 15.6C4.41 15.45 4.55 15.34 4.7 15.24H4.71C5.01 15.06 5.36 14.99 5.7 15.03H5.76C5.92 15.06 6.09 15.11 6.23 15.19C6.25 15.2 6.27 15.21 6.3 15.22C6.65 15.45 6.89 15.79 6.97 16.18C6.99 16.28 7 16.39 7 16.49C7 17.32 6.33 17.99 5.5 17.99V18ZM16.5 18C16.2211 18.0007 15.9476 17.9236 15.7101 17.7773C15.4727 17.631 15.2809 17.4214 15.1561 17.1719C15.0314 16.9225 14.9788 16.6432 15.0042 16.3655C15.0296 16.0878 15.1321 15.8227 15.3 15.6C15.5189 15.3084 15.8362 15.1061 16.193 15.0307C16.5498 14.9553 16.9218 15.0119 17.24 15.19C17.4713 15.3224 17.6641 15.5129 17.7991 15.7427C17.9342 15.9724 18.0069 16.2335 18.01 16.5C18.01 17.33 17.34 18 16.51 18H16.5Z" fill="black" />
                        </svg>
                    </div>
                    <div class="step-label">Out for Delivery</div>
                </div>

                <!-- Step 5: Delivered — inactive -->
                <div class="progress-step">
                    <div class="step-circle">
                        <svg width="27" height="22" viewBox="0 0 27 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M13.6663 0.143491C13.5202 0.0498023 13.3504 0 13.1769 0C13.0034 0 12.8335 0.0498023 12.6875 0.143491L0 8.29974L0.97875 9.82224L2.30187 8.97399V20.8422C2.30187 21.0826 2.39735 21.3131 2.56731 21.4831C2.73726 21.653 2.96777 21.7485 3.20812 21.7485H23.1456C23.386 21.7485 23.6165 21.653 23.7864 21.4831C23.9564 21.3131 24.0519 21.0826 24.0519 20.8422V8.97399L25.375 9.82405L26.3538 8.29793L13.6663 0.143491ZM10.4581 12.686C10.2178 12.686 9.98726 12.7815 9.81731 12.9514C9.64736 13.1214 9.55188 13.3519 9.55188 13.5922V19.936H11.3644V14.4985H14.9894V19.936H16.8019V13.5922C16.8019 13.3519 16.7064 13.1214 16.5364 12.9514C16.3665 12.7815 16.136 12.686 15.8956 12.686H10.4581Z" fill="black" />
                        </svg>
                    </div>
                    <div class="step-label">Delivered</div>
                </div>
            </div>

            <div class="order-summary">
                <div class="section-label">Order Summary</div>
                <div class="empty-order">
                    <div class="empty-order-icon-wrap">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <span class="empty-order-title">No items in this order yet.</span>
                </div>
                <div class="order-divider"></div>
                <div class="order-total">
                    <span>Total:</span>
                    <strong>₱0.00</strong>
                </div>
            </div>

            <!-- Delivery Details -->
            <div class="delivery-details">
                <div class="section-label">Delivery Details</div>
                <div class="empty-delivery">
                    <div class="empty-delivery-icon-wrap">
                        <i class="fa-solid fa-map-location-dot"></i>
                    </div>
                    <span class="empty-delivery-title">No delivery details yet.</span>
                    <span class="empty-delivery-sub">Place an order to see delivery information here.</span>
                </div>
            </div>
            <!-- Need Help -->
            <div class="need-help">
                <div class="section-label">Need Help?</div>

                <button class="help-btn" onclick="openRiderModal()">
                    <div class="help-btn-icon">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.4508 7.22542C14.4508 6.27657 14.264 5.337 13.9008 4.46037C13.5377 3.58374 13.0055 2.78722 12.3346 2.11628C11.6636 1.44533 10.8671 0.913114 9.99047 0.550003C9.11384 0.186891 8.17428 0 7.22542 0V1.44508C8.36862 1.44506 9.48615 1.78401 10.4367 2.41909C11.3873 3.05417 12.1282 3.95686 12.5657 5.013C12.8563 5.71442 13.0058 6.46621 13.0058 7.22542H14.4508ZM0 5.78034V2.16763C0 1.976 0.0761247 1.79222 0.211628 1.65671C0.347131 1.52121 0.530912 1.44508 0.722542 1.44508H4.33525C4.52688 1.44508 4.71067 1.52121 4.84617 1.65671C4.98167 1.79222 5.0578 1.976 5.0578 2.16763V5.0578C5.0578 5.24943 4.98167 5.43321 4.84617 5.56871C4.71067 5.70421 4.52688 5.78034 4.33525 5.78034H2.89017C2.89017 7.31338 3.49917 8.78363 4.58319 9.86765C5.66721 10.9517 7.13747 11.5607 8.67051 11.5607V10.1156C8.67051 9.92396 8.74663 9.74018 8.88214 9.60468C9.01764 9.46917 9.20142 9.39305 9.39305 9.39305H12.2832C12.4748 9.39305 12.6586 9.46917 12.7941 9.60468C12.9296 9.74018 13.0058 9.92396 13.0058 10.1156V13.7283C13.0058 13.9199 12.9296 14.1037 12.7941 14.2392C12.6586 14.3747 12.4748 14.4508 12.2832 14.4508H8.67051C3.88222 14.4508 0 10.5686 0 5.78034Z" fill="white" />
                            <path d="M11.2306 5.56643C11.4486 6.09237 11.5608 6.65609 11.5608 7.22539H10.2603C10.2603 6.42054 9.94054 5.64866 9.37143 5.07955C8.80231 4.51044 8.03043 4.19071 7.22559 4.19071V2.89014C8.08299 2.89018 8.92113 3.14447 9.63402 3.62084C10.3469 4.09722 10.9025 4.77429 11.2306 5.56643Z" fill="white" />
                        </svg>
                    </div>
                    <div class="help-btn-text">
                        <span class="help-btn-title">Contact Rider</span>
                        <span class="help-btn-sub">Call or message your rider</span>
                    </div>
                    <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.0705161 0.542819C-0.0940739 1.02779 0.0392648 1.61712 0.368445 1.85961L5.84366 5.89286L0.368445 9.9261C0.0392648 10.1686 -0.0940739 10.7579 0.0705161 11.2429C0.235106 11.7279 0.635122 11.9243 0.964302 11.6818L7.63124 6.77072C7.85624 6.60497 8 6.26426 8 5.89286C8 5.52145 7.85833 5.18075 7.63124 5.015L0.964302 0.103889C0.635122 -0.138596 0.235106 0.0578477 0.0705161 0.542819Z" fill="#483121" />
                    </svg>
                </button>

                <button class="help-btn" onclick="openReportModal()">
                    <div class="help-btn-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="help-btn-text">
                        <span class="help-btn-title">Report a Problem</span>
                        <span class="help-btn-sub">Wrong order, missing item, etc.</span>
                    </div>
                    <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.0705161 0.542819C-0.0940739 1.02779 0.0392648 1.61712 0.368445 1.85961L5.84366 5.89286L0.368445 9.9261C0.0392648 10.1686 -0.0940739 10.7579 0.0705161 11.2429C0.235106 11.7279 0.635122 11.9243 0.964302 11.6818L7.63124 6.77072C7.85624 6.60497 8 6.26426 8 5.89286C8 5.52145 7.85833 5.18075 7.63124 5.015L0.964302 0.103889C0.635122 -0.138596 0.235106 0.0578477 0.0705161 0.542819Z" fill="#483121" />
                    </svg>
                </button>
            </div>

        </div><!-- end .order-card -->

    </main>
    <div class="modal-overlay" id="riderModal" onclick="closeRiderModal(event)">
        <div class="modal">
            <button class="modal-close" onclick="closeRiderModalDirect()">&times;</button>

            <div class="modal-icon-svg">
                <svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M25.8333 2.8384e-10C29.2258 -1.5902e-05 32.585 0.66817 35.7193 1.96641C38.8536 3.26464 41.7014 5.16751 44.1003 7.56635C46.4991 9.9652 48.402 12.813 49.7003 15.9473C50.9985 19.0815 51.6667 22.4408 51.6667 25.8333C51.6667 40.1007 40.1007 51.6667 25.8333 51.6667C11.566 51.6667 0 40.1007 0 25.8333C0 11.566 11.566 2.8384e-10 25.8333 2.8384e-10ZM28.4167 28.4167H23.25C16.8544 28.4167 11.3637 32.2901 8.99545 37.8194C12.7426 43.0738 18.8878 46.5 25.8333 46.5C32.7787 46.5 38.924 43.0738 42.6713 37.819C40.303 32.2901 34.8123 28.4167 28.4167 28.4167ZM25.8333 7.75C21.5531 7.75 18.0833 11.2198 18.0833 15.5C18.0833 19.7802 21.5531 23.25 25.8333 23.25C30.1135 23.25 33.5833 19.7802 33.5833 15.5C33.5833 11.2198 30.1136 7.75 25.8333 7.75Z" fill="#483121" />
                </svg>
            </div>
            <div class="modal-title">Contact Rider</div>
            <div class="modal-subtitle">Here's your rider's information</div>

            <div class="rider-card">
                <div class="rider-card-title">Rider Information</div>

                <div class="rider-row">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6.66667 0C7.55072 0 8.39857 0.351189 9.02369 0.976311C9.64881 1.60143 10 2.44928 10 3.33333C10 4.21739 9.64881 5.06523 9.02369 5.69036C8.39857 6.31548 7.55072 6.66667 6.66667 6.66667C5.78261 6.66667 4.93476 6.31548 4.30964 5.69036C3.68452 5.06523 3.33333 4.21739 3.33333 3.33333C3.33333 2.44928 3.68452 1.60143 4.30964 0.976311C4.93476 0.351189 5.78261 0 6.66667 0ZM6.66667 1.66667C6.22464 1.66667 5.80072 1.84226 5.48816 2.15482C5.17559 2.46738 5 2.89131 5 3.33333C5 3.77536 5.17559 4.19928 5.48816 4.51184C5.80072 4.8244 6.22464 5 6.66667 5C7.10869 5 7.53262 4.8244 7.84518 4.51184C8.15774 4.19928 8.33333 3.77536 8.33333 3.33333C8.33333 2.89131 8.15774 2.46738 7.84518 2.15482C7.53262 1.84226 7.10869 1.66667 6.66667 1.66667ZM6.66667 7.5C8.89167 7.5 13.3333 8.60833 13.3333 10.8333V13.3333H0V10.8333C0 8.60833 4.44167 7.5 6.66667 7.5ZM6.66667 9.08333C4.19167 9.08333 1.58333 10.3 1.58333 10.8333V11.75H11.75V10.8333C11.75 10.3 9.14167 9.08333 6.66667 9.08333Z" fill="black" />
                    </svg>
                    <span class="rider-row-label">Rider Name</span>
                    <span class="rider-row-value">Dave Andrew Santiago</span>
                </div>
                <div class="rider-divider"></div>
                <div class="rider-row">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.895 9.605C8.02408 9.66428 8.1695 9.67782 8.30731 9.6434C8.44511 9.60898 8.56708 9.52864 8.65312 9.41562L8.875 9.125C8.99143 8.96975 9.14241 8.84375 9.31598 8.75697C9.48955 8.67018 9.68094 8.625 9.875 8.625H11.75C12.0815 8.625 12.3995 8.7567 12.6339 8.99112C12.8683 9.22554 13 9.54348 13 9.875V11.75C13 12.0815 12.8683 12.3995 12.6339 12.6339C12.3995 12.8683 12.0815 13 11.75 13C8.76631 13 5.90483 11.8147 3.79505 9.70495C1.68526 7.59517 0.5 4.73369 0.5 1.75C0.5 1.41848 0.631696 1.10054 0.866116 0.866116C1.10054 0.631696 1.41848 0.5 1.75 0.5H3.625C3.95652 0.5 4.27446 0.631696 4.50888 0.866116C4.7433 1.10054 4.875 1.41848 4.875 1.75V3.625C4.875 3.81906 4.82982 4.01045 4.74303 4.18402C4.65625 4.35759 4.53025 4.50857 4.375 4.625L4.0825 4.84438C3.96776 4.93199 3.88689 5.05662 3.85362 5.19709C3.82035 5.33757 3.83674 5.48523 3.9 5.615C4.75418 7.34992 6.15902 8.753 7.895 9.605Z" stroke="black" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span class="rider-row-label">Phone Number</span>
                    <span class="rider-row-value">0945 123 4589</span>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="reportModal" onclick="closeReportModal(event)">
        <div class="modal">
            <button class="modal-close" onclick="closeReportModalDirect()">&times;</button>

            <div class="modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="modal-title">Report a Problem</div>
            <div class="modal-subtitle">Let us know what went wrong</div>

            <div class="report-form">

                <!-- Issue dropdown -->
                <div>
                    <label class="form-label">What's the issue?</label>
                    <div class="custom-select-wrapper" id="selectWrapper">
                        <button class="custom-select-btn" id="selectBtn" onclick="toggleDropdown()" type="button">
                            <span id="selectDisplay" style="color:#aaa;">Select an issue</span>
                        </button>
                        <i class="fa-solid fa-chevron-down select-arrow-icon"></i>
                        <div class="custom-dropdown" id="customDropdown">
                            <div class="dropdown-option" onclick="selectIssue('Missing Items')">Missing Items</div>
                            <div class="dropdown-option" onclick="selectIssue('Late Delivery')">Late Delivery</div>
                            <div class="dropdown-option" onclick="selectIssue('Damaged or Spilled Order')">Damaged or Spilled Order</div>
                            <div class="dropdown-option" onclick="selectIssue('Can\'t Contact the Rider')">Can't Contact the Rider</div>
                            <div class="dropdown-option" onclick="selectIssue('Wrong Order')">Wrong Order</div>
                            <div class="dropdown-option" onclick="selectIssue('Other')">Other</div>
                        </div>
                    </div>
                </div>

                <!-- Tell us more -->
                <div>
                    <label class="form-label">Tell Us More</label>
                    <div class="report-textarea-wrap">
                        <textarea
                            class="report-textarea"
                            id="reportTextarea"
                            placeholder="Please provide more details on your concern"
                            maxlength="500"
                            oninput="updateCharCount(this)"></textarea>
                        <div class="preview-list" id="previewList"></div>
                        <div class="textarea-footer">
                            <div style="position:relative;">
                                <button class="attach-btn" type="button" onclick="toggleAttachPopup(event)" title="Attach image">
                                    <i class="fa-solid fa-paperclip"></i>
                                </button>
                                <div class="attach-popup" id="attachPopup">
                                    <div class="attach-option" onclick="triggerCamera()">
                                        <svg width="9" height="9" viewBox="0 0 9 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M2.645 0.932083C2.75625 0.38625 3.24208 0 3.79833 0H5.16C5.71625 0 6.20167 0.38625 6.31333 0.932083C6.32564 0.992188 6.35781 1.0464 6.40467 1.08601C6.45153 1.12561 6.51035 1.1483 6.57167 1.15042H6.58542C7.17 1.17625 7.61917 1.24792 7.99417 1.49417C8.23042 1.64917 8.43375 1.84833 8.59208 2.08125C8.78917 2.37042 8.87583 2.70292 8.9175 3.10458C8.95833 3.4975 8.95833 3.98958 8.95833 4.61292V4.64833C8.95833 5.27167 8.95833 5.76417 8.9175 6.15667C8.87583 6.55833 8.78917 6.89083 8.59208 7.18042C8.43288 7.41315 8.22978 7.61258 7.99417 7.7675C7.70125 7.95958 7.365 8.04458 6.9575 8.085C6.55833 8.125 6.05792 8.125 5.42208 8.125H3.53625C2.90042 8.125 2.4 8.125 2.00083 8.085C1.59333 8.04458 1.25708 7.96 0.964167 7.7675C0.728518 7.61246 0.525412 7.41289 0.36625 7.18C0.169167 6.89083 0.0825 6.55833 0.0408334 6.15667C2.17309e-08 5.76417 0 5.27167 0 4.64833V4.61292C0 3.98958 2.17309e-08 3.4975 0.0408334 3.10458C0.0825 2.70292 0.169167 2.37042 0.36625 2.08125C0.525412 1.84836 0.728518 1.64879 0.964167 1.49375C1.33917 1.24792 1.78833 1.17625 2.37292 1.15083L2.38 1.15042H2.38667C2.44798 1.1483 2.5068 1.12561 2.55366 1.08601C2.60052 1.0464 2.63269 0.992188 2.645 0.932083ZM3.79833 0.625C3.53167 0.625 3.30792 0.809583 3.2575 1.05667C3.17625 1.45667 2.82125 1.77167 2.39417 1.77542C1.8325 1.80042 1.53167 1.86917 1.30667 2.01667C1.13977 2.12664 0.995849 2.26801 0.882917 2.43292C0.767917 2.60167 0.69875 2.81792 0.662083 3.16917C0.625417 3.52583 0.625 3.98583 0.625 4.63083C0.625 5.27583 0.625 5.73542 0.6625 6.09208C0.69875 6.44333 0.767917 6.65958 0.883333 6.82875C0.995 6.99292 1.13875 7.13458 1.30708 7.245C1.48083 7.35875 1.70333 7.4275 2.06292 7.46333C2.42708 7.49958 2.89625 7.5 3.55333 7.5H5.405C6.06167 7.5 6.53083 7.5 6.89542 7.46333C7.255 7.4275 7.4775 7.35917 7.65125 7.245C7.81958 7.13458 7.96375 6.99292 8.07542 6.82833C8.19042 6.65958 8.25958 6.44333 8.29625 6.09208C8.33292 5.73542 8.33333 5.27542 8.33333 4.63083C8.33333 3.98625 8.33333 3.52583 8.29583 3.16917C8.25958 2.81792 8.19042 2.60167 8.075 2.43292C7.96211 2.26786 7.81819 2.12634 7.65125 2.01625C7.42708 1.86917 7.12625 1.80042 6.56375 1.77542C6.13708 1.77125 5.78208 1.45708 5.70083 1.05667C5.67417 0.933446 5.60579 0.823205 5.50726 0.744559C5.40872 0.665913 5.28607 0.623683 5.16 0.625H3.79833ZM4.47917 3.54167C4.23053 3.54167 3.99207 3.64044 3.81625 3.81625C3.64044 3.99207 3.54167 4.23053 3.54167 4.47917C3.54167 4.72781 3.64044 4.96626 3.81625 5.14208C3.99207 5.31789 4.23053 5.41667 4.47917 5.41667C4.72781 5.41667 4.96626 5.31789 5.14208 5.14208C5.31789 4.96626 5.41667 4.72781 5.41667 4.47917C5.41667 4.23053 5.31789 3.99207 5.14208 3.81625C4.96626 3.64044 4.72781 3.54167 4.47917 3.54167ZM2.91667 4.47917C2.91667 4.06477 3.08129 3.66734 3.37431 3.37431C3.66734 3.08129 4.06477 2.91667 4.47917 2.91667C4.89357 2.91667 5.291 3.08129 5.58402 3.37431C5.87705 3.66734 6.04167 4.06477 6.04167 4.47917C6.04167 4.89357 5.87705 5.291 5.58402 5.58402C5.291 5.87705 4.89357 6.04167 4.47917 6.04167C4.06477 6.04167 3.66734 5.87705 3.37431 5.58402C3.08129 5.291 2.91667 4.89357 2.91667 4.47917ZM6.66667 3.22917C6.66667 3.14629 6.69959 3.0668 6.7582 3.0082C6.8168 2.94959 6.89629 2.91667 6.97917 2.91667H7.39583C7.47871 2.91667 7.5582 2.94959 7.6168 3.0082C7.67541 3.0668 7.70833 3.14629 7.70833 3.22917C7.70833 3.31205 7.67541 3.39153 7.6168 3.45014C7.5582 3.50874 7.47871 3.54167 7.39583 3.54167H6.97917C6.89629 3.54167 6.8168 3.50874 6.7582 3.45014C6.69959 3.39153 6.66667 3.31205 6.66667 3.22917Z" fill="black" />
                                        </svg> Take a photo
                                    </div>
                                    <div class="attach-option" onclick="triggerUpload()">
                                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.75 8.66667V1.08333C9.75 0.4875 9.2625 0 8.66667 0H1.08333C0.4875 0 0 0.4875 0 1.08333V8.66667C0 9.2625 0.4875 9.75 1.08333 9.75H8.66667C9.2625 9.75 9.75 9.2625 9.75 8.66667ZM2.97917 5.6875L4.33333 7.31792L6.22917 4.875L8.66667 8.125H1.08333L2.97917 5.6875Z" fill="black" />
                                        </svg> Upload a photo
                                    </div>
                                </div>
                            </div>
                            <span class="char-count" id="charCount">0/500</span>
                        </div>
                    </div>

                    <!-- Hidden file inputs -->
                    <input type="file" id="cameraInput" accept="image/*" capture style="display:none" onchange="handleFileSelect(this)">
                    <input type="file" id="uploadInput" accept="image/*" multiple style="display:none" onchange="handleFileSelect(this)">
                </div>
                <!-- ══════════════════════════════
                    CAMERA MODAL
                ══════════════════════════════ -->
                <div class="camera-modal-overlay" id="cameraModal">
                    <div class="camera-video-wrap">
                        <video id="cameraVideo" autoplay playsinline muted></video>
                        <p class="camera-error" id="cameraError" style="display:none;"></p>
                        <div class="camera-controls">
                            <button class="camera-cancel-btn" onclick="closeCamera()">Cancel</button>
                            <button class="camera-capture-btn" id="captureBtn" onclick="capturePhoto()" title="Take photo"></button>
                        </div>
                    </div>
                </div>

                <!-- Hidden canvas for capturing frame -->
                <canvas id="cameraCanvas" style="display:none;"></canvas>

                <!-- Actions -->
                <div class="modal-actions">
                    <button class="btn-cancel" onclick="closeReportModalDirect()">Cancel</button>
                    <button class="btn-submit" onclick="submitReport()">Submit Report</button>
                </div>

            </div>
        </div>
    </div>

    <!-- FOOTER -->
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

    <div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
        <img class="lightbox-img" id="lightboxImg" src="" alt="Preview">
    </div>


    <script>
        async function handleAvatarFile(file) {
            if (!file) return;

            const avatarMsg = document.getElementById('avatar-msg');
            const profileImg = document.getElementById('profileAvatarImg');
            const sidebarImg = document.getElementById('sidebarAvatarImg');
            const navImg = document.getElementById('navAvatarImg');
            const sidebarIcon = document.getElementById('sidebarAvatarIcon');
            const navIcon = document.getElementById('navAvatarIcon');

            // Instant local preview
            const localURL = URL.createObjectURL(file);
            if (profileImg) {
                profileImg.src = localURL;
                profileImg.style.cssText = 'position:absolute;inset:0;width:110px;height:110px;object-fit:cover;border-radius:50%;display:block;';
            }
            if (sidebarImg) {
                sidebarImg.src = localURL;
                sidebarImg.style.display = '';
            }
            if (navImg) {
                navImg.src = localURL;
                navImg.style.display = 'block';
            }
            if (navIcon) {
                navIcon.style.display = 'none';
            }
            if (sidebarIcon) {
                sidebarIcon.style.display = 'none';
            }

            avatarMsg.style.color = '#888';
            avatarMsg.textContent = 'Uploading…';

            const fd = new FormData();
            fd.append('avatar', file);

            try {
                const res = await fetch('uploadavatar.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.success) {
                    const newSrc = data.path + '?v=' + Date.now();
                    if (profileImg) profileImg.src = newSrc;
                    if (sidebarImg) sidebarImg.src = newSrc;
                    if (navImg) navImg.src = newSrc;
                    if (navIcon) navIcon.style.display = 'none';
                    avatarMsg.style.color = '#27ae60';
                    avatarMsg.textContent = data.message || 'Photo updated!';
                    setTimeout(() => {
                        avatarMsg.textContent = '';
                    }, 3000);
                } else {
                    avatarMsg.style.color = '#c0392b';
                    avatarMsg.textContent = data.error || 'Upload failed.';
                }
            } catch (err) {
                avatarMsg.style.color = '#c0392b';
                avatarMsg.textContent = 'Network error. Try again.';
            }

            URL.revokeObjectURL(localURL);
            document.getElementById('avatarFileInput').value = '';
            document.getElementById('avatarCameraInput').value = '';
        }

        const avatarFileInput = document.getElementById('avatarFileInput');
        const avatarCameraInput = document.getElementById('avatarCameraInput');
        if (avatarFileInput) avatarFileInput.addEventListener('change', function() {
            handleAvatarFile(this.files[0]);
        });
        if (avatarCameraInput) avatarCameraInput.addEventListener('change', function() {
            handleAvatarFile(this.files[0]);
        });

        // ── Avatar hover overlay ───────────────────────────────────
        const avatarWrap = document.getElementById('profileAvatarWrap');
        const avatarOverlay = document.getElementById('avatarOverlay');
        if (avatarWrap && avatarOverlay) {
            avatarWrap.addEventListener('mouseenter', () => avatarOverlay.style.opacity = '1');
            avatarWrap.addEventListener('mouseleave', () => avatarOverlay.style.opacity = '0');
        }

        // Category filter active state
        document.querySelectorAll('.box ul li a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.box ul li a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Heart toggle
        document.querySelectorAll('.card-heart').forEach(btn => {
            btn.addEventListener('click', function() {
                const icon = this.querySelector('i');
                const isLiked = icon.style.color === 'rgb(229, 57, 53)';
                if (isLiked) {
                    icon.style.color = 'transparent';
                    icon.style.webkitTextStroke = '1.5px #e53935';
                } else {
                    icon.style.color = '#e53935';
                    icon.style.webkitTextStroke = '0';
                }
            });
        });

        /* ── Nav Sidebar ── */
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
            document.getElementById('avatarDropdown').classList.toggle('open');
        }
        document.addEventListener('click', function(e) {
            const wrap = document.querySelector('.avatar-dropdown-wrap');
            if (wrap && !wrap.contains(e.target)) {
                const dd = document.getElementById('avatarDropdown');
                if (dd) dd.classList.remove('open');
            }
        });


        /* ── Rider Modal ── */
        function openRiderModal() {
            document.getElementById('riderModal').classList.add('open');
        }

        function closeRiderModal(e) {
            if (e.target === document.getElementById('riderModal')) closeRiderModalDirect();
        }

        function closeRiderModalDirect() {
            document.getElementById('riderModal').classList.remove('open');
        }

        /* ── Report Modal ── */
        function openReportModal() {
            document.getElementById('reportModal').classList.add('open');
        }

        function closeReportModal(e) {
            if (e.target === document.getElementById('reportModal')) closeReportModalDirect();
        }

        function closeReportModalDirect() {
            closeCamera(); // ← add this line at the top
            document.getElementById('reportModal').classList.remove('open');
            document.getElementById('selectDisplay').textContent = 'Select an issue';
            document.getElementById('selectDisplay').style.color = '#aaa';
            document.getElementById('selectWrapper').querySelectorAll('.dropdown-option').forEach(o => o.classList.remove('selected'));
            document.getElementById('reportTextarea').value = '';
            document.getElementById('charCount').textContent = '0/500';
            document.getElementById('previewList').innerHTML = '';
            document.getElementById('attachPopup').classList.remove('open');
            document.getElementById('selectWrapper').classList.remove('open');
        }

        /* ── Custom dropdown ── */
        function toggleDropdown() {
            document.getElementById('selectWrapper').classList.toggle('open');
        }

        function selectIssue(value) {
            const display = document.getElementById('selectDisplay');
            display.textContent = value;
            display.style.color = '#1e1e1e';
            document.getElementById('selectWrapper').classList.remove('open');
            document.getElementById('customDropdown').querySelectorAll('.dropdown-option').forEach(o => {
                o.classList.toggle('selected', o.textContent === value);
            });
        }

        document.addEventListener('click', function(e) {
            const wrap = document.getElementById('selectWrapper');
            if (wrap && !wrap.contains(e.target)) wrap.classList.remove('open');
        });

        /* ── Char count ── */
        function updateCharCount(el) {
            document.getElementById('charCount').textContent = el.value.length + '/500';
        }

        /* ── Attach popup ── */
        function toggleAttachPopup(e) {
            e.stopPropagation();
            document.getElementById('attachPopup').classList.toggle('open');
        }

        document.addEventListener('click', function(e) {
            const p = document.getElementById('attachPopup');
            if (p && !p.parentElement.contains(e.target)) p.classList.remove('open');
        });




        /* ── Submit report ── */
        function submitReport() {
            const issue = document.getElementById('selectDisplay').textContent;
            if (issue === 'Select an issue') {
                alert('Please select an issue first.');
                return;
            }
            alert('Report submitted! We will look into this shortly.');
            closeReportModalDirect();
        }
        /* ── Camera (getUserMedia) ── */
        let cameraStream = null;

        async function triggerCamera() {
            document.getElementById('attachPopup').classList.remove('open');

            const overlay = document.getElementById('cameraModal');
            const video = document.getElementById('cameraVideo');
            const errEl = document.getElementById('cameraError');
            const capBtn = document.getElementById('captureBtn');

            errEl.style.display = 'none';
            capBtn.style.display = 'flex';
            overlay.classList.add('open');

            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment',
                        width: {
                            ideal: 1280
                        },
                        height: {
                            ideal: 720
                        }
                    },
                    audio: false
                });
                video.srcObject = cameraStream;
            } catch (err) {
                // Fallback: try any camera if rear-facing fails
                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: true,
                        audio: false
                    });
                    video.srcObject = cameraStream;
                } catch (err2) {
                    errEl.textContent = 'Camera access denied or not available. Please allow camera permission and try again.';
                    errEl.style.display = 'block';
                    capBtn.style.display = 'none';
                }
            }
        }

        function capturePhoto() {
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const dataURL = canvas.toDataURL('image/jpeg', 0.9);
            addPreviewThumb(dataURL);

            closeCamera();
        }

        function closeCamera() {
            document.getElementById('cameraModal').classList.remove('open');
            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
                cameraStream = null;
            }
            document.getElementById('cameraVideo').srcObject = null;
        }

        /* ── Upload ── */
        function triggerUpload() {
            document.getElementById('attachPopup').classList.remove('open');
            document.getElementById('uploadInput').click();
        }

        function handleFileSelect(input) {
            Array.from(input.files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = e => addPreviewThumb(e.target.result);
                reader.readAsDataURL(file);
            });
            input.value = '';
        }

        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightboxOverlay').classList.add('open');
        }

        function closeLightbox() {
            document.getElementById('lightboxOverlay').classList.remove('open');
            document.getElementById('lightboxImg').src = '';
        }

        /* ── Updated shared preview helper ── */
        function addPreviewThumb(src) {
            const list = document.getElementById('previewList');
            const thumb = document.createElement('div');
            thumb.className = 'preview-thumb';
            thumb.innerHTML = `
                <img src="${src}" alt="attachment" onclick="openLightbox('${src}')" style="cursor:zoom-in;">
                <button class="preview-remove" onclick="this.parentElement.remove()" title="Remove">&times;</button>
            `;
            list.appendChild(thumb);
        }
    </script>

</body>

</html>