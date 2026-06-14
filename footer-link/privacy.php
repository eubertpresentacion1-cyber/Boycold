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
    <link rel="stylesheet" href="footer-css/privacy.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Privacy and Safety</title>
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
                        <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar">
                    <?php else: ?>
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon"></i>
                        <img id="sidebarAvatarImg" src="" alt="avatar" style="display:none;">
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
    </nav>


    <!-- MAIN CONTENT -->
    <main class="about-main">
        <section class="about-hero">
            <div class="privacy-header">
                <div class="hero-label">Privacy and Safety</div>
                <p class="hero-desc">
                    At Boycold Cafe, your trust means everything to us. We are committed
                    to protecting your personal information and creating a safe,<br class="hero-br">
                    respectful experience—both online and in our cafés.
                </p>
            </div>

            <div class="privacy-grid">

                <div class="privacy-item">
                    <div class="privacy-icon">
                        <svg width="84" height="84" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 63C14 59.287 15.475 55.726 18.1005 53.1005C20.726 50.475 24.287 49 28 49H56C59.713 49 63.274 50.475 65.8995 53.1005C68.525 55.726 70 59.287 70 63C70 64.8565 69.2625 66.637 67.9497 67.9497C66.637 69.2625 64.8565 70 63 70H21C19.1435 70 17.363 69.2625 16.0503 67.9497C14.7375 66.637 14 64.8565 14 63Z" stroke="#483121" stroke-width="3" stroke-linejoin="round"/>
                            <path d="M42 35C47.799 35 52.5 30.299 52.5 24.5C52.5 18.701 47.799 14 42 14C36.201 14 31.5 18.701 31.5 24.5C31.5 30.299 36.201 35 42 35Z" stroke="#483121" stroke-width="3"/>
                        </svg>
                    </div>
                    <div class="privacy-text">
                        <h3>Information We Collect</h3>
                        <p>We collect information you provide to us, such as your name, email address, phone number, and order details when you place an order, sign up for our newsletter, or interact with our website, app, or in-store services. We may also collect information automatically, including device and browsing data, to help us improve your experience.</p>
                    </div>
                </div>

                <div class="privacy-item">
                    <div class="other-icon">
                        <svg width="68" height="68" viewBox="0 0 68 68" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.91699 10.6248C9.91699 10.437 9.99162 10.2568 10.1245 10.124C10.2573 9.99113 10.4375 9.9165 10.6253 9.9165H48.8753C49.0632 9.9165 49.2434 9.99113 49.3762 10.124C49.509 10.2568 49.5837 10.437 49.5837 10.6248V38.9582C49.5837 39.5218 49.8075 40.0623 50.2061 40.4608C50.6046 40.8593 51.1451 41.0832 51.7087 41.0832C52.2722 41.0832 52.8128 40.8593 53.2113 40.4608C53.6098 40.0623 53.8337 39.5218 53.8337 38.9582V10.6248C53.8337 9.30981 53.3113 8.04863 52.3814 7.11877C51.4515 6.1889 50.1904 5.6665 48.8753 5.6665H10.6253C9.31029 5.6665 8.04912 6.1889 7.11925 7.11877C6.18939 8.04863 5.66699 9.30981 5.66699 10.6248V57.3748C5.66699 60.1118 7.88833 62.3332 10.6253 62.3332H30.4587C31.0222 62.3332 31.5627 62.1093 31.9613 61.7108C32.3598 61.3123 32.5837 60.7718 32.5837 60.2082C32.5837 59.6446 32.3598 59.1041 31.9613 58.7056C31.5627 58.3071 31.0222 58.0832 30.4587 58.0832H10.6253C10.4375 58.0832 10.2573 58.0085 10.1245 57.8757C9.99162 57.7429 9.91699 57.5627 9.91699 57.3748V10.6248Z" fill="#483121"/>
                            <path d="M17.708 19.8335C17.1444 19.8335 16.6039 20.0574 16.2054 20.4559C15.8069 20.8544 15.583 21.3949 15.583 21.9585C15.583 22.5221 15.8069 23.0626 16.2054 23.4611C16.6039 23.8596 17.1444 24.0835 17.708 24.0835H41.7913C42.3549 24.0835 42.8954 23.8596 43.2939 23.4611C43.6925 23.0626 43.9163 22.5221 43.9163 21.9585C43.9163 21.3949 43.6925 20.8544 43.2939 20.4559C42.8954 20.0574 42.3549 19.8335 41.7913 19.8335H17.708ZM15.583 33.2918C15.583 32.7282 15.8069 32.1877 16.2054 31.7892C16.6039 31.3907 17.1444 31.1668 17.708 31.1668H30.458C31.0216 31.1668 31.5621 31.3907 31.9606 31.7892C32.3591 32.1877 32.583 32.7282 32.583 33.2918C32.583 33.8554 32.3591 34.3959 31.9606 34.7944C31.5621 35.1929 31.0216 35.4168 30.458 35.4168H17.708C17.1444 35.4168 16.6039 35.1929 16.2054 34.7944C15.8069 34.3959 15.583 33.8554 15.583 33.2918ZM61.7097 46.1268C61.9185 45.9323 62.0859 45.6977 62.2021 45.437C62.3182 45.1764 62.3806 44.895 62.3857 44.6096C62.3907 44.3243 62.3382 44.0409 62.2314 43.7763C62.1245 43.5117 61.9654 43.2713 61.7636 43.0696C61.5618 42.8678 61.3215 42.7087 61.0569 42.6018C60.7923 42.4949 60.5089 42.4424 60.2235 42.4475C59.9382 42.4525 59.6568 42.515 59.3961 42.6311C59.1355 42.7473 58.9009 42.9147 58.7063 43.1235L44.6247 57.2052L39.043 51.6235C38.8485 51.4147 38.6139 51.2473 38.3532 51.1311C38.0925 51.015 37.8111 50.9525 37.5258 50.9475C37.2405 50.9425 36.9571 50.9949 36.6925 51.1018C36.4279 51.2087 36.1875 51.3678 35.9857 51.5696C35.7839 51.7713 35.6249 52.0117 35.518 52.2763C35.4111 52.5409 35.3586 52.8243 35.3637 53.1096C35.3687 53.395 35.4312 53.6764 35.5473 53.937C35.6634 54.1977 35.8309 54.4323 36.0397 54.6268L43.123 61.7102C43.5214 62.1081 44.0616 62.3316 44.6247 62.3316C45.1878 62.3316 45.7279 62.1081 46.1263 61.7102L61.7097 46.1268Z" fill="#483121"/>
                        </svg>
                    </div>
                    <div class="privacy-text">
                        <h3>How We Use Your Information</h3>
                        <p>We use your information to process orders, personalize your experience, provide customer support, send updates and promotions (with your consent), and improve our products and services. We may also use your data for analytics to understand how our customers interact with Boycold Cafe.</p>
                    </div>
                </div>

                <div class="privacy-item">
                    <div class="other-icon">
                        <svg width="59" height="67" viewBox="0 0 59 67" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.5 9.603L29.2639 1.5L57 9.603V26.2191C56.9989 34.7346 54.3188 43.0339 49.3393 49.9417C44.3597 56.8494 37.3331 62.0155 29.2546 64.7083C21.1731 62.0167 14.1437 56.8501 9.16224 49.9407C4.18082 43.0312 1.50012 34.7293 1.5 26.2114V9.603Z" stroke="#483121" stroke-width="3" stroke-linejoin="round"/>
                            <path d="M15.375 30.7918L26.1667 41.5835L44.6667 23.0835" stroke="#483121" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="privacy-text">
                        <h3>How We Protect Your Information</h3>
                        <p>We take your privacy seriously. We use industry-standard security measures to protect your information from unauthorized access, disclosure, alteration, or destruction. Our systems are regularly monitored, and access to personal data is limited to authorized team members only.</p>
                    </div>
                </div>

                <div class="privacy-item">
                    <div class="privacy-icon">
                        <svg width="91" height="91" viewBox="0 0 91 91" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9.84277 70.5859V64.341C9.84277 62.5817 10.2978 61.0688 11.2078 59.8024C12.1203 58.5385 13.3463 57.5274 14.8857 56.7691C18.1743 55.2018 21.4402 53.9531 24.6834 53.0229C27.924 52.0927 31.8243 51.6276 36.3844 51.6276C40.9445 51.6276 44.8462 52.0927 48.0893 53.0229C51.3325 53.9531 54.5983 55.2018 57.887 56.7691C59.4239 57.5274 60.6486 58.5385 61.5611 59.8024C62.4711 61.0688 62.9261 62.5817 62.9261 64.341V70.5859H9.84277ZM70.5094 70.5859V64.1666C70.5094 61.9674 70.0645 59.8934 69.1748 57.9445C68.2875 55.993 67.0262 54.3196 65.3907 52.9243C67.2537 53.3035 69.0484 53.8292 70.7749 54.5016C72.4988 55.1765 74.205 55.9336 75.8936 56.7728C77.5367 57.5994 78.8246 58.6573 79.7573 59.9465C80.6901 61.2356 81.1564 62.6423 81.1564 64.1666V70.5859H70.5094ZM28.3347 39.8431C26.1179 37.6262 25.0094 34.943 25.0094 31.7933C25.0094 28.6437 26.1179 25.9605 28.3347 23.7436C30.5516 21.5268 33.2348 20.4183 36.3844 20.4183C39.534 20.4183 42.2173 21.5268 44.4341 23.7436C46.651 25.9605 47.7594 28.6437 47.7594 31.7933C47.7594 34.943 46.651 37.6262 44.4341 39.8431C42.2173 42.0599 39.534 43.1683 36.3844 43.1683C33.2348 43.1683 30.5516 42.0599 28.3347 39.8431ZM60.6056 39.8431C58.3786 42.0599 55.7017 43.1683 52.5749 43.1683C52.4131 43.1683 52.2083 43.1494 51.9606 43.1115C51.7129 43.0735 51.5081 43.0344 51.3464 42.9939C52.633 41.4191 53.6201 39.6724 54.3076 37.7538C54.9977 35.8378 55.3428 33.8472 55.3428 31.782C55.3428 29.7168 54.9826 27.7451 54.2621 25.867C53.5392 23.9838 52.5673 22.2257 51.3464 20.5928C51.5511 20.5195 51.7559 20.4714 51.9606 20.4487C52.1654 20.4259 52.3701 20.4146 52.5749 20.4146C55.7017 20.4146 58.3786 21.523 60.6056 23.7398C62.8326 25.9567 63.9473 28.6399 63.9499 31.7896C63.9524 34.9392 62.8376 37.6237 60.6056 39.8431ZM13.6344 66.7942H59.1344V64.341C59.1344 63.4512 58.912 62.6739 58.4671 62.0091C58.0222 61.3443 57.2234 60.7073 56.0708 60.0981C53.2422 58.5865 50.2594 57.4288 47.1224 56.625C43.9855 55.8211 40.4061 55.4192 36.3844 55.4192C32.3627 55.4192 28.7821 55.8211 25.6426 56.625C22.5082 57.4288 19.5254 58.5865 16.6943 60.0981C15.5442 60.7048 14.7467 61.3418 14.3018 62.0091C13.8569 62.6739 13.6344 63.4512 13.6344 64.341V66.7942ZM41.7421 37.1472C43.2259 35.6634 43.9678 33.8788 43.9678 31.7933C43.9678 29.7079 43.2259 27.9221 41.7421 26.4357C40.2583 24.9494 38.4724 24.2075 36.3844 24.21C34.2965 24.2125 32.5119 24.9544 31.0306 26.4357C29.5493 27.917 28.8062 29.7029 28.8011 31.7933C28.7961 33.8838 29.5392 35.6684 31.0306 37.1472C32.522 38.6259 34.3066 39.3691 36.3844 39.3767C38.4623 39.3843 40.2481 38.6411 41.7421 37.1472Z" fill="#483121"/>
                        </svg>
                    </div>
                    <div class="privacy-text">
                        <h3>Sharing Your Information</h3>
                        <p>We do not sell your personal information. We may share your information with trusted service providers who help us operate our business—such as payment processors, delivery partners, and marketing platforms—but only to the extent necessary and under strict confidentiality obligations.</p>
                    </div>
                </div>

                <div class="privacy-item">
                    <div class="privacy-icon">
                        <svg width="84" height="84" viewBox="0 0 84 84" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M59.7665 36.8726C60.6603 37.7668 61.7215 38.4762 62.8896 38.9602C64.0576 39.4442 65.3096 39.6933 66.574 39.6933C67.8383 39.6933 69.0903 39.4442 70.2583 38.9602C71.4264 38.4762 72.4876 37.7668 73.3815 36.8726C73.826 36.4246 74.6485 36.6066 74.757 37.2296C75.9592 44.0121 74.9669 51.0013 71.9242 57.1811C68.8816 63.3609 63.9471 68.4092 57.8383 71.592C51.7295 74.7748 44.7648 75.9263 37.9566 74.879C31.1484 73.8317 24.8518 70.6402 19.982 65.7686C15.1103 60.8988 11.9188 54.6021 10.8715 47.7939C9.82424 40.9858 10.9757 34.021 14.1585 27.9122C17.3414 21.8034 22.3896 16.869 28.5694 13.8263C34.7493 10.7836 41.7384 9.79129 48.521 10.9936C49.144 11.1021 49.326 11.9246 48.878 12.3726C47.4496 13.8009 46.5068 15.6425 46.1831 17.6364C45.8594 19.6303 46.1713 21.6755 47.0745 23.4823C47.9777 25.2891 49.4265 26.766 51.2157 27.7037C53.0049 28.6414 55.0437 28.9924 57.0435 28.7071C56.8325 30.1861 56.9687 31.6939 57.4413 33.1112C57.9139 34.5284 58.71 35.8162 59.7665 36.8726Z" stroke="#483121" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M29.75 50.875C31.614 50.875 33.125 52.386 33.125 54.25C33.125 56.114 31.614 57.625 29.75 57.625C27.886 57.625 26.375 56.114 26.375 54.25C26.375 52.386 27.886 50.875 29.75 50.875Z" fill="#483121" stroke="#483121" stroke-width="2"/>
                            <path d="M26.25 29.875C28.114 29.875 29.625 31.386 29.625 33.25C29.625 35.114 28.114 36.625 26.25 36.625C24.386 36.625 22.875 35.114 22.875 33.25C22.875 31.386 24.386 29.875 26.25 29.875Z" fill="#483121" stroke="#483121" stroke-width="2"/>
                            <path d="M43.75 40.375C45.614 40.375 47.125 41.886 47.125 43.75C47.125 45.614 45.614 47.125 43.75 47.125C41.886 47.125 40.375 45.614 40.375 43.75C40.375 41.886 41.886 40.375 43.75 40.375Z" fill="#483121" stroke="#483121" stroke-width="2"/>
                            <path d="M54.25 54.375C56.114 54.375 57.625 55.886 57.625 57.75C57.625 59.614 56.114 61.125 54.25 61.125C52.386 61.125 50.875 59.614 50.875 57.75C50.875 55.886 52.386 54.375 54.25 54.375Z" fill="#483121" stroke="#483121" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="privacy-text">
                        <h3>Cookies & Technologies</h3>
                        <p>We use cookies and similar technologies to enhance your browsing experience, remember your preferences, and analyze site performance. You can manage or disable cookies through your browser settings at any time.</p>
                    </div>
                </div>

                <div class="privacy-item">
                    <div class="other-icon">
                        <svg width="68" height="68" viewBox="0 0 68 68" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M63.75 17H55.0375C53.975 12.1125 49.725 8.5 44.625 8.5C39.525 8.5 35.275 12.1125 34.2125 17H4.25V21.25H34.2125C35.275 26.1375 39.525 29.75 44.625 29.75C49.725 29.75 53.975 26.1375 55.0375 21.25H63.75V17ZM44.625 25.5C41.0125 25.5 38.25 22.7375 38.25 19.125C38.25 15.5125 41.0125 12.75 44.625 12.75C48.2375 12.75 51 15.5125 51 19.125C51 22.7375 48.2375 25.5 44.625 25.5ZM4.25 51H12.9625C14.025 55.8875 18.275 59.5 23.375 59.5C28.475 59.5 32.725 55.8875 33.7875 51H63.75V46.75H33.7875C32.725 41.8625 28.475 38.25 23.375 38.25C18.275 38.25 14.025 41.8625 12.9625 46.75H4.25V51ZM23.375 42.5C26.9875 42.5 29.75 45.2625 29.75 48.875C29.75 52.4875 26.9875 55.25 23.375 55.25C19.7625 55.25 17 52.4875 17 48.875C17 45.2625 19.7625 42.5 23.375 42.5Z" fill="#483121"/>
                        </svg>
                    </div>
                    <div class="privacy-text">
                        <h3>Your Choices</h3>
                        <p>You can update your information, opt out of marketing emails, or manage cookies through your browser settings at any time. If you have an account with us, you may also update your communication preferences in your profile.</p>
                    </div>
                </div>

            </div>

            <div class="privacy-safety-row">
                <div class="other-icon">
                    <svg width="74" height="74" viewBox="0 0 74 74" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.25 14.2695L37.0139 6.1665L64.75 14.2695V30.8856C64.7489 39.4011 62.0688 47.7004 57.0893 54.6082C52.1097 61.5159 45.0831 66.682 37.0046 69.3748C28.9231 66.6832 21.8937 61.5166 16.9122 54.6072C11.9308 47.6977 9.25012 39.3958 9.25 30.8779V14.2695Z" stroke="#483121" stroke-width="3" stroke-linejoin="round"/>
                        <path d="M23.125 35.4583L33.9167 46.25L52.4167 27.75" stroke="#483121" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="privacy-text">
                    <h3>Your Safety Matters</h3>
                    <p>If you ever have concerns about your data or privacy, we're here to help. Reach out to us anytime.</p>
                </div>
            </div>

            <div class="privacy-notice">
                <span class="privacy-notice-heart"><i class="fa-solid fa-heart"></i></span>
                <div>
                    <p>We may update this Privacy & Safety Policy from time to time. Any changes will be posted on this page with the updated effective date.</p>
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