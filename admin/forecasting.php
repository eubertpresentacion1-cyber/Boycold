<?php
require_once '../config/db_config.php';

// Get branch filter
$branchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : 'all';

// Fetch available branches
$branchesQuery = "SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name";
$branchesResult = $connect->query($branchesQuery);
$branches = [];
while ($row = $branchesResult->fetch_assoc()) {
    $branches[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/forecasting.css">
    <link rel="icon" href="../POS/img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <title>BoyCold - Forecasting</title>
</head>

<body>
    <div class="app-shell">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true">
                    <img src="../POS/img/ChatGPT Image Jun 23, 2026, 09_22_57 PM 1.png" alt="">
                </span>
                <span class="brand-text">
                    <span class="brand-name">BoyCold Cafe</span>
                    <span class="brand-sub">Administration Panel</span>
                </span>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-top">
                    <ul>
                        <li>
                            <a href="dashboard.php">
                                <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z" fill="currentColor"/></svg></span>
                                <span class="nav-label">Dashboard</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z" stroke="currentColor" stroke-width="2"/><path d="M5.62939 6.78662H12.5735M5.62939 11.416H12.5735M5.62939 16.0454H10.2588" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <span class="nav-label">Orders</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="data-analytics.php">
                                <span class="nav-icon2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.8601 4.39V19.39C15.8601 21.06 17.0001 22 18.2501 22C19.3901 22 20.6401 21.21 20.6401 19.39V4.5C20.6401 2.96 19.5001 2 18.2501 2C17.0001 2 15.8601 3.06 15.8601 4.39ZM9.61011 12V19.39C9.61011 21.07 10.7701 22 12.0001 22C13.1401 22 14.3901 21.21 14.3901 19.39V12.11C14.3901 10.57 13.2501 9.61 12.0001 9.61C10.7501 9.61 9.61011 10.67 9.61011 12ZM5.75011 17.23C7.07011 17.23 8.14011 18.3 8.14011 19.61C8.14011 20.2439 7.88831 20.8518 7.44009 21.3C6.99188 21.7482 6.38398 22 5.75011 22C5.11624 22 4.50833 21.7482 4.06012 21.3C3.61191 20.8518 3.36011 20.2439 3.36011 19.61C3.36011 18.3 4.43011 17.23 5.75011 17.23Z" fill="white"/></svg></span>
                                <span class="nav-label">Data Analytics</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="forecasting.php" class="active">
                                <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.3751 6C21.0698 6.00008 20.7692 6.0747 20.4993 6.21737C20.2294 6.36005 19.9984 6.56647 19.8264 6.81869C19.6545 7.07092 19.5467 7.36132 19.5124 7.66468C19.4782 7.96803 19.5185 8.27516 19.6299 8.55938L15.6845 12.5048C15.2447 12.3317 14.7556 12.3317 14.3157 12.5048L11.4953 9.68438C11.6069 9.40009 11.6475 9.09283 11.6134 8.78931C11.5792 8.48579 11.4715 8.1952 11.2995 7.94281C11.1275 7.69042 10.8964 7.48387 10.6264 7.34113C10.3563 7.19839 10.0555 7.12377 9.75011 7.12377C9.44467 7.12377 9.14386 7.19839 8.87384 7.34113C8.60381 7.48387 8.37274 7.69042 8.20073 7.94281C8.02872 8.1952 7.92096 8.48579 7.88684 8.78931C7.85272 9.09283 7.89327 9.40009 8.00495 9.68438L3.30948 14.3798C2.90848 14.2225 2.46554 14.2081 2.05514 14.339C1.64474 14.4698 1.29192 14.738 1.056 15.0984C0.82007 15.4588 0.715432 15.8895 0.759675 16.3179C0.803918 16.7464 0.994344 17.1466 1.29893 17.4512C1.60352 17.7558 2.0037 17.9462 2.43218 17.9904C2.86065 18.0347 3.2913 17.93 3.6517 17.6941C4.0121 17.4582 4.28028 17.1054 4.41114 16.695C4.542 16.2846 4.52757 15.8416 4.37026 15.4406L9.06573 10.7452C9.50556 10.9183 9.99466 10.9183 10.4345 10.7452L13.2549 13.5656C13.1433 13.8499 13.1027 14.1572 13.1368 14.4607C13.171 14.7642 13.2787 15.0548 13.4507 15.3072C13.6227 15.5596 13.8538 15.7661 14.1238 15.9089C14.3939 16.0516 14.6947 16.1262 15.0001 16.1262C15.3055 16.1262 15.6063 16.0516 15.8764 15.9089C16.1464 15.7661 16.3775 15.5596 16.5495 15.3072C16.7215 15.0548 16.8293 14.7642 16.8634 14.4607C16.8975 14.1572 16.8569 13.8499 16.7453 13.5656L20.6907 9.62016C20.9475 9.72102 21.2233 9.76399 21.4986 9.74601C21.7738 9.72803 22.0417 9.64953 22.2832 9.51613C22.5246 9.38272 22.7336 9.19768 22.8953 8.97421C23.0571 8.75073 23.1675 8.49433 23.2187 8.22329C23.2699 7.95225 23.2607 7.67324 23.1918 7.40616C23.1228 7.13907 22.9957 6.8905 22.8197 6.67816C22.6436 6.46582 22.4228 6.29495 22.1731 6.17773C21.9234 6.0605 21.651 5.99982 21.3751 6Z" fill="white"/></svg></span>
                                <span class="nav-label">Forecasting</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="inventory.php">
                                <span class="nav-icon"><svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.9126 20.6502V9.48774C25.9136 9.34411 25.8793 9.20245 25.8126 9.07524C25.7205 8.87674 25.5611 8.71729 25.3626 8.62524L15.3626 4.15024C15.2409 4.09499 15.1088 4.06641 14.9751 4.06641C14.8414 4.06641 14.7093 4.09499 14.5876 4.15024L4.5876 8.62524C4.42677 8.70617 4.29077 8.82903 4.19397 8.98084C4.09716 9.13265 4.04314 9.30778 4.0376 9.48774V20.5127C4.04694 20.6918 4.10252 20.8653 4.19891 21.0165C4.2953 21.1676 4.42922 21.2912 4.5876 21.3752L14.5876 25.8502C14.7086 25.908 14.841 25.9379 14.9751 25.9379C15.1092 25.9379 15.2416 25.908 15.3626 25.8502L25.3626 21.3752C25.507 21.3091 25.6327 21.2083 25.7287 21.0818C25.8247 20.9553 25.8878 20.8071 25.9126 20.6502ZM5.9126 10.9252L14.0376 14.5752V23.5502L5.9126 19.9127V10.9252ZM15.9126 14.5752L24.0376 10.9252V19.9127L15.9126 23.5502V14.5752ZM15.0001 6.02524L22.7126 9.48774L15.0001 12.9377L7.2876 9.48774L15.0001 6.02524Z" fill="white"/></svg></span>
                                <span class="nav-label">Inventory</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="mapping.php">
                                <span class="nav-icon"><svg width="27" height="27" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.1154 4.13114C22.9044 3.92024 22.6183 3.80176 22.32 3.80176C22.0217 3.80176 21.7356 3.92024 21.5246 4.13114L15.4901 10.1656H2.25V12.4156H2.259C2.50425 18.4119 7.443 23.1988 13.5 23.1988C19.557 23.1988 24.4958 18.4119 24.741 12.4156H24.75V10.1656H18.6716L23.1154 5.72189C23.3263 5.51092 23.4448 5.22483 23.4448 4.92652C23.4448 4.62821 23.3263 4.34211 23.1154 4.13114ZM15.9491 12.4156H22.4888C22.3733 14.7218 21.3759 16.8954 19.7029 18.4869C18.0298 20.0783 15.8091 20.9658 13.5 20.9658C11.1909 20.9658 8.97019 20.0783 7.29713 18.4869C5.62406 16.8954 4.62667 14.7218 4.51125 12.4156H15.9491Z" fill="white"/></svg></span>
                                <span class="nav-label">Ingredients Mapping</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                    </ul>

                    <div class="sidebar-divider"></div>

                    <ul>
                        <li>
                            <a href="#">
                                <span class="nav-icon"><i class="fa-solid fa-bars"></i></span>
                                <span class="nav-label">Menu Management</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                                <span class="nav-label">Customers</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <span class="nav-icon"><svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.75 8.75C0.75 4.979 0.75 3.093 1.922 1.922C3.094 0.751 4.979 0.75 8.75 0.75H12.75C16.521 0.75 18.407 0.75 19.578 1.922C20.749 3.094 20.75 4.979 20.75 8.75C20.75 12.521 20.75 14.407 19.578 15.578C18.406 16.749 16.521 16.75 12.75 16.75H8.75C4.979 16.75 3.093 16.75 1.922 15.578C0.751 14.406 0.75 12.521 0.75 8.75Z" stroke="currentColor" stroke-width="1.5"/><path d="M8.75 12.75H4.75M12.75 12.75H11.25M0.75 6.75H20.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>
                                <span class="nav-label">Loyalty Card</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                    </ul>

                    <div class="sidebar-divider"></div>

                    <ul>
                        <li>
                            <a href="#">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-gear"></i>
                                </span>
                                <span class="nav-label">Settings</span>
                                <i class="fa-solid fa-chevron-right nav-chevron"></i>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="logout-link">
                                <span class="nav-icon">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </span>
                                <span class="nav-label">Log Out</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- MAIN PANEL -->
        <div class="main-panel">

            <div class="top-header">

                <div class="notif-wrap">
                    <button class="header-action" id="notifBtn" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <span>Notification</span>
                        <span class="header-badge" id="notifBadge">2</span>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <span class="notif-title">Notifications</span>
                            <a href="#" class="notif-mark-read" id="markAllRead">Mark all as read</a>
                        </div>

                        <div class="notif-list" id="notifList">
                            <div class="notif-item unread">
                                <div class="notif-icon notif-icon-bag"><i class="fa-solid fa-bag-shopping"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">New online order received</p>
                                    <p class="notif-item-sub">Order #0001</p>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>

                            <div class="notif-item unread">
                                <div class="notif-icon notif-icon-card"><i class="fa-solid fa-credit-card"></i></div>
                                <div class="notif-content">
                                    <p class="notif-item-title">Payment Confirmed</p>
                                    <p class="notif-item-sub">Order #0003</p>
                                </div>
                                <div class="notif-time">
                                    <span class="notif-time-main">10:30 am</span>
                                    <span class="notif-time-sub">Just now</span>
                                </div>
                            </div>
                        </div>

                        <a href="notification.html" class="notif-footer">
                            View all notifications <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="header-divider"></div>

                <button class="header-action" id="alertsBtn" aria-label="Alerts">
                    <svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18.8402 4.45947L31.0718 25.6443C31.2583 25.9673 31.3564 26.3338 31.3564 26.7068C31.3565 27.0798 31.2583 27.4462 31.0718 27.7693C30.8853 28.0923 30.617 28.3606 30.294 28.5471C29.9709 28.7336 29.6045 28.8318 29.2315 28.8318H4.7685C4.39549 28.8318 4.02905 28.7336 3.70602 28.5471C3.38299 28.3606 3.11474 28.0923 2.92824 27.7693C2.74174 27.4462 2.64355 27.0798 2.64355 26.7068C2.64356 26.3338 2.74175 25.9673 2.92825 25.6443L15.1598 4.45947C15.9772 3.04281 18.0214 3.04281 18.8402 4.45947ZM17 6.93864L5.99533 25.9985H28.0047L17 6.93864ZM17 21.2498C17.3757 21.2498 17.7361 21.3991 18.0017 21.6647C18.2674 21.9304 18.4167 22.2907 18.4167 22.6665C18.4167 23.0422 18.2674 23.4025 18.0017 23.6682C17.7361 23.9339 17.3757 24.0831 17 24.0831C16.6243 24.0831 16.2639 23.9339 15.9983 23.6682C15.7326 23.4025 15.5833 23.0422 15.5833 22.6665C15.5833 22.2907 15.7326 21.9304 15.9983 21.6647C16.2639 21.3991 16.6243 21.2498 17 21.2498ZM17 11.3331C17.3757 11.3331 17.7361 11.4824 18.0017 11.7481C18.2674 12.0137 18.4167 12.3741 18.4167 12.7498V18.4165C18.4167 18.7922 18.2674 19.1525 18.0017 19.4182C17.7361 19.6839 17.3757 19.8331 17 19.8331C16.6243 19.8331 16.2639 19.6839 15.9983 19.4182C15.7326 19.1525 15.5833 18.7922 15.5833 18.4165V12.7498C15.5833 12.3741 15.7326 12.0137 15.9983 11.7481C16.2639 11.4824 16.6243 11.3331 17 11.3331Z" fill="black" fill-opacity="0.8"/>
                    </svg>
                    <span>Alerts</span>
                    <span class="header-badge">2</span>
                </button>

                <div class="header-divider"></div>

                <button class="profile-btn">
                    <div class="profile-avatar">
                        <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.75737 25.5989C10.0298 24.6259 11.452 23.8589 13.0238 23.298C14.5957 22.7372 16.2424 22.4562 17.964 22.4552C19.6855 22.4542 21.3322 22.7352 22.9041 23.298C24.4759 23.8609 25.8981 24.6279 27.1705 25.5989C28.0438 24.576 28.7239 23.4158 29.211 22.1184C29.698 20.821 29.941 19.4363 29.94 17.9642C29.94 14.6458 28.7738 11.82 26.4415 9.48666C24.1092 7.15332 21.2833 5.98715 17.964 5.98815C14.6446 5.98915 11.8187 7.15582 9.48641 9.48815C7.15408 11.8205 5.98791 14.6458 5.98791 17.9642C5.98791 19.4363 6.23142 20.821 6.71845 22.1184C7.20547 23.4158 7.88512 24.576 8.75737 25.5989ZM14.2409 17.9447C13.2299 16.9358 12.7244 15.6947 12.7244 14.2217C12.7244 12.7486 13.2299 11.5071 14.2409 10.4971C15.2519 9.48715 16.4929 8.98216 17.964 8.98216C19.435 8.98216 20.6765 9.48765 21.6885 10.4986C22.7005 11.5096 23.2055 12.7506 23.2035 14.2217C23.2015 15.6927 22.6965 16.9343 21.6885 17.9462C20.6805 18.9582 19.439 19.4632 17.964 19.4612C16.4889 19.4592 15.2474 18.9542 14.2394 17.9462M17.964 32.9343C15.8931 32.9343 13.947 32.541 12.1256 31.7546C10.3043 30.9682 8.71995 29.9018 7.37264 28.5555C6.02534 27.2092 4.95897 25.6249 4.17354 23.8025C3.38811 21.9802 2.9949 20.0341 2.9939 17.9642C2.9929 15.8943 3.38611 13.9482 4.17354 12.1259C4.96096 10.3035 6.02733 8.71919 7.37264 7.37288C8.71795 6.02658 10.3023 4.96021 12.1256 4.17378C13.949 3.38735 15.8951 2.99414 17.964 2.99414C20.0328 2.99414 21.9789 3.38735 23.8023 4.17378C25.6256 4.96021 27.21 6.02658 28.5553 7.37288C29.9006 8.71919 30.9675 10.3035 31.7559 12.1259C32.5443 13.9482 32.937 15.8943 32.934 17.9642C32.931 20.0341 32.5378 21.9802 31.7544 23.8025C30.9709 25.6249 29.9046 27.2092 28.5553 28.5555C27.206 29.9018 25.6216 30.9687 23.8023 31.7561C21.9829 32.5435 20.0368 32.9363 17.964 32.9343Z" fill="black"/>
                        </svg>
                    </div>
                    <div class="profile-info">
                        <span class="profile-role">Admin</span>
                    </div>
                </button>
            </div>

            <!-- PAGE CONTENT -->
            <div class="page-content">

                <div class="page-heading">
                    <h1 class="page-title">Forecasting Analytics</h1>
                    <p class="page-subtitle">Predict future sales, demand, and inventory needs using historical data.</p>
                    
                    <div class="analytics-filters">
                        <div class="branch-filter">
                            <div class="branch-dropdown">
                                <button type="button" class="branch-trigger">
                                    <span class="branch-trigger-label"><?php echo $branchId === 'all' ? 'All Branches' : ($branches[array_search($branchId, array_column($branches, 'id'))]['branch_name'] ?? 'All Branches'); ?></span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                                <div class="branch-menu">
                                    <button type="button" class="branch-option" data-value="all">All Branches</button>
                                    <?php foreach ($branches as $branch): ?>
                                        <button type="button" class="branch-option" data-value="<?php echo $branch['id']; ?>">
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="last-update-badge" id="lastUpdateBadge">
                            <i class="fa-solid fa-circle" style="color: #4CAF50; font-size: 8px;"></i>
                            <span id="lastUpdateText">Live</span>
                        </div>
                    </div>
                </div>

                <div class="stats-grid">

                    <div class="stat-card" id="predictedSalesCard">
                        <div class="stat-card-top">
                            <span class="stat-label">Predicted Sales (14 Days)</span>
                            <span class="stat-icon stat-icon-peach"><i class="fa-solid fa-chart-column"></i></span>
                        </div>
                        <div class="stat-value" id="predictedSalesValue">₱ 0.00</div>
                        <div class="stat-trend" id="predictedSalesTrend">
                            <i class="fa-solid fa-arrow-up"></i>
                            <span class="trend-percent" id="predictedSalesPercent">0%</span>
                            <span class="trend-note">vs last 14 days</span>
                        </div>
                    </div>

                    <div class="stat-card" id="restockCard">
                        <div class="stat-card-top">
                            <span class="stat-label">Ingredients to Restock</span>
                            <span class="stat-icon stat-icon-orange"><i class="fa-solid fa-bag-shopping"></i></span>
                        </div>
                        <div class="stat-value" id="restockCount">0</div>
                        <div class="restock-status">
                            <span class="restock-critical" id="restockCritical">0 Critical</span>
                            <span class="restock-dot">•</span>
                            <span class="restock-soon" id="restockSoon">0 Soon</span>
                        </div>
                    </div>

                    <div class="stat-card" id="highestDemandCard">
                        <div class="stat-card-top">
                            <span class="stat-label">Highest Demand Item</span>
                            <span class="stat-icon stat-icon-purple"><i class="fa-regular fa-star"></i></span>
                        </div>
                        <div class="stat-value stat-value-name" id="highestDemandItem">Loading...</div>
                        <div class="forecast-note" id="highestDemandQty">0 orders (forecast)</div>
                    </div>

                </div>
                <div class="single-row">
                    <div class="chart-card full-width-card" id="salesForecastCard">
                        <div class="chart-card-header">
                            <h2 class="chart-card-title">Sales Forecast</h2>
                            <div class="chart-period-select">
                                <div class="date-range-picker">
                                    <div class="period-dropdown">
                                        <button type="button" class="period-trigger">
                                            <span class="period-trigger-label">Last 28 Days</span>
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </button>
                                        <div class="period-menu">
                                            <button type="button" class="period-option" data-value="14">Last 14 Days</button>
                                            <button type="button" class="period-option" data-value="28">Last 28 Days</button>
                                            <button type="button" class="period-option" data-value="60">Last 60 Days</button>
                                            <button type="button" class="period-option" data-value="90">Last 90 Days</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="chart-legend forecast-legend-row">
                            <div class="forecast-legend">
                                <span class="legend-item">
                                    <span class="legend-line legend-line-solid"></span>
                                    Historical Sales
                                </span>
                                <span class="legend-item">
                                    <span class="legend-line legend-line-dashed"></span>
                                    Forecasted Sales
                                </span>
                            </div>
                        </div>

                        <div class="chart-canvas-wrap forecast-canvas-wrap">
                            <canvas id="salesForecastChart"></canvas>
                        </div>

                        <div class="forecast-footer-note" id="forecastFooterNote">
                            <span class="forecast-footer-icon"><i class="fa-solid fa-chart-simple"></i></span>
                            <span id="forecastFooterText">Loading forecast data...</span>
                        </div>
                    </div>
                </div>
                <div class="secondary-grid">

                    <div class="chart-card demand-forecast-card">
                        <div class="chart-card-header">
                            <h2 class="chart-card-title">Demand Forecast (Top Menu Items)</h2>
                            <a href="#" class="view-all-link">View All</a>
                        </div>

                        <div class="demand-table" id="demandTable">
                            <div class="demand-table-head">
                                <span class="demand-col-item">Menu Items</span>
                                <span class="demand-col-orders">Predicted Orders</span>
                                <span class="demand-col-trend">Trend</span>
                            </div>
                            <div class="demand-loading">Loading demand data...</div>
                        </div>
                    </div>
                    <div class="chart-card peak-hours-forecast-card">
                        <div class="chart-card-header">
                            <h2 class="chart-card-title">Predicted Peak Hours</h2>
                        </div>

                        <div class="peak-table" id="peakHoursTable">
                            <div class="peak-table-head">
                                <span class="peak-col-time">Time</span>
                                <span class="peak-col-traffic">Expected Traffic</span>
                            </div>
                            <div class="peak-loading">Loading peak hours data...</div>
                        </div>
                    </div>
                    <div class="chart-card trending-drinks-card">
                        <div class="chart-card-header">
                            <h2 class="chart-card-title">Trending Drinks Prediction</h2>
                            <a href="#" class="view-all-link">View All</a>
                        </div>

                        <div class="trending-list" id="trendingList">
                            <div class="trending-loading">Loading trending data...</div>
                        </div>
                    </div>

                </div>
                <div class="insights-card">
                    <h2 class="insights-title">Insights</h2>

                    <div class="insights-grid" id="insightsGrid">
                        <div class="insight-loading">Loading insights...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Image filename mapping for products
        const imageMapping = {
            'French Vanilla': 'Franch Vanilla.png',
            'Messy Tuna Spinach': 'Messy Tuna Spinach.png',
            'Chicken Quesadilla': 'Chicken Quesadilla.png',
            'Beef Quesadilla': 'Beef Quesadilla.png',
            'Messy Tuna Quesadilla': 'Messy Tuna Spinach.png',
            'hershey delight': 'hershey delight.png',
            'Hershey Delight Frappe': 'hershey delight.png',
            'White Smores': 'white smores.png',
            'Choco Banana Pudding': 'Choco Banana Pudding.png',
            'Matcha banana Pudding': 'Matcha banana Pudding.png',
            'Mango graham': 'Mango graham.png',
            'Mango matcha': 'Mango matcha.png',
            'mango oreo': 'mango oreo.png',
            'Berry mango': 'Berry mango.png',
            'Berry Caramel Bliss': 'Berry Caramel Bliss.png',
            'Berry Oreo': 'Berry Oreo.png',
            'Choco Berry': 'Choco Berry.png',
            'Choco Vanilla Cookie': 'Choco Vanilla Cookie.png',
            'Choco Matcha': 'Choco Matcha.png',
            'Lavender Matcha': 'Lavender Matcha.png',
            'Lavander Matcha': 'Lavender Matcha.png',
            'Strawberry Matcha': 'Strawberry Matcha.png',
            'Seasalt Matcha': 'Seasalt Matcha.png',
            'Matcha Frappe': 'Matcha Frappe.png',
            'Matcha Freddo': 'Matcha Freddo.png',
            'Matcha banana Pudding': 'Matcha banana Pudding.png',
            'Lolly Matcha waffle': 'Matcha waffle.png',
            'Lolly Chocolate waffle': 'Chocolate waffle.png',
            'Lolly Biscoff waffle': 'Biscoff waffle.png',
            'Lolly Oreo waffle': 'Oreo waffle.png',
            'Lolly Strawberry waffle': 'Strawberry waffle.png',
            'Lolly tiramisu waffle': 'tiramisu waffle.png',
            'Lolly ube waffle': 'ube waffle.png',
            'Nuttela Hazelnut Frappe': 'Nuttela Hazelnut Frappe.png',
            'Cheesecake Frappe': 'Cheesecake Frappe.png',
            'Biscoff frappe': 'Biscoff frappe.png',
            'Caramel Frappe': 'Caramel Frappe.png',
            'Oreo Frappe': 'Oreo Frappe.png',
            'Java Chips': 'Java Chips.png',
            'Chicken poppers and fries': 'Chicken poppers and fries.png',
            'Beef Natchos': 'Beef Natchos.png',
            'Fries and Chicken Poppers': 'Chicken poppers and fries.png',
            'Chicken Poppers': 'Chicken Poppers.png',
            'French Fries': 'Fries.png',
            'Blueberry shake': 'BLUEBERRY SHAKE 1.png',
            'Strawberry shake': 'Strawberry shake.png',
            'Strawberry Milk': 'Strawberry Milk.png',
            'Blueberry Milk': 'Blueberry Milk.png',
            'White cocoa': 'White cocoa.png',
            'Milky Oreo': 'Milky Oreo.png',
            'Einspanner Latte': 'Einspanner Latte.png',
            'Butter scotch latte': 'Butter scotch latte.png',
            'Nutella Hazelnut latte': 'Nutella Hazelnut latte.png',
            'Salted Caramel': 'Salted Caramel.png',
            'Salted Macadamia': 'Salted Macadamia.png',
            'Salted Mango Dream': 'Salted Mango Dream.png',
            'Biscoff Creamy Latte': 'Biscoff Creamy Latte.png',
            'Cheesecake Latte': 'Cheesecake Latte.png',
            'Cheesecake Matcha': 'Cheesecake Matcha.png',
            'Sea Salt Latte': 'Sea salt Latte.png',
            'Sea salt Latte': 'Sea salt Latte.png',
            'Tiramisu Latte': 'Tiramisu Latte.png',
            'Hazelnut Latte': 'Hazelnut Latte.png',
            'Caramel Macchiato': 'Caramel Macchiato.png',
            'Spanish Latte': 'Spanish Latte.png',
            'Dark Mocha': 'Dark Mocha.png',
            'White Mocha': 'White Mocha.png',
            'Cafe Latte': 'Cafe Latte.png',
            'Pure matcha': 'Pure matcha.png',
            'Dirty Matcha': 'Dirty Matcha.png',
            'Matcha Latte': 'Matcha Latte.png'
        };

        function getProductImage(productName) {
            const name = imageMapping[productName] || productName + '.png';
            return '../POS/img/' + name;
        }

        // State
        let forecastChart = null;
        let currentBranchId = '<?php echo $branchId; ?>';
        let historicalDays = 28;
        let autoRefreshInterval = null;

        // ==========================================
        // FETCH FORECAST DATA
        // ==========================================
        async function fetchForecastData() {
            const url = `api/forecast_api.php?branch_id=${currentBranchId}&historical_days=${historicalDays}&forecast_days=14`;
            try {
                const response = await fetch(url);
                const data = await response.json();
                if (data.success) {
                    updateDashboard(data);
                    updateLastUpdateTime();
                }
                return data;
            } catch (error) {
                console.error('Forecast fetch error:', error);
                return null;
            }
        }

        // ==========================================
        // UPDATE DASHBOARD
        // ==========================================
        function updateDashboard(data) {
            // Stats cards
            document.getElementById('predictedSalesValue').textContent = '₱ ' + Number(data.stats.predicted_sales_next_14).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            const trendEl = document.getElementById('predictedSalesTrend');
            const trendPercent = data.stats.sales_change_percent;
            trendEl.className = 'stat-trend ' + (trendPercent >= 0 ? 'trend-up' : 'trend-down');
            trendEl.querySelector('i').className = 'fa-solid fa-arrow-' + (trendPercent >= 0 ? 'up' : 'down');
            trendEl.querySelector('.trend-percent').textContent = Math.abs(trendPercent) + '%';
            
            document.getElementById('restockCount').textContent = data.stats.critical_restocks + data.stats.soon_restocks;
            document.getElementById('restockCritical').textContent = data.stats.critical_restocks + ' Critical';
            document.getElementById('restockSoon').textContent = data.stats.soon_restocks + ' Soon';
            
            document.getElementById('highestDemandItem').textContent = data.stats.highest_demand_item;
            document.getElementById('highestDemandQty').textContent = data.stats.highest_demand_qty + ' orders (forecast)';

            // Sales Forecast Chart
            updateForecastChart(data);

            // Forecast footer note
            document.getElementById('forecastFooterText').innerHTML = 
                `Sales are expected to <strong>${trendPercent >= 0 ? 'increase' : 'decrease'}</strong> by <strong>${Math.abs(trendPercent)}%</strong> in the next 14 days.`;

            // Demand Forecast Table
            updateDemandTable(data.demand_forecast);

            // Peak Hours
            updatePeakHours(data.peak_hours);

            // Trending Items
            updateTrendingItems(data.trending_items);

            // Insights
            updateInsights(data.insights);
        }

        // ==========================================
        // UPDATE FORECAST CHART
        // ==========================================
        function updateForecastChart(data) {
            const canvas = document.getElementById('salesForecastChart');
            if (!canvas || typeof Chart === 'undefined') return;
            const ctx = canvas.getContext('2d');

            // Build labels: historical dates + forecast dates
            const labels = [];
            const historicalSales = [];
            const forecastedSales = [];

            // Historical data - push actual sales values
            data.historical_sales.forEach(item => {
                const d = new Date(item.date + 'T00:00:00');
                labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                historicalSales.push(item.sales);
                forecastedSales.push(undefined);
            });

            // Forecast data - push forecast values
            const lastDate = data.historical_sales.length > 0 
                ? new Date(data.historical_sales[data.historical_sales.length - 1].date + 'T00:00:00')
                : new Date();
            
            data.forecasted_sales.forEach((sale, i) => {
                const d = new Date(lastDate);
                d.setDate(d.getDate() + i + 1);
                labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                historicalSales.push(undefined);
                forecastedSales.push(sale);
            });

            const histCount = data.historical_sales.length;
            const totalCount = labels.length;

            // If we have very few historical points, duplicate last point to create a visible transition
            if (histCount > 0 && histCount < totalCount) {
                // Set the first forecast point to also have the last historical value for a smooth bridge
                // Actually let's make the last historical point also show on forecast dataset for continuity
                // Already handled by spanGaps
            }

            if (forecastChart) {
                forecastChart.destroy();
            }

            // Custom plugin: vertical divider line + "Historical" label pill
            const historicalDividerPlugin = {
                id: 'historicalDivider',
                afterDraw(chart) {
                    const { ctx, chartArea, scales, data: chartData } = chart;
                    if (histCount < 1 || histCount >= totalCount) return;
                    
                    // Draw divider exactly at the boundary
                    const x = scales.x.getPixelForValue(histCount - 0.5);

                    // Draw vertical dashed line
                    ctx.save();
                    ctx.setLineDash([6, 4]);
                    ctx.beginPath();
                    ctx.moveTo(x, chartArea.top);
                    ctx.lineTo(x, chartArea.bottom);
                    ctx.lineWidth = 2;
                    ctx.strokeStyle = '#8A5A2E';
                    ctx.stroke();
                    ctx.setLineDash([]);

                    // Draw "Forecast" pill label on the right side
                    const label = 'Forecast →';
                    ctx.font = "600 12px 'Afacad', sans-serif";
                    const textWidth = ctx.measureText(label).width;
                    const paddingX = 10;
                    const pillWidth = textWidth + paddingX * 2;
                    const pillHeight = 24;
                    const pillX = x + 8;
                    const pillY = chartArea.top + 6;

                    ctx.fillStyle = '#FFF1E6';
                    ctx.beginPath();
                    if (ctx.roundRect) {
                        ctx.roundRect(pillX, pillY, pillWidth, pillHeight, 12);
                    } else {
                        ctx.rect(pillX, pillY, pillWidth, pillHeight);
                    }
                    ctx.fill();

                    ctx.fillStyle = '#8A5A2E';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(label, pillX + paddingX, pillY + pillHeight / 2);
                    ctx.restore();
                }
            };

            // Build historical dataset - mask out forecast points
            const histData = [];
            const foreData = [];
            for (let i = 0; i < totalCount; i++) {
                if (i < histCount) {
                    histData.push(historicalSales[i]);
                    foreData.push(undefined);
                } else {
                    histData.push(undefined);
                    foreData.push(forecastedSales[i]);
                }
            }

            forecastChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Historical Sales',
                            data: histData,
                            borderColor: '#6B4B3E',
                            backgroundColor: 'rgba(107, 75, 62, 0.08)',
                            pointBackgroundColor: '#6B4B3E',
                            pointBorderColor: '#6B4B3E',
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointHitRadius: 10,
                            tension: 0.3,
                            fill: true,
                            spanGaps: true,
                            datalabels: { display: false }
                        },
                        {
                            label: 'Forecasted Sales',
                            data: foreData,
                            borderColor: '#E3B996',
                            backgroundColor: 'rgba(227, 185, 150, 0.08)',
                            pointBackgroundColor: '#E3B996',
                            pointBorderColor: '#E3B996',
                            borderWidth: 2.5,
                            borderDash: [7, 4],
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointHitRadius: 10,
                            tension: 0.3,
                            fill: true,
                            spanGaps: true,
                            datalabels: { display: false }
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    resizeDelay: 0,
                    animation: {
                        duration: 1200,
                        easing: 'easeInOutQuart'
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#322B2B',
                            padding: 12,
                            titleFont: { family: 'Afacad', size: 13, weight: '600' },
                            bodyFont: { family: 'Afacad', size: 14 },
                            callbacks: {
                                title: (items) => items[0].label,
                                label: (item) => {
                                    if (item.parsed.y == null) return null;
                                    return `${item.dataset.label}: ₱ ${Number(item.parsed.y).toLocaleString('en-US')}`;
                                }
                            }
                        },
                        datalabels: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#777',
                                font: { family: 'Afacad', size: 13 },
                                callback: (value) => `₱ ${(value / 1000).toFixed(0)}K`
                            },
                            grid: { color: '#eee' }
                        },
                        x: {
                            ticks: {
                                color: '#777',
                                font: { family: 'Afacad', size: 12 },
                                maxTicksLimit: 15,
                                autoSkip: true,
                                maxRotation: 45,
                                minRotation: 0
                            },
                            grid: { display: false }
                        }
                    },
                    elements: {
                        line: {
                            borderJoinStyle: 'round'
                        }
                    }
                },
                plugins: [historicalDividerPlugin]
            });
        }

        // ==========================================
        // UPDATE DEMAND TABLE
        // ==========================================
        function updateDemandTable(items) {
            const table = document.getElementById('demandTable');
            // Keep header
            const head = table.querySelector('.demand-table-head');
            table.innerHTML = '';
            table.appendChild(head);

            if (!items || items.length === 0) {
                table.innerHTML += '<div class="demand-row"><span class="demand-item-name" style="grid-column: span 4; text-align: center;">No demand data available</span></div>';
                return;
            }

            items.forEach((item, index) => {
                const progress = item.progress || 0;
                const trendClass = item.trend === 'increasing' ? 'trend-high' : 
                                  (item.trend === 'decreasing' ? 'trend-decreasing' : 'trend-stable');
                const trendIcon = item.trend_icon === 'arrow-up' ? 'fa-arrow-up' : 
                                 (item.trend_icon === 'arrow-down' ? 'fa-arrow-down' : 'fa-minus');
                const trendLabel = item.trend === 'increasing' ? 'High Increase' :
                                  (item.trend === 'decreasing' ? 'Decreasing' : 'Stable');

                const row = document.createElement('div');
                row.className = 'demand-row';
                row.innerHTML = `
                    <span class="demand-thumb"><img src="${getProductImage(item.product_name)}" alt="" onerror="this.src='../POS/img/icon.png'"></span>
                    <span class="demand-item-name">${item.product_name}</span>
                    <span class="demand-orders">${item.forecasted_quantity} cups</span>
                    <span class="demand-trend ${trendClass}">
                        <i class="fa-solid ${trendIcon}"></i> ${trendLabel}
                    </span>
                    <div class="item-progress-track demand-progress">
                        <div class="item-progress-fill" style="width: ${progress}%;"></div>
                    </div>
                `;
                table.appendChild(row);
            });
        }

        // ==========================================
        // UPDATE PEAK HOURS
        // ==========================================
        function updatePeakHours(hours) {
            const table = document.getElementById('peakHoursTable');
            const head = table.querySelector('.peak-table-head');
            table.innerHTML = '';
            table.appendChild(head);

            if (!hours || hours.length === 0) {
                table.innerHTML += '<div class="peak-row"><span class="peak-time" style="grid-column: span 3; text-align: center;">No peak hours data available</span></div>';
                return;
            }

            // Get current hour to highlight current time
            const currentHour = new Date().getHours();

            hours.forEach(hour => {
                const isCurrent = hour.hour === currentHour;
                const row = document.createElement('div');
                row.className = 'peak-row';
                row.innerHTML = `
                    <span class="peak-time ${isCurrent ? 'peak-time-current' : ''}">${hour.label}</span>
                    <div class="peak-track">
                        <div class="peak-fill" style="width: ${hour.progress}%; background: ${hour.color};"></div>
                    </div>
                    <span class="peak-traffic" style="color: ${hour.color};">${hour.traffic}</span>
                `;
                table.appendChild(row);
            });
        }

        // ==========================================
        // UPDATE TRENDING ITEMS
        // ==========================================
        function updateTrendingItems(items) {
            const list = document.getElementById('trendingList');
            list.innerHTML = '';

            if (!items || items.length === 0) {
                list.innerHTML = '<div class="trending-row"><span class="trending-name" style="text-align: center;">No trending data available</span></div>';
                return;
            }

            items.forEach(item => {
                const isUp = item.is_up;
                const row = document.createElement('div');
                row.className = `trending-row ${isUp ? 'trending-up' : ''}`;
                row.innerHTML = `
                    <span class="trending-thumb"><img src="${getProductImage(item.product_name)}" alt="" onerror="this.src='../POS/img/icon.png'"></span>
                    <div class="trending-content">
                        <span class="trending-name">${item.product_name}</span>
                        <p class="trending-desc">${item.recent_7} orders this week vs ${item.prev_7} last week.</p>
                    </div>
                    <span class="trending-percent ${isUp ? 'trending-percent-up' : 'trending-percent-down'}">
                        <i class="fa-solid fa-arrow-${isUp ? 'up' : 'down'}"></i> ${Math.abs(item.change_percent)}%
                    </span>
                `;
                list.appendChild(row);
            });
        }

        // ==========================================
        // UPDATE INSIGHTS
        // ==========================================
        function updateInsights(insights) {
            const grid = document.getElementById('insightsGrid');
            grid.innerHTML = '';

            if (!insights || insights.length === 0) {
                grid.innerHTML = '<div class="insight-item"><p>No insights available</p></div>';
                return;
            }

            insights.forEach((insight, index) => {
                const iconClass = insight.type === 'positive' ? 'insight-icon-green' :
                                 (insight.type === 'negative' ? 'insight-icon-red' :
                                 (insight.type === 'warning' ? 'insight-icon-orange' : 'insight-icon-purple'));

                const item = document.createElement('div');
                item.className = 'insight-item';
                item.innerHTML = `
                    <span class="insight-icon ${iconClass}">
                        <i class="fa-solid fa-${insight.icon}"></i>
                    </span>
                    <div class="insight-content">
                        <p class="insight-heading">${insight.heading}</p>
                        <p class="insight-desc">${insight.desc}</p>
                    </div>
                `;
                grid.appendChild(item);

                // Add divider between insights
                if (index < insights.length - 1) {
                    const divider = document.createElement('div');
                    divider.className = 'insight-divider';
                    grid.appendChild(divider);
                }
            });
        }

        // ==========================================
        // UPDATE LAST UPDATE TIME
        // ==========================================
        function updateLastUpdateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('lastUpdateText').textContent = 'Live • ' + timeStr;
        }

        // ==========================================
        // BRANCH DROPDOWN
        // ==========================================
        const branchTrigger = document.querySelector('.branch-trigger');
        const branchMenu = document.querySelector('.branch-menu');
        const branchOptions = document.querySelectorAll('.branch-option');

        branchTrigger?.addEventListener('click', (e) => {
            e.stopPropagation();
            branchMenu.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (!branchMenu.contains(e.target) && !branchTrigger.contains(e.target)) {
                branchMenu.classList.remove('open');
            }
        });

        branchOptions.forEach(option => {
            option.addEventListener('click', () => {
                const branchId = option.getAttribute('data-value');
                currentBranchId = branchId;
                branchTrigger.querySelector('.branch-trigger-label').textContent = option.textContent;
                branchMenu.classList.remove('open');
                // Refresh data
                fetchForecastData();
            });
        });

        // ==========================================
        // PERIOD DROPDOWN
        // ==========================================
        document.querySelectorAll('.period-dropdown').forEach(dropdown => {
            const trigger = dropdown.querySelector('.period-trigger');
            const triggerLabel = dropdown.querySelector('.period-trigger-label');
            const menu = dropdown.querySelector('.period-menu');

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                menu.classList.toggle('open');
            });

            menu.querySelectorAll('.period-option').forEach(option => {
                option.addEventListener('click', () => {
                    const days = parseInt(option.dataset.value);
                    historicalDays = days;
                    triggerLabel.textContent = option.textContent;
                    menu.classList.remove('open');
                    fetchForecastData();
                });
            });
        });

        document.addEventListener('click', (e) => {
            document.querySelectorAll('.period-dropdown').forEach(d => {
                if (!d.contains(e.target)) {
                    d.querySelector('.period-menu')?.classList.remove('open');
                }
            });
        });

        // ==========================================
        // SIDEBAR
        // ==========================================
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') e.preventDefault();
                document.querySelectorAll('.sidebar-nav a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        (function () {
            const sidebar = document.getElementById("sidebar");
            const backdrop = document.getElementById("sidebarBackdrop");
            const mq = window.matchMedia("(max-width: 860px)");

            function openSidebar() {
                if (!mq.matches) return;
                sidebar.classList.add("expanded");
                backdrop.classList.add("show");
            }

            function closeSidebar() {
                sidebar.classList.remove("expanded");
                backdrop.classList.remove("show");
            }

            sidebar.addEventListener("click", () => {
                if (mq.matches && !sidebar.classList.contains("expanded")) {
                    openSidebar();
                }
            });

            sidebar.querySelectorAll(".sidebar-nav a").forEach((link) => {
                link.addEventListener("click", () => {
                    if (mq.matches) closeSidebar();
                });
            });

            backdrop.addEventListener("click", closeSidebar);
            mq.addEventListener("change", closeSidebar);
        })();

        // ==========================================
        // NOTIFICATIONS
        // ==========================================
        const notifBtn = document.getElementById("notifBtn");
        const notifDropdown = document.getElementById("notifDropdown");
        const markAllRead = document.getElementById("markAllRead");
        const notifBadge = document.getElementById("notifBadge");
        const notifList = document.getElementById("notifList");

        notifBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle("open");
        });
        document.addEventListener("click", (e) => {
            if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                notifDropdown.classList.remove("open");
            }
        });
        markAllRead.addEventListener("click", (e) => {
            e.preventDefault();
            notifList.querySelectorAll(".notif-item.unread").forEach(item => item.classList.remove("unread"));
            if (notifBadge) notifBadge.style.display = "none";
        });

        // ==========================================
        // INIT
        // ==========================================
        document.addEventListener('DOMContentLoaded', () => {
            // Initial fetch
            fetchForecastData();

            // Auto-refresh every 30 seconds
            autoRefreshInterval = setInterval(fetchForecastData, 30000);
        });
    </script>
</body>
</html>