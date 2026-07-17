<?php
session_start();
require_once '../config/db_config.php';

// Session guard
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Fetch ingredients from database
$ingredientsQuery = "SELECT id, name, unit, stock, branch_id FROM ingredients ORDER BY name";
$ingredientsResult = $connect->query($ingredientsQuery);
$ingredients = [];
while ($row = $ingredientsResult->fetch_assoc()) {
    $ingredients[] = $row;
}

// Fetch product ingredients mapping from database
$productIngredientsQuery = "SELECT pi.product_name, pi.ingredient_id, pi.amount, i.name as ingredient_name, i.unit 
                            FROM product_ingredients pi 
                            JOIN ingredients i ON pi.ingredient_id = i.id 
                            ORDER BY pi.product_name, i.name";
$productIngredientsResult = $connect->query($productIngredientsQuery);
$productIngredients = [];
while ($row = $productIngredientsResult->fetch_assoc()) {
    $productIngredients[] = $row;
}

// Get branch info for display
$branchId = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : 1;
$branchQuery = "SELECT branch_name FROM branches WHERE id = ?";
$branchStmt = $connect->prepare($branchQuery);
$branchStmt->bind_param('i', $branchId);
$branchStmt->execute();
$branchResult = $branchStmt->get_result()->fetch_assoc();
$branchName = $branchResult['branch_name'] ?? 'Main Branch';
$branchStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/mapping.css">
    <link rel="icon" href="/public/assets/icons/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Ingredients Mapping</title>
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
                            <a href="forecasting.php">
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
                            <a href="mapping.php" class="active">
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
                        <span class="profile-name"><?= htmlspecialchars($branchName) ?></span>
                        <span class="profile-role">Admin</span>
                    </div>
                    <i class="fa-solid fa-chevron-down profile-caret"></i>
                </button>
            </div>

            <!-- INGREDIENTS MAPPING CONTENT -->
            <div class="page-content">

                <div class="page-header">
                    <h1 class="page-title">Ingredients Mapping</h1>
                    <p class="page-subtitle">Assign ingredients to each menu item and set the required quantity for automatic inventory deduction</p>
                </div>

                <div class="mapping-grid">

                    <!-- LEFT: Select Menu Item -->
                    <section class="panel">
                        <div class="panel-header">
                            <span class="panel-title">Select Menu Item</span>
                        </div>
                        <div class="menu-search">
                            <input type="text" id="menuSearch" placeholder="Search menu item...">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <div class="menu-list" id="menuList"></div>
                        <button type="button" class="add-menu-item-btn" id="addMenuItemBtn">
                            <i class="fa-solid fa-plus"></i> Add New Menu Item
                        </button>
                    </section>

                    <!-- RIGHT: Map Ingredients -->
                    <section class="panel">
                        <div class="panel-header">
                            <span class="panel-title">Map Ingredients</span>
                            <label class="toggle-field">
                                Show inactive orders
                                <span class="switch">
                                    <input type="checkbox" id="showInactiveToggle">
                                    <span class="switch-slider"></span>
                                </span>
                            </label>
                        </div>
                        <div class="map-panel-body" id="mapPanel"></div>
                    </section>

                    <!-- INVENTORY STATUS -->
                    <section class="panel">
                        <div class="panel-header">
                            <span class="panel-title">Real-time Inventory Status</span>
                            <span class="refresh-indicator" id="refreshIndicator">
                                <i class="fa-solid fa-sync fa-spin"></i> Updating...
                            </span>
                        </div>
                        <div class="inventory-status-body" id="inventoryStatus">
                            <div class="loading-inventory">Loading inventory data...</div>
                        </div>
                    </section>

                </div>
            </div>
        </div>
    </div>
    <script>
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
        const MENU_ITEMS = [
            // Coffee
            { id: "americano", name: "Americano", price: 69, category: "Coffee", img: "Americano.png" },
            { id: "cafe-latte", name: "Cafe Latte", price: 85, category: "Coffee", img: "Cafe Latte.png" },
            { id: "spanish-latte", name: "Spanish Latte", price: 95, category: "Coffee", img: "Spanish Latte.png" },
            { id: "dirty-matcha", name: "Dirty Matcha", price: 119, category: "Coffee", img: "Dirty Matcha.png" },
            { id: "dark-mocha", name: "Dark Mocha", price: 139, category: "Coffee", img: "Dark Mocha.png" },
            { id: "white-mocha", name: "White Mocha", price: 129, category: "Coffee", img: "White Mocha.png" },
            { id: "french-vanilla", name: "French Vanilla", price: 135, category: "Coffee", img: "Franch Vanilla.png" },
            { id: "hazelnut-latte", name: "Hazelnut Latte", price: 135, category: "Coffee", img: "Hazelnut Latte.png" },

            // Non-Coffee
            { id: "strawberry-milk", name: "Strawberry Milk", price: 79, category: "Non-Coffee", img: "Strawberry Milk.png" },
            { id: "blueberry-milk", name: "Blueberry Milk", price: 79, category: "Non-Coffee", img: "Blueberry Milk.png" },
            { id: "milky-oreo", name: "Milky Oreo", price: 85, category: "Non-Coffee", img: "Milky Oreo.png" },
            { id: "white-cocoa", name: "White cocoa", price: 95, category: "Non-Coffee", img: "White cocoa.png" },
            { id: "choco-berry", name: "Choco Berry", price: 109, category: "Non-Coffee", img: "Choco Berry.png" },
            { id: "choco-vanilla-cookie", name: "Choco Vanilla Cookie", price: 129, category: "Non-Coffee", img: "Choco Vanilla Cookie.png" },
            { id: "choco-banana-pudding", name: "Choco Banana Pudding", price: 179, category: "Non-Coffee", img: "Choco Banna Pudding.png" },

            // Special Coffee
            { id: "sea-salt-latte", name: "Sea Salt Latte", price: 115, category: "Special Coffee", img: "Sea salt Latte.png" },
            { id: "salted-mango-dream", name: "Salted Mango Dream", price: 139, category: "Special Coffee", img: "Salted Mango Dream.png" },
            { id: "berry-caramel-bliss", name: "Berry Caramel Bliss", price: 139, category: "Special Coffee", img: "Berry Caramel Bliss.png" },
            { id: "caramel-macchiato", name: "Caramel Macchiato", price: 139, category: "Special Coffee", img: "Caramel Macchiato.png" },
            { id: "butterscotch-latte", name: "Butter Scotch Latte", price: 139, category: "Special Coffee", img: "Butter scotch latte.png" },
            { id: "salted-caramel", name: "Salted Caramel", price: 139, category: "Special Coffee", img: "Salted Caramel.png" },
            { id: "salted-macadamia", name: "Salted Macadamia", price: 139, category: "Special Coffee", img: "Salted Macadamia.png" },
            { id: "cheesecake-latte", name: "Cheesecake Latte", price: 149, category: "Special Coffee", img: "Cheesecake Latte.png" },
            { id: "einspanner-latte", name: "Einspanner Latte", price: 149, category: "Special Coffee", img: "Einspanner Latte.png" },
            { id: "biscoff-creamy-latte", name: "Biscoff Creamy Latte", price: 159, category: "Special Coffee", img: "Biscoff Creamy Latte.png" },
            { id: "nutella-hazelnut-latte", name: "Nutella Hazelnut Latte", price: 169, category: "Special Coffee", img: "Nutella Hazelnut latte.png" },
            { id: "tiramisu-latte", name: "Tiramisu Latte", price: 179, category: "Special Coffee", img: "Tiramisu Latte.png" },

            // Matcha Fusion
            { id: "pure-matcha", name: "Pure Matcha", price: 80, category: "Matcha Fusion", img: "Pure matcha.png" },
            { id: "matcha-latte", name: "Matcha Latte", price: 85, category: "Matcha Fusion", img: "Matcha Latte.png" },
            { id: "mango-matcha", name: "Mango Matcha", price: 89, category: "Matcha Fusion", img: "Mango matcha.png" },
            { id: "sea-salt-matcha", name: "Sea Salt Matcha", price: 95, category: "Matcha Fusion", img: "Seasalt Matcha.png" },
            { id: "matcha-freddo", name: "Matcha Freddo", price: 99, category: "Matcha Fusion", img: "Matcha Freddo.png" },
            { id: "choco-matcha", name: "Choco Matcha", price: 109, category: "Matcha Fusion", img: "Choco Matcha.png" },
            { id: "strawberry-matcha", name: "Strawberry Matcha", price: 115, category: "Matcha Fusion", img: "Strawberry Matcha.png" },
            { id: "cheesecake-matcha", name: "Cheesecake Matcha", price: 119, category: "Matcha Fusion", img: "Cheesecake Matcha.png" },
            { id: "lavender-matcha", name: "Lavander Matcha", price: 119, category: "Matcha Fusion", img: "Lavander Matcha.png" },
            { id: "matcha-banana-pudding", name: "Matcha Banana Pudding", price: 179, category: "Matcha Fusion", img: "Matcha banana Pudding.png" },

            // Fruit Shake
            { id: "mango-graham", name: "Mango Graham", price: 65, category: "Fruit Shake", img: "Mango graham.png" },
            { id: "strawberry-shake", name: "Strawberry Shake", price: 65, category: "Fruit Shake", img: "Strawberry shake.png" },
            { id: "blueberry-shake", name: "Blueberry Shake", price: 65, category: "Fruit Shake", img: "BLUEBERRY SHAKE 1.png" },
            { id: "mango-oreo", name: "Mango Oreo", price: 79, category: "Fruit Shake", img: "mango oreo.png" },
            { id: "berry-oreo", name: "Berry Oreo", price: 79, category: "Fruit Shake", img: "Berry Oreo.png" },
            { id: "berry-mango", name: "Berry Mango", price: 79, category: "Fruit Shake", img: "Berry mango.png" },

            // Frappe Series
            { id: "hershey-delight", name: "Hershey Delight", price: 95, category: "Frappe Series", img: "hershey delight.png" },
            { id: "oreo-frappe", name: "Oreo Frappe", price: 105, category: "Frappe Series", img: "Oreo Frappe.png" },
            { id: "matcha-frappe", name: "Matcha Frappe", price: 105, category: "Frappe Series", img: "Matcha Frappe.png" },
            { id: "java-chips", name: "Java Chips", price: 199, category: "Frappe Series", img: "Java Chips.png" },
            { id: "cheesecake-frappe", name: "Cheesecake Frappe", price: 129, category: "Frappe Series", img: "Cheesecake Frappe.png" },
            { id: "white-smore-frappe", name: "White Smore Frappe", price: 129, category: "Frappe Series", img: "FRP-White Smore CB 129 1.png" },
            { id: "caramel-frappe", name: "Caramel Frappe", price: 139, category: "Frappe Series", img: "Caramel Frappe.png" },
            { id: "biscoff-frappe", name: "Biscoff Frappe", price: 139, category: "Frappe Series", img: "Biscoff frappe.png" },
            { id: "nuttela-hazelnut-frappe", name: "Nuttela Hazelnut Frappe", price: 149, category: "Frappe Series", img: "Nuttela Hazelnut Frappe.png" },

            // Snacks — waffles
            { id: "waffle-chocolate", name: "Lolly Waffle Chocolate", price: 69, category: "Snacks", img: "Chocolate waffle.png" },
            { id: "waffle-ube", name: "Lolly Waffle Ube", price: 65, category: "Snacks", img: "ube waffle.png" },
            { id: "waffle-matcha", name: "Lolly Waffle Matcha", price: 69, category: "Snacks", img: "Matcha waffle.png" },
            { id: "waffle-strawberry", name: "Lolly Waffle Strawberry", price: 69, category: "Snacks", img: "Strawberry waffle.png" },
            { id: "waffle-oreo", name: "Lolly Waffle Oreo", price: 65, category: "Snacks", img: "Oreo waffle.png" },
            { id: "waffle-tiramisu", name: "Lolly Waffle Tiramisu", price: 75, category: "Snacks", img: "tiramisu waffle.png" },
            { id: "waffle-biscoff", name: "Lolly Waffle Biscoff", price: 89, category: "Snacks", img: "Biscoff waffle.png" },

            // Snacks — bites & mains
            { id: "french-fries", name: "French Fries", price: 69, category: "Snacks", img: "Fries.png" },
            { id: "chicken-poppers", name: "Chicken Poppers", price: 79, category: "Snacks", img: "Chicken Poppers.png" },
            { id: "beef-nachos", name: "Beef Natchos", price: 149, category: "Snacks", img: "Beef Natchos.png" },
            { id: "fries-poppers", name: "Fries and Chicken Poppers", price: 99, category: "Snacks", img: "Chicken poppers and fries.png" },
            { id: "beef-quesadilla", name: "Beef Quesadilla", price: 149, category: "Snacks", img: "Beef Quesadilla.png" },
            { id: "chicken-quesadilla", name: "Chicken Quesadilla", price: 159, category: "Snacks", img: "Chicken Quesadilla.png" },
            { id: "messy-tuna-spinach", name: "Messy Tuna Spinach", price: 179, category: "Snacks", img: "Messy Tuna Spinach.png" },
        ];

        const IMG_BASE = "../POS/img/";

        /* Master ingredient list — populated from database */
        const INGREDIENT_LIBRARY = <?= json_encode($ingredients) ?>;

        /* In-memory mapping store: { menuItemId: [ {ingredient, unit, qty, cost} ] }
        Populated from database */
        const mappingStore = {};
        
        // Populate mappingStore from database
        const productIngredients = <?= json_encode($productIngredients) ?>;
        productIngredients.forEach(mapping => {
            const productId = mapping.product_name.toLowerCase().replace(/\s+/g, '-');
            if (!mappingStore[productId]) {
                mappingStore[productId] = [];
            }
            mappingStore[productId].push({
                ingredient: mapping.ingredient_name,
                unit: mapping.unit,
                qty: mapping.amount,
                cost: 0,
                total: 0
            });
        });

        let selectedItemId = "hershey-delight";
        let showInactiveOnly = false;

        const peso = (n) => `₱ ${Number(n).toFixed(2)}`;

        function renderMenuList(filterText = "") {
            const list = document.getElementById("menuList");
            const term = filterText.trim().toLowerCase();
            const items = MENU_ITEMS.filter((m) => m.name.toLowerCase().includes(term));

            if (!items.length) {
                list.innerHTML = `<div class="menu-empty">No menu items match "${filterText}"</div>`;
                return;
            }

            list.innerHTML = items
                .map(
                    (m) => `
                <button type="button" class="menu-item ${m.id === selectedItemId ? "selected" : ""}" data-id="${m.id}">
                    <span class="menu-item-thumb"><img src="${IMG_BASE}${m.img}" alt="${m.name}" loading="lazy"></span>
                    <span class="menu-item-info">
                        <span class="menu-item-name">${m.name}</span>
                        <span class="menu-item-price">${peso(m.price)}</span>
                    </span>
                </button>`
                )
                .join("");

            list.querySelectorAll(".menu-item").forEach((btn) => {
                btn.addEventListener("click", () => {
                    selectedItemId = btn.dataset.id;
                    renderMenuList(document.getElementById("menuSearch").value);
                    renderMapPanel();
                });
            });
        }

        function ingredientOptionsHTML(selectedName) {
            return INGREDIENT_LIBRARY.map(
                (ing) => `<option value="${ing.name}" ${ing.name === selectedName ? "selected" : ""}>${ing.name}</option>`
            ).join("");
        }

        function renderMapPanel() {
            const item = MENU_ITEMS.find((m) => m.id === selectedItemId);
            const panel = document.getElementById("mapPanel");
            if (!item) {
                panel.innerHTML = `<div class="map-empty">Select a menu item on the left to map its ingredients.</div>`;
                return;
            }

            const rows = mappingStore[item.id] || [];

            panel.innerHTML = `
                <div class="item-info-bar">
                    <div class="item-info-left">
                        <span class="item-info-thumb"><img src="${IMG_BASE}${item.img}" alt="${item.name}"></span>
                        <span class="item-info-text">
                            <span class="item-info-name">${item.name}</span>
                            <span class="item-info-sub">Category: ${item.category}</span>
                        </span>
                    </div>
                    <div class="item-info-right">
                        <span class="item-info-price-label">Selling Price</span>
                        <span class="item-info-price">${peso(item.price)}</span>
                    </div>
                    <span class="status-badge active">Active</span>
                </div>

                <div class="ingredient-table">
                    <div class="ingredient-row ingredient-head">
                        <span>Ingredient</span>
                        <span>Unit</span>
                        <span>Qty Per Serving</span>
                        <span>Cost Per Unit</span>
                        <span>Total Cost</span>
                        <span></span>
                    </div>
                    <div id="ingredientRows">
                        ${
                            rows.length
                                ? rows
                                    .map(
                                        (row, i) => `
                            <div class="ingredient-row" data-index="${i}">
                                <select class="ing-select">${ingredientOptionsHTML(row.ingredient)}</select>
                                <input class="ing-unit" type="text" value="${row.unit}" placeholder="e.g. ml">
                                <input class="ing-qty" type="number" min="0" step="any" value="${row.qty}" placeholder="0">
                                <span class="ing-cost">${peso(row.cost)}</span>
                                <span class="ing-total">${peso(row.total)}</span>
                                <button type="button" class="ing-delete" aria-label="Remove ingredient" data-index="${i}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>`
                                    )
                                    .join("")
                                : `<div class="ingredient-empty">No ingredients mapped yet. Click "Add Ingredients" below to start.</div>`
                        }
                    </div>
                </div>

                <button type="button" class="add-ingredients-btn" id="addIngredientBtn">
                    <i class="fa-solid fa-plus"></i> Add Ingredients
                </button>

                <div class="map-footer">
                    <div class="estimated-cost">
                        <span class="estimated-cost-label">Estimated Cost per serving</span>
                        <span class="estimated-cost-value" id="estimatedCost">${peso(estimateTotal(rows))}</span>
                    </div>
                    <button type="button" class="save-mapping-btn" id="saveMappingBtn">Save Mapping</button>
                </div>
            `;

            wireMapPanelEvents(item.id);
        }

        function estimateTotal(rows) {
            return rows.reduce((sum, r) => sum + (Number(r.total) || 0), 0);
        }

        function wireMapPanelEvents(itemId) {
            const rows = mappingStore[itemId] || [];

            document.querySelectorAll("#ingredientRows .ingredient-row").forEach((rowEl) => {
                const idx = Number(rowEl.dataset.index);

                const select = rowEl.querySelector(".ing-select");
                const unitInput = rowEl.querySelector(".ing-unit");
                const qtyInput = rowEl.querySelector(".ing-qty");
                const costEl = rowEl.querySelector(".ing-cost");
                const totalEl = rowEl.querySelector(".ing-total");
                const delBtn = rowEl.querySelector(".ing-delete");

                select.addEventListener("change", () => {
                    const lib = INGREDIENT_LIBRARY.find((i) => i.name === select.value);
                    rows[idx].ingredient = select.value;
                    if (lib) {
                        rows[idx].cost = lib.cost;
                        if (!unitInput.value) {
                            rows[idx].unit = lib.unit;
                            unitInput.value = lib.unit;
                        }
                        costEl.textContent = peso(lib.cost);
                        recalcRow(idx, rows, qtyInput, totalEl);
                    }
                });

                unitInput.addEventListener("input", () => {
                    rows[idx].unit = unitInput.value;
                });

                qtyInput.addEventListener("input", () => {
                    rows[idx].qty = qtyInput.value;
                    recalcRow(idx, rows, qtyInput, totalEl);
                });

                delBtn.addEventListener("click", () => {
                    rows.splice(idx, 1);
                    renderMapPanel();
                });
            });

            document.getElementById("addIngredientBtn").addEventListener("click", () => {
                const defaultIng = INGREDIENT_LIBRARY[0];
                rows.push({ ingredient: defaultIng.name, unit: defaultIng.unit, qty: "", cost: defaultIng.cost, total: 0 });
                mappingStore[itemId] = rows;
                renderMapPanel();
            });

            document.getElementById("saveMappingBtn").addEventListener("click", () => {
                mappingStore[itemId] = rows;
                const btn = document.getElementById("saveMappingBtn");
                const original = btn.textContent;
                btn.textContent = "Saved ✓";
                btn.disabled = true;
                setTimeout(() => {
                    btn.textContent = original;
                    btn.disabled = false;
                }, 1200);
            });
        }

        function recalcRow(idx, rows, qtyInput, totalEl) {
            const qty = Number(qtyInput.value) || 0;
            const cost = Number(rows[idx].cost) || 0;
            const total = qty > 0 ? qty * cost : rows[idx].total;
            rows[idx].total = qty > 0 ? total : rows[idx].total;
            totalEl.textContent = peso(rows[idx].total);
            const estimatedEl = document.getElementById("estimatedCost");
            if (estimatedEl) estimatedEl.textContent = peso(estimateTotal(rows));
        }

        document.addEventListener("DOMContentLoaded", () => {
            renderMenuList();
            renderMapPanel();

            document.getElementById("menuSearch").addEventListener("input", (e) => {
                renderMenuList(e.target.value);
            });

            document.getElementById("addMenuItemBtn").addEventListener("click", () => {
                alert("Open the Menu Management page to add a new menu item, then come back here to map its ingredients.");
            });

            const toggle = document.getElementById("showInactiveToggle");
            toggle.addEventListener("change", () => {
                showInactiveOnly = toggle.checked;
                document.getElementById("mapPanel").classList.toggle("show-inactive", showInactiveOnly);
            });

            // Load inventory status
            loadInventoryStatus();
            
            // Auto-refresh inventory every 10 seconds
            setInterval(loadInventoryStatus, 10000);
        });

        // Load and display real-time inventory status
        async function loadInventoryStatus() {
            const indicator = document.getElementById('refreshIndicator');
            const statusBody = document.getElementById('inventoryStatus');
            
            try {
                indicator.style.display = 'flex';
                
                const response = await fetch('inventory_api.php?action=get_inventory_status');
                const data = await response.json();
                
                if (data.success && data.inventory) {
                    renderInventoryStatus(data.inventory);
                } else {
                    statusBody.innerHTML = '<div class="error-inventory">Failed to load inventory data</div>';
                }
            } catch (error) {
                console.error('Error loading inventory:', error);
                statusBody.innerHTML = '<div class="error-inventory">Error loading inventory data</div>';
            } finally {
                indicator.style.display = 'none';
            }
        }

        function renderInventoryStatus(inventory) {
            const statusBody = document.getElementById('inventoryStatus');
            
            if (!inventory || inventory.length === 0) {
                statusBody.innerHTML = '<div class="no-inventory">No inventory data available</div>';
                return;
            }

            let html = '<div class="inventory-grid">';
            
            inventory.forEach(item => {
                const stockLevel = calculateStockLevel(item.stock, item.max_stock);
                const stockClass = getStockClass(stockLevel);
                
                html += `
                    <div class="inventory-item ${stockClass}">
                        <div class="inventory-name">${item.name}</div>
                        <div class="inventory-details">
                            <span class="inventory-stock">${item.stock} ${item.unit}</span>
                            <span class="inventory-max">/ ${item.max_stock} ${item.unit}</span>
                        </div>
                        <div class="stock-progress">
                            <div class="stock-bar ${stockClass}" style="width: ${stockLevel}%"></div>
                        </div>
                        <div class="inventory-branch">Branch: ${item.branch_name || 'Main'}</div>
                    </div>
                `;
            });
            
            html += '</div>';
            statusBody.innerHTML = html;
        }

        function calculateStockLevel(current, max) {
            if (!max || max <= 0) return 0;
            return Math.min(100, Math.max(0, (current / max) * 100));
        }

        function getStockClass(level) {
            if (level <= 20) return 'critical';
            if (level <= 50) return 'warning';
            return 'good';
        }

    </script>
</body>

</html>