<?php
session_name('POS_SESSION');
session_start();
require_once '../config/db_config.php';

// Session guard — redirect to login if not logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$employeeId = (int) $_SESSION['employee_id'];

// Fetch fresh employee data from DB to validate session
$stmt = $connect->prepare("SELECT id, employee_name, email, is_active, branch_id FROM employees WHERE id=?");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee || (int) $employee['is_active'] === 0) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}
$stmt->close();

// Check for active shift - redirect to shift page if no open shift
$shiftStmt = $connect->prepare("SELECT id, opening_cash_float, opened_at FROM shift_logs WHERE employee_id = ? AND status = 'open' LIMIT 1");
$shiftStmt->bind_param('i', $employeeId);
$shiftStmt->execute();
$shiftResult = $shiftStmt->get_result()->fetch_assoc();
$shiftStmt->close();

if (!$shiftResult) {
    header('Location: pos-shift.php');
    exit;
}

// Store shift info for use in the page
$shiftId = $shiftResult['id'];
$openingCash = $shiftResult['opening_cash_float'];
$shiftOpenedAt = $shiftResult['opened_at'];

// Get branch name for profile display
$branchName = 'Main Branch';
$branchId = isset($_SESSION['branch_id']) ? (int) $_SESSION['branch_id'] : 0;

// Get employee name for display
$employeeName = isset($_SESSION['employee_name']) ? $_SESSION['employee_name'] : 'Cashier';

if ($branchId > 0) {
    $branchStmt = $connect->prepare("SELECT branch_name FROM branches WHERE id = ?");
    $branchStmt->bind_param('i', $branchId);
    $branchStmt->execute();
    $branchResult = $branchStmt->get_result()->fetch_assoc();
    if ($branchResult) {
        // Baliuag = Main Branch, Bustos = Bustos Branch
        if (stripos($branchResult['branch_name'], 'Baliuag') !== false) {
            $branchName = 'Main Branch';
        } else {
            $branchName = $branchResult['branch_name'] . ' Branch';
        }
    }
    $branchStmt->close();
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dash-css/pos-menu.css">
    <link rel="stylesheet" href="dash-css/order-notify.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold</title>
</head>
<body>
    <script>
        document.body.classList.toggle(
            "dark-theme",
            (localStorage.getItem("boycold_theme") || "dark") === "dark"
        );
    </script>

    <div class="app-shell">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">

            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true">
                    <img class="logo-light" src="../img/icon2.png" alt="LOGO">
                    <img class="logo-dark" src="../img/ChatGPT Image Jul 1, 2026, 12_58_44 PM 1.png" alt="LOGO">                
                </span>
                <span class="brand-text">
                    <span class="brand-name">BoyCold Cafe</span>
                    <span class="brand-sub">Point of Sale</span>
                </span>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="pos-menu.php" class="active">
                            <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"  xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z" fill="currentColor"/>
                            </svg></span>
                            <span class="nav-label">Menu</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-status.php">
                            <span class="nav-icon"><svg width="19" height="22" viewBox="0 0 19 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.8882 1H3.31469C2.03632 1 1 2.03632 1 3.31469V18.3602C1 19.6386 2.03632 20.6749 3.31469 20.6749H14.8882C16.1665 20.6749 17.2029 19.6386 17.2029 18.3602V3.31469C17.2029 2.03632 16.1665 1 14.8882 1Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M5.62939 6.78662H12.5735M5.62939 11.416H12.5735M5.62939 16.0454H10.2588" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg></span>
                            <span class="nav-label">Order Status</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-online.php">
                            <span class="nav-icon2"><i class="fa-regular fa-bell"></i></span>
                            <span class="nav-label">Online Orders</span>
                            <span class="nav-badge">3</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-history.php">
                            <span class="nav-icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.64456 19.2891C7.17984 19.2891 5.03232 18.4722 3.20199 16.8383C1.37167 15.2045 0.3222 13.1638 0.0535808 10.7162H2.2504C2.50044 12.5737 3.32666 14.1096 4.72905 15.3241C6.13144 16.5386 7.76994 17.1459 9.64456 17.1459C11.7342 17.1459 13.507 16.4183 14.963 14.963C16.419 13.5077 17.1466 11.7349 17.1459 9.64456C17.1452 7.55419 16.4175 5.78174 14.963 4.32719C13.5085 2.87265 11.7356 2.14466 9.64456 2.14324C8.4122 2.14324 7.26021 2.429 6.18859 3.00053C5.11698 3.57206 4.21503 4.35791 3.48276 5.35809H6.42971V7.50133H0V1.07162H2.14324V3.58992C3.05411 2.44686 4.16609 1.56278 5.47918 0.937666C6.79227 0.312555 8.18073 0 9.64456 0C10.9841 0 12.2389 0.254688 13.4092 0.764064C14.5794 1.27344 15.5974 1.9607 16.4633 2.82586C17.3291 3.69101 18.0168 4.70905 18.5261 5.87997C19.0355 7.05089 19.2898 8.30575 19.2891 9.64456C19.2884 10.9834 19.0341 12.2382 18.5261 13.4092C18.0182 14.5801 17.3306 15.5981 16.4633 16.4633C15.596 17.3284 14.5779 18.016 13.4092 18.5261C12.2404 19.0362 10.9855 19.2906 9.64456 19.2891ZM12.6451 14.1454L8.57294 10.0732V4.28647H10.7162V9.21591L14.1454 12.6451L12.6451 14.1454Z" fill="currentColor"/>
                            </svg></span>
                            <span class="nav-label">Order History</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-settings.php">
                            <span class="nav-icon"><svg width="22" height="23" viewBox="0 0 22 23" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.30036 22.2897C8.79884 22.2897 8.36716 22.1225 8.00533 21.7881C7.64349 21.4538 7.42505 21.0451 7.35001 20.5622L7.09925 18.7233C6.85778 18.6304 6.63043 18.519 6.41719 18.389C6.20395 18.2589 5.9948 18.1196 5.78974 17.971L4.06229 18.6954C3.59792 18.8998 3.13355 18.9183 2.66919 18.7512C2.20482 18.584 1.84261 18.2868 1.58257 17.8596L0.273048 15.5749C0.0130021 15.1477 -0.0612965 14.6926 0.0501518 14.2097C0.1616 13.7267 0.412359 13.3274 0.802427 13.0116L2.27912 11.8971C2.26054 11.7671 2.25125 11.6415 2.25125 11.5204V10.7681C2.25125 10.6478 2.26054 10.5226 2.27912 10.3926L0.802427 9.27807C0.412359 8.9623 0.1616 8.56294 0.0501518 8.08C-0.0612965 7.59706 0.0130021 7.14198 0.273048 6.71476L1.58257 4.43007C1.84261 4.00285 2.20482 3.70566 2.66919 3.53848C3.13355 3.37131 3.59792 3.38988 4.06229 3.59421L5.78974 4.31862C5.99406 4.17002 6.20767 4.03071 6.43056 3.90069C6.65346 3.77067 6.87636 3.65922 7.09925 3.56634L7.35001 1.72745C7.42431 1.24451 7.64275 0.835862 8.00533 0.501517C8.36791 0.167172 8.79958 0 9.30036 0H11.9194C12.4209 0 12.853 0.167172 13.2155 0.501517C13.5781 0.835862 13.7962 1.24451 13.8697 1.72745L14.1205 3.56634C14.362 3.65922 14.5897 3.77067 14.8037 3.90069C15.0177 4.03071 15.2264 4.17002 15.43 4.31862L17.1575 3.59421C17.6218 3.38988 18.0862 3.37131 18.5506 3.53848C19.0149 3.70566 19.3771 4.00285 19.6372 4.43007L20.9467 6.71476C21.2067 7.14198 21.281 7.59706 21.1696 8.08C21.0582 8.56294 20.8074 8.9623 20.4173 9.27807L18.9406 10.3926C18.9592 10.5226 18.9685 10.6481 18.9685 10.7692V11.5204C18.9685 11.6415 18.9499 11.7671 18.9128 11.8971L20.3895 13.0116C20.7795 13.3274 21.0303 13.7267 21.1417 14.2097C21.2532 14.6926 21.1789 15.1477 20.9188 15.5749L19.5815 17.8596C19.3214 18.2868 18.9592 18.584 18.4948 18.7512C18.0305 18.9183 17.5661 18.8998 17.1017 18.6954L15.43 17.971C15.2257 18.1196 15.0121 18.2589 14.7892 18.389C14.5663 18.519 14.3434 18.6304 14.1205 18.7233L13.8697 20.5622C13.7954 21.0451 13.5774 21.4538 13.2155 21.7881C12.8537 22.1225 12.4217 22.2897 11.9194 22.2897H9.30036ZM9.49539 20.0607H11.6965L12.0866 17.1073C12.6624 16.9587 13.1966 16.7406 13.6892 16.4531C14.1818 16.1656 14.632 15.8171 15.0399 15.4077L17.7983 16.5501L18.8849 14.6554L16.4888 12.8444C16.5816 12.5844 16.6467 12.3106 16.6838 12.023C16.721 11.7355 16.7395 11.4428 16.7395 11.1448C16.7395 10.8469 16.721 10.5545 16.6838 10.2677C16.6467 9.98094 16.5816 9.70677 16.4888 9.44524L18.8849 7.63421L17.7983 5.73959L15.0399 6.90979C14.6313 6.48257 14.181 6.1252 13.6892 5.83766C13.1973 5.55012 12.6631 5.33169 12.0866 5.18234L11.7244 2.22897H9.52325L9.13318 5.18234C8.55737 5.33094 8.02353 5.54938 7.53167 5.83766C7.03982 6.12594 6.58919 6.47403 6.17981 6.88193L3.42146 5.73959L2.33484 7.63421L4.73098 9.41738C4.63811 9.696 4.57309 9.97462 4.53594 10.2532C4.4988 10.5319 4.48022 10.8291 4.48022 11.1448C4.48022 11.442 4.4988 11.7299 4.53594 12.0086C4.57309 12.2872 4.63811 12.5658 4.73098 12.8444L2.33484 14.6554L3.42146 16.5501L6.17981 15.3799C6.58845 15.8071 7.03907 16.1648 7.53167 16.4531C8.02427 16.7414 8.55811 16.9595 9.13318 17.1073L9.49539 20.0607ZM10.6656 15.0455C11.7429 15.0455 12.6624 14.6647 13.4239 13.9032C14.1855 13.1416 14.5663 12.2222 14.5663 11.1448C14.5663 10.0675 14.1855 9.14804 13.4239 8.38648C12.6624 7.62492 11.7429 7.24414 10.6656 7.24414C9.56969 7.24414 8.64578 7.62492 7.89388 8.38648C7.14198 9.14804 6.76565 10.0675 6.76491 11.1448C6.76417 12.2222 7.14049 13.1416 7.89388 13.9032C8.64727 14.6647 9.57118 15.0455 10.6656 15.0455Z" fill="currentColor"/>
                            </svg></span>
                            <span class="nav-label">POS Settings</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-divider"></div>

                <ul>
                    <li>
                        <a href="pos-shift.php">
                            <span class="nav-icon"><svg width="23" height="23" viewBox="0 0 23 23" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M6.94408 0C5.83907 0 4.77932 0.438964 3.99796 1.22033C3.2166 2.00169 2.77763 3.06144 2.77763 4.16645V9.16619C3.22165 8.93937 3.68681 8.75656 4.16645 8.62039V4.16645C4.16645 3.42978 4.45909 2.72327 4.98 2.20237C5.50091 1.68146 6.20741 1.38882 6.94408 1.38882H18.0546C18.7913 1.38882 19.4978 1.68146 20.0187 2.20237C20.5396 2.72327 20.8323 3.42978 20.8323 4.16645V15.277C20.8323 16.0137 20.5396 16.7202 20.0187 17.2411C19.4978 17.762 18.7913 18.0546 18.0546 18.0546H13.6007C13.4627 18.5398 13.2808 19.0027 13.0549 19.4434H18.0546C19.1596 19.4434 20.2194 19.0045 21.0007 18.2231C21.7821 17.4418 22.2211 16.382 22.2211 15.277V4.16645C22.2211 3.06144 21.7821 2.00169 21.0007 1.22033C20.2194 0.438964 19.1596 0 18.0546 0H6.94408ZM6.24968 22.2211C7.90719 22.2211 9.49682 21.5626 10.6689 20.3906C11.8409 19.2185 12.4994 17.6289 12.4994 15.9714C12.4994 14.3139 11.8409 12.7242 10.6689 11.5522C9.49682 10.3802 7.90719 9.72172 6.24968 9.72172C4.59216 9.72172 3.00253 10.3802 1.83049 11.5522C0.658446 12.7242 0 14.3139 0 15.9714C0 17.6289 0.658446 19.2185 1.83049 20.3906C3.00253 21.5626 4.59216 22.2211 6.24968 22.2211ZM6.24968 12.4994C6.43384 12.4994 6.61047 12.5725 6.7407 12.7027C6.87092 12.833 6.94408 13.0096 6.94408 13.1938V15.277H9.02731C9.21148 15.277 9.3881 15.3501 9.51833 15.4804C9.64856 15.6106 9.72172 15.7872 9.72172 15.9714C9.72172 16.1556 9.64856 16.3322 9.51833 16.4624C9.3881 16.5926 9.21148 16.6658 9.02731 16.6658H6.94408V18.749C6.94408 18.9332 6.87092 19.1098 6.7407 19.2401C6.61047 19.3703 6.43384 19.4434 6.24968 19.4434C6.06551 19.4434 5.88888 19.3703 5.75866 19.2401C5.62843 19.1098 5.55527 18.9332 5.55527 18.749V16.6658H3.47204C3.28787 16.6658 3.11125 16.5926 2.98102 16.4624C2.85079 16.3322 2.77763 16.1556 2.77763 15.9714C2.77763 15.7872 2.85079 15.6106 2.98102 15.4804C3.11125 15.3501 3.28787 15.277 3.47204 15.277H5.55527V13.1938C5.55527 13.0096 5.62843 12.833 5.75866 12.7027C5.88888 12.5725 6.06551 12.4994 6.24968 12.4994ZM13.8882 4.86086C13.8882 4.67669 13.815 4.50007 13.6848 4.36984C13.5546 4.23961 13.3779 4.16645 13.1938 4.16645C13.0096 4.16645 12.833 4.23961 12.7027 4.36984C12.5725 4.50007 12.4994 4.67669 12.4994 4.86086V9.02731C12.4994 9.21148 12.5725 9.3881 12.7027 9.51833C12.833 9.64856 13.0096 9.72172 13.1938 9.72172H15.9714C16.1556 9.72172 16.3322 9.64856 16.4624 9.51833C16.5926 9.3881 16.6658 9.21148 16.6658 9.02731C16.6658 8.84314 16.5926 8.66652 16.4624 8.53629C16.3322 8.40606 16.1556 8.3329 15.9714 8.3329H13.8882V4.86086Z" fill="currentColor"/>
                            </svg></span>
                            <span class="nav-label">Open / Close Shift</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <span class="nav-icon"><svg width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.75 8.75C0.75 4.979 0.75 3.093 1.922 1.922C3.094 0.751 4.979 0.75 8.75 0.75H12.75C16.521 0.75 18.407 0.75 19.578 1.922C20.749 3.094 20.75 4.979 20.75 8.75C20.75 12.521 20.75 14.407 19.578 15.578C18.406 16.749 16.521 16.75 12.75 16.75H8.75C4.979 16.75 3.093 16.75 1.922 15.578C0.751 14.406 0.75 12.521 0.75 8.75Z" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M8.75 12.75H4.75M12.75 12.75H11.25M0.75 6.75H20.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg></span>
                            <span class="nav-label">Loyalty Card</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="logout-link">
                    <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                    <span class="nav-label">Log Out</span>
                </a>
            </div>
        </aside>

        <!-- MAIN PANEL -->
        <div class="main-panel">

            <div class="top-header">
                <div id="popupHost" style="display:none;"></div>

                <div class="header-divider"></div>

                <div class="shift-pill is-open" id="shiftPill">
                    <span class="shift-dot"></span>
                    <span id="shiftPillLabel">Shift Open</span>
                </div>

                <div class="notif-wrap">
                    <button class="icon-btn" id="notifBtn" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <span class="icon-badge" id="notifBadge">2</span>
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

                        <a href="#" class="notif-footer">
                            View all notifications <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="header-divider"></div>

                <button class="profile-btn">
                    <div class="profile-avatar">
                        <svg class="logo-light" width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.75762 25.5987C10.0301 24.6256 11.4522 23.8587 13.0241 23.2978C14.5959 22.7369 16.2426 22.456 17.9642 22.455C19.6858 22.454 21.3325 22.7349 22.9043 23.2978C24.4762 23.8607 25.8983 24.6276 27.1708 25.5987C28.044 24.5757 28.7242 23.4156 29.2112 22.1181C29.6982 20.8207 29.9412 19.436 29.9403 17.964C29.9403 14.6456 28.7741 11.8197 26.4417 9.48641C24.1094 7.15308 21.2836 5.98691 17.9642 5.98791C14.6448 5.98891 11.819 7.15557 9.48666 9.48791C7.15432 11.8202 5.98815 14.6456 5.98815 17.964C5.98815 19.436 6.23167 20.8207 6.71869 22.1181C7.20572 23.4156 7.88536 24.5757 8.75762 25.5987ZM14.2411 17.9445C13.2302 16.9355 12.7247 15.6945 12.7247 14.2214C12.7247 12.7484 13.2302 11.5069 14.2411 10.4969C15.2521 9.48691 16.4931 8.98192 17.9642 8.98192C19.4353 8.98192 20.6768 9.48741 21.6888 10.4984C22.7007 11.5094 23.2057 12.7504 23.2037 14.2214C23.2017 15.6925 22.6967 16.934 21.6888 17.946C20.6808 18.958 19.4393 19.463 17.9642 19.461C16.4892 19.459 15.2476 18.954 14.2397 17.946M17.9642 32.934C15.8933 32.934 13.9472 32.5408 12.1259 31.7544C10.3045 30.9679 8.72019 29.9016 7.37289 28.5553C6.02558 27.209 4.95921 25.6246 4.17378 23.8023C3.38835 21.9799 2.99514 20.0338 2.99414 17.964C2.99314 15.8941 3.38636 13.948 4.17378 12.1256C4.96121 10.3033 6.02758 8.71895 7.37289 7.37264C8.71819 6.02633 10.3025 4.95996 12.1259 4.17354C13.9492 3.38711 15.8953 2.9939 17.9642 2.9939C20.0331 2.9939 21.9792 3.38711 23.8025 4.17354C25.6259 4.95996 27.2102 6.02633 28.5555 7.37264C29.9008 8.71895 30.9677 10.3033 31.7561 12.1256C32.5445 13.948 32.9373 15.8941 32.9343 17.964C32.9313 20.0338 32.5381 21.9799 31.7546 23.8023C30.9712 25.6246 29.9048 27.209 28.5555 28.5553C27.2062 29.9016 25.6219 30.9684 23.8025 31.7559C21.9832 32.5433 20.0371 32.936 17.9642 32.934Z" fill="black"/>
                        </svg>
                        <svg class="logo-dark" width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.75762 25.5988C10.0301 24.6257 11.4522 23.8588 13.0241 23.2979C14.5959 22.737 16.2426 22.4561 17.9642 22.4551C19.6858 22.4541 21.3325 22.735 22.9043 23.2979C24.4762 23.8608 25.8983 24.6277 27.1708 25.5988C28.044 24.5758 28.7242 23.4157 29.2112 22.1183C29.6982 20.8209 29.9412 19.4361 29.9403 17.9641C29.9403 14.6457 28.7741 11.8199 26.4417 9.48653C24.1094 7.15319 21.2836 5.98702 17.9642 5.98802C14.6448 5.98902 11.819 7.15569 9.48666 9.48802C7.15432 11.8204 5.98815 14.6457 5.98815 17.9641C5.98815 19.4361 6.23167 20.8209 6.71869 22.1183C7.20572 23.4157 7.88536 24.5758 8.75762 25.5988ZM14.2411 17.9446C13.2302 16.9356 12.7247 15.6946 12.7247 14.2216C12.7247 12.7485 13.2302 11.507 14.2411 10.497C15.2521 9.48702 16.4931 8.98203 17.9642 8.98203C19.4353 8.98203 20.6768 9.48752 21.6888 10.4985C22.7007 11.5095 23.2057 12.7505 23.2037 14.2216C23.2017 15.6926 22.6967 16.9341 21.6888 17.9461C20.6808 18.9581 19.4393 19.4631 17.9642 19.4611C16.4892 19.4591 15.2476 18.9541 14.2397 17.9461M17.9642 32.9341C15.8933 32.9341 13.9472 32.5409 12.1259 31.7545C10.3045 30.9681 8.72019 29.9017 7.37289 28.5554C6.02558 27.2091 4.95921 25.6247 4.17378 23.8024C3.38835 21.98 2.99514 20.0339 2.99414 17.9641C2.99314 15.8942 3.38636 13.9481 4.17378 12.1257C4.96121 10.3034 6.02758 8.71906 7.37289 7.37275C8.71819 6.02645 10.3025 4.96008 12.1259 4.17365C13.9492 3.38722 15.8953 2.99401 17.9642 2.99401C20.0331 2.99401 21.9792 3.38722 23.8025 4.17365C25.6259 4.96008 27.2102 6.02645 28.5555 7.37275C29.9008 8.71906 30.9677 10.3034 31.7561 12.1257C32.5445 13.9481 32.9373 15.8942 32.9343 17.9641C32.9313 20.0339 32.5381 21.98 31.7546 23.8024C30.9712 25.6247 29.9048 27.2091 28.5555 28.5554C27.2062 29.9017 25.6219 30.9686 23.8025 31.756C21.9832 32.5434 20.0371 32.9361 17.9642 32.9341Z" fill="white"/>
                        </svg>
                    </div>
                    <span class="profile-name"><?= htmlspecialchars($branchName) ?></span>
                </button>
            </div>

            <nav class="category-bar" aria-label="Menu categories">
                <a href="#" data-filter="coffee" class="cat-pill active">Coffee</a>
                <a href="#" data-filter="non-coffee" class="cat-pill">Non-Coffee</a>
                <a href="#" data-filter="special-coffee" class="cat-pill">Special Coffee</a>
                <a href="#" data-filter="matcha-fusion" class="cat-pill">Matcha Fusion</a>
                <a href="#" data-filter="fruit-shake" class="cat-pill">Fruit Shake</a>
                <a href="#" data-filter="frappe-series" class="cat-pill">Frappe Series</a>
                <a href="#" data-filter="snacks" class="cat-pill">Snacks</a>
                <button class="cat-add" id="addProductBtn" aria-label="Add category">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add</span>
                </button>
                <button class="cat-more" id="viewToggle" aria-label="sort">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </nav>

            <section class="menu-section">
                <div class="menu-content">
                    <div class="product-list-header">
                        <span>Item</span>
                        <span>Item Name</span>
                        <span>Price</span>
                        <span>Servings</span>
                        <span>Cups</span>
                        <span>Status</span>
                        <span>Action</span>
                    </div>
                    <div class="product-grid" id="productGrid">
                        
                        <!-- Coffee -->
                        <div class="product-card" data-category="coffee" data-id="americano">
                            <div class="product-row">
                                <div class="card-image">
                                    <div class="card-image-placeholder">
                                        
                                        <img src="../img/Americano.png">
                                    </div>
                                </div>
                                <div class="card-info">
                                    <div class="card-mid">
                                        <p class="card-name">Americano</p>
                                    </div>

                                    <div class="card-footer">
                                        <p class="card-price">₱69.00</p>

                                        <!-- NEW: Inventory Status -->
                                        <div class="drink-stock">
                                            <p class="drink-status available">
                                                <span class="status-dot"></span>
                                                Available
                                            </p>

                                            <p class="drink-ingredient">
                                                Ingredients: <span>Sufficient</span>
                                            </p>

                                            <p class="drink-cups">
                                                Cups: <span>40 pcs</span>
                                            </p>
                                        </div>
                                        <button class="card-btn btn-order" aria-label="Add to order">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </div>
                                    
                                </div>
                            </div>
                            
                        </div>
                        <div class="product-card" data-category="coffee" data-id="cafe-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Cafe Latte.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Cafe Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱85.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    
                                        
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  coffee" data-id="spanish-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Spanish Latte.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Spanish Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱95.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="coffee" data-id="dirty-matcha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Dirty Matcha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Dirty Matcha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱119.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="coffee" data-id="dark-mocha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Dark Mocha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Dark Mocha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱139.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  coffee" data-id="white-mocha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/White Mocha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">White Mocha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱129.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  coffee" data-id="french-vanilla">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Franch Vanilla.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">French Vanilla</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱135.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="coffee" data-id="hazelnut-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Hazelnut Latte.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Hazelnut Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱135.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    
                                </div>
                            </div>
                        </div>

                        <!-- Non Coffee -->
                        <div class="product-card" data-category="non-coffee" data-id="strawberry-milk">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Strawberry Milk.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Strawberry Milk</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱79.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="non-coffee" data-id="blueberry-milk">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Blueberry Milk.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Blueberry Milk</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱79.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  non-coffee" data-id="milky-oreo">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Milky Oreo.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Milky Oreo</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱85.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="non-coffee" data-id="white-cocoa">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/White cocoa.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">White cocoa</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱95.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  non-coffee" data-id="choco-berry">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Choco Berry.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Choco Berry</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱109.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  non-coffee" data-id="choco-vanilla-cookie">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Choco Vanilla Cookie.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Choco Vanilla Cookie</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱129.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  non-coffee" data-id="choco-banana-pudding">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Choco Banna Pudding.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Choco Banana Pudding</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱179.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Special Coffee -->
                        <div class="product-card" data-category="  special-coffee" data-id="sea-salt-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Sea salt Latte.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Sea Salt Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱115.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="special-coffee" data-id="salted-mango-dream">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Salted Mango Dream.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Salted Mango Dream</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱139.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="special-coffee" data-id="berry-caramel-bliss">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Berry Caramel Bliss.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Berry Caramel Bliss</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱139.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  special-coffee" data-id="caramel-macchiato">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Caramel Macchiato.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Caramel Macchiato</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱139.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="special-coffee" data-id="butterscotch-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Butter scotch latte.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Butter Scotch Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱139.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  special-coffee" data-id="salted-caramel">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Salted Caramel.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Salted Caramel</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱139.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="special-coffee" data-id="salted-macadamia">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Salted Macadamia.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Salted Macadamia</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱139.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  special-coffee" data-id="cheesecake-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Cheesecake Latte.png" >
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Cheesecake Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱149.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  special-coffee" data-id="einspanner-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Einspanner Latte.png" >
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Einspanner Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱149.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="product-card" data-category="  special-coffee" data-id="biscoff-creamy-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Biscoff Creamy Latte.png" >
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Biscoff Creamy Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱159.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="special-coffee" data-id="nutella-hazelnut-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Nutella Hazelnut latte.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Nutella Hazelnut Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱169.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  special-coffee" data-id="tiramisu-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Tiramisu Latte.png" alt="Einspanner Latte">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Tiramisu Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱179.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Matcha Fusion-->
                        <div class="product-card" data-category="matcha-fusion" data-id="pure-matcha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Pure matcha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Pure Matcha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱80.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  matcha-fusion" data-id="matcha-latte">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    <img src="../img/Matcha Latte.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Matcha Latte</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱85.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="matcha-fusion" data-id="mango-matcha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                     
                                    <img src="../img/Mango matcha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Mango Matcha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱89.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>

                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  matcha-fusion" data-id="sea-salt-matcha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                <img src="../img/Seasalt Matcha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Sea Salt Matcha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱95.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  matcha-fusion" data-id="matcha-freddo">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Matcha Freddo.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Matcha Freddo</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱99.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="matcha-fusion" data-id="choco-matcha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Choco Matcha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Choco Matcha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱109.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  matcha-fusion" data-id="strawberry-matcha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Strawberry Matcha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Strawberry Matcha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱115.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="matcha-fusion" data-id="cheesecake-matcha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Cheesecake Matcha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Cheesecake Matcha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱119.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="matcha-fusion" data-id="lavender-matcha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Lavander Matcha.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Lavander Matcha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱119.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  matcha-fusion" data-id="matcha-banana-pudding">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Matcha banana Pudding.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Matcha Banana Pudding</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱179.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>

                                </div>
                            </div>
                        </div>

                        <!-- Fruit Shake -->
                        <div class="product-card" data-category="  fruit-shake" data-id="mango-graham">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Mango graham.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Mango Graham</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱65.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="fruit-shake" data-id="strawberry-shake">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Strawberry shake.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Strawberry Shake</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱65.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="fruit-shake" data-id="blueberry-shake">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/BLUEBERRY SHAKE 1.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Blueberry Shake</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱65.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  fruit-shake" data-id="mango-oreo">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/mango oreo.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Mango Oreo</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱79.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  fruit-shake" data-id="berry-oreo">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Berry Oreo.png" >
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Berry Oreo</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱79.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  fruit-shake" data-id="berry-mango">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Berry mango.png" >
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Berry Mango</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱79.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Frappe Series -->
                        <div class="product-card" data-category="  frappe-series" data-id="hershey-delight">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/hershey delight.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Hershey Delight</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱95.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="ca..er" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  frappe-series" data-id="oreo-frappe">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Oreo Frappe.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Oreo Frappe</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱105.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="frappe-series" data-id="matcha-frappe">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Matcha Frappe.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Matcha Frappe</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱105.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  frappe-series" data-id="java-chips">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Java Chips.png" >
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Java Chips</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱199.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="frappe-series" data-id="cheesecake-frappe">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Cheesecake Frappe.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Cheesecake Frappe</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱129.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="frappe-series" data-id="white-smore-frappe">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/FRP-White Smore CB 129 1.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">White Smore Frappe</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱129.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="frappe-series" data-id="caramel-frappe">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Caramel Frappe.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Caramel Frappe</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱139.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                        <button class="card-btn btn-order" aria-label="Add to order">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="  frappe-series" data-id="biscoff-frappe">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Biscoff frappe.png" alt="">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Biscoff Frappe</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱139.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="frappe-series" data-id="nuttela-hazelnut-frappe">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Nuttela Hazelnut Frappe.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Nuttela Hazelnut Frappe</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱149.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Waffles -->
                        <div class="product-card" data-category="snacks" data-id="waffle-chocolate">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Chocolate waffle.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Lolly Waffle Chocolate</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱69.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="snacks" data-id="waffle-ube">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/ube waffle.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Lolly Waffle Ube</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱65.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="snacks" data-id="waffle-matcha">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Matcha waffle.png" alt="White Smore">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Lolly Waffle Matcha</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱69.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="snacks" data-id="waffle-strawberry">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Strawberry waffle.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Lolly Waffle Strawberry</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱69.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="snacks" data-id="waffle-oreo">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Oreo waffle.png" alt="">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Lolly Waffle Oreo</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱65.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="snacks" data-id="waffle-tiramisu">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/tiramisu waffle.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Lolly Waffle Tiramisu</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱75.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="product-card" data-category="snacks" data-id="waffle-biscoff">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Biscoff waffle.png" alt="Einspanner Latte">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Lolly Waffle Biscoff</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱89.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Bites -->
                        <div class="product-card" data-category="snacks" data-id="french-fries">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Fries.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">French Fries</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱69.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="snacks" data-id="chicken-poppers">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Chicken Poppers.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Chicken Poppers</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱79.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="snacks" data-id="beef-nachos">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Beef Natchos.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Beef Natchos</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱149.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="snacks" data-id="fries-poppers">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Chicken poppers and fries.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Fries and Chicken Poppers</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱99.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Quesadilla -->
                        <div class="product-card" data-category="snacks" data-id="beef-quesadilla">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Beef Quesadilla.png">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Beef Quesadilla</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱149.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="product-card" data-category="snacks" data-id="chicken-quesadilla">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Chicken Quesadilla.png" alt="">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Chicken Quesadilla</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱159.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4 -->
                        <div class="product-card" data-category="snacks" data-id="messy-tuna-spinach">
                            <div class="card-image">
                                <div class="card-image-placeholder">
                                    
                                    <img src="../img/Messy Tuna Spinach.png" alt="">
                                </div>
                            </div>
                            <div class="card-info">
                                <div class="card-mid">
                                    <p class="card-name">Messy Tuna Spinach</p>
                                    
                                </div>
                                <div class="card-footer">
                                    <p class="card-price">₱179.00</p>
                                    <div class="drink-stock">
                                        <p class="drink-status available">
                                            <span class="status-dot"></span>
                                            Available
                                        </p>

                                        <p class="drink-ingredient">
                                            Ingredients: <span>Sufficient</span>
                                        </p>

                                        <p class="drink-cups">
                                            Cups: <span>40 pcs</span>
                                        </p>
                                    </div>
                                    <button class="card-btn btn-order" aria-label="Add to order">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="inventory-alerts-bar">
                        <div class="inventory-left">
                            <div class="inventory-bell-icon">
                                <i class="fa-regular fa-bell"></i>
                            </div>
                            <div>
                                <p class="inventory-title">Inventory Alerts</p>
                                <p class="inventory-sub">Some items need restocking</p>
                                <a href="#" class="inventory-link">View All Alerts <i class="fa-solid fa-chevron-right"></i></a>
                            </div>
                        </div>

                        <div class="inventory-divider"></div>

                        <div class="inventory-stat">
                            <div class="stat-icon-circle">
                                <svg width="37" height="37" viewBox="0 0 37 37" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12.3334 3.08325H24.6667M13.8751 3.08325V7.38296C13.8751 8.60055 13.5146 9.79088 12.8391 10.8039L11.8277 12.3209C11.152 13.3344 10.7915 14.5253 10.7917 15.7434V30.8333C10.7917 31.651 11.1166 32.4353 11.6948 33.0135C12.2731 33.5917 13.0573 33.9166 13.8751 33.9166H23.1251C23.9428 33.9166 24.7271 33.5917 25.3053 33.0135C25.8836 32.4353 26.2084 31.651 26.2084 30.8333V15.7419C26.2084 14.5243 25.8479 13.334 25.1724 12.3209L24.1611 10.8039C23.4853 9.79045 23.1248 8.59953 23.1251 7.38142V3.08325" stroke="#1E1E1E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10.7917 23.1248C12.0124 22.6134 13.3225 22.3501 14.6459 22.3501C15.9693 22.3501 17.2795 22.6134 18.5001 23.1248C19.7207 23.6362 21.0309 23.8995 22.3542 23.8995C23.6776 23.8995 24.9878 23.6362 26.2084 23.1248" stroke="#1E1E1E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <p class="stat-name">Milk</p>
                                <p class="stat-status" id="milkStatusText">In Stock</p>
                                <p class="stat-remaining" id="milkRemainingText">1000 ml remaining</p>
                                <div class="stat-progress-row">
                                    <div class="stat-progress-track">
                                        <div class="stat-progress-fill" id="milkFill" style="width: 100%;"></div>
                                    </div>
                                    <span class="stat-percent" id="milkPercentText">100%</span>
                                </div>
                            </div>
                        </div>

                        <div class="inventory-divider"></div>

                        <div class="inventory-stat">
                            <div class="stat-icon-circle">
                                <svg width="37" height="37" viewBox="0 0 37 37" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3.08325 14.6459C3.08325 19.4251 5.61159 23.5413 9.21909 25.2988C9.21909 25.0676 9.18825 24.8363 9.18825 24.6051C9.20367 21.5526 10.2674 18.5001 12.1328 15.9101C12.0558 15.8176 11.9941 15.7097 11.9016 15.6172C11.0691 14.5688 10.0208 13.2738 10.0208 10.8072C10.0208 8.34051 11.0691 7.04551 11.9016 5.99717C12.6262 5.08759 13.1041 4.48634 13.1041 3.11426C7.58492 3.11426 3.08325 8.29426 3.08325 14.6768V14.6459ZM16.1566 3.63842C16.0178 5.75051 15.0774 6.93759 14.3066 7.90884C13.582 8.81842 13.1041 9.41967 13.1041 10.7918C13.1041 12.1638 13.5358 12.7188 14.2141 13.5513C15.9562 11.9172 17.9912 10.6838 20.1341 9.99009C20.7816 9.77426 21.4291 9.62009 22.0766 9.49676C20.8895 6.70634 18.762 4.57884 16.1566 3.63842ZM24.6203 23.3563C24.4508 24.713 24.2195 26.378 22.4312 28.1663C20.6428 29.9547 18.9624 30.1705 17.6212 30.3555C16.6037 30.4943 15.8174 30.5868 14.9233 31.3422C16.6962 33.0997 19.0549 33.963 21.537 33.963C24.5433 33.963 27.7037 32.6988 30.1703 30.2322C33.8241 26.5938 34.8262 21.3984 33.007 17.4826C31.5116 18.6543 30.1087 18.8547 28.9524 18.9934C27.7962 19.1476 26.9637 19.2555 25.9153 20.3038C24.867 21.3522 24.7591 22.1847 24.6049 23.3409L24.6203 23.3563Z" fill="#483121"/>
                                    <path d="M21.1054 12.9192C19.185 13.5509 17.4415 14.6287 16.0179 16.0642C13.6592 18.423 12.3025 21.5371 12.2871 24.6205C12.2871 26.1159 12.5954 27.5342 13.1813 28.8138C14.6767 27.6421 16.0796 27.4571 17.2359 27.3029C18.3921 27.1488 19.2246 27.0409 20.2729 25.9925C21.3213 24.9442 21.4292 24.1117 21.5834 22.9555C21.7529 21.5988 21.9842 19.9338 23.7725 18.1455C25.5609 16.3571 27.2413 16.1413 28.5825 15.9563C29.6154 15.8175 30.3863 15.725 31.2804 14.9696C28.7675 12.4721 24.8671 11.6859 21.1209 12.9192H21.1054Z" fill="#483121"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <p class="stat-name">Coffee Beans</p>
                                <p class="stat-status" id="coffeeBeansStatusText">In Stock</p>
                                <p class="stat-remaining" id="coffeeBeansRemainingText">1000 g remaining</p>
                                <div class="stat-progress-row">
                                    <div class="stat-progress-track">
                                        <div class="stat-progress-fill" id="coffeeBeansFill" style="width: 100%;"></div>
                                    </div>
                                    <span class="stat-percent" id="coffeeBeansPercentText">100%</span>
                                </div>
                            </div>
                        </div>

                        <div class="inventory-divider"></div>

                        <div class="inventory-stat">
                            <div class="stat-icon-circle">
                                <svg width="37" height="37" viewBox="0 0 37 37" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.7532 33.9166C9.95663 33.9166 9.26956 33.6596 8.69195 33.1458C8.11433 32.6319 7.78647 31.9767 7.70836 31.1801L5.01045 6.51346C4.90767 5.61415 5.15844 4.81763 5.76278 4.12388C6.36711 3.43013 7.13126 3.08325 8.05524 3.08325H28.9448C29.8698 3.08325 30.6345 3.43013 31.2388 4.12388C31.8432 4.81763 32.0934 5.61415 31.9896 6.51346L29.2917 31.1801C29.2146 31.9767 28.8873 32.6319 28.3097 33.1458C27.732 33.6596 27.0445 33.9166 26.2469 33.9166H10.7532ZM10.4448 27.7499L10.7917 30.8333H26.2084L26.5552 27.7499H10.4448ZM10.1365 24.6666H26.9021L28.9063 6.16659H8.09378L10.1365 24.6666Z" fill="#1E1E1E"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <p class="stat-name">16oz Cup</p>
                                <p class="stat-status" id="cupsStatusText">In Stock</p>
                                <p class="stat-remaining" id="cupsRemainingText">500 pcs remaining</p>
                                <div class="stat-progress-row">
                                    <div class="stat-progress-track">
                                        <div class="stat-progress-fill" id="cupsFill" style="width: 100%;"></div>
                                    </div>
                                    <span class="stat-percent" id="cupsPercentText">100%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
    <!-- ADD PRODUCT MODAL -->
    <div class="modal-overlay" id="addProductOverlay">
        <div class="modal-box">
            <div class="modal-header">
                <div>
                    <h2>Add Product</h2>
                    <p>Add a new product to your POS</p>
                </div>
                <button class="modal-close" id="closeAddProduct" aria-label="Close">&times;</button>
            </div>

            <div class="modal-body">
                <!-- LEFT COLUMN -->
                <div class="modal-col">
                    <div class="panel">
                        <h3>Product Image</h3>
                        <div class="image-upload-row">
                            <div class="upload-box" id="uploadBox">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p class="upload-title">Upload Image</p>
                                <p class="upload-sub">PNG, JPG, WEBP up to 5mb</p>
                                <label class="choose-file-btn">
                                    <i class="fa-regular fa-image"></i> Choose File
                                    <input type="file" id="productImageInput" accept="image/png, image/jpeg, image/webp" hidden>
                                </label>
                            </div>
                            <div class="image-preview-box" id="imagePreviewBox" style="display:none;">
                                <img id="imagePreview" src="" alt="Product preview">
                                <button class="image-delete-btn" id="deleteImageBtn" aria-label="Remove image">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header-row">
                            <h3>Add Ons</h3>
                            <button class="btn-addon" id="addAddonBtn" type="button">
                                <i class="fa-solid fa-plus"></i> Add Ons
                            </button>
                        </div>
                        <div class="addon-columns">
                            <span>Add On Name</span>
                            <span>Additional Price</span>
                        </div>
                        <div id="addonList" class="addon-list">
                            <div class="addon-row">
                                <input type="text" class="addon-name" value="Whipped Cream">
                                <div class="addon-price-wrap">
                                    <div class="price-input"><span>₱</span><input type="number" class="addon-price" value="15.00" min="0" step="0.01"></div>
                                    <button class="addon-remove" type="button" aria-label="Remove add-on"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                            <div class="addon-row">
                                <input type="text" class="addon-name" value="Oat Milk">
                                <div class="addon-price-wrap">
                                    <div class="price-input"><span>₱</span><input type="number" class="addon-price" value="15.00" min="0" step="0.01"></div>
                                    <button class="addon-remove" type="button" aria-label="Remove add-on"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                            <div class="addon-row">
                                <input type="text" class="addon-name" value="Chocolate Drizzle">
                                <div class="addon-price-wrap">
                                    <div class="price-input"><span>₱</span><input type="number" class="addon-price" value="15.00" min="0" step="0.01"></div>
                                    <button class="addon-remove" type="button" aria-label="Remove add-on"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Additional Settings</h3>
                        <div class="settings-row">
                            <div class="field">
                                <label>Stock Quantity</label>
                                <input type="number" id="stockQty" placeholder="0" min="0">
                            </div>
                            <div class="field">
                                <label>Stock Status</label>
                                <select id="stockStatus">
                                    <option value="in-stock">In Stock</option>
                                    <option value="low-stock">Low Stock</option>
                                    <option value="out-of-stock">Out of Stock</option>
                                </select>
                            </div>
                        </div>
                        <div class="field toggle-field">
                            <label>Product Status</label>
                            <div class="toggle-row">
                                <label class="switch">
                                    <input type="checkbox" id="productStatus" checked>
                                    <span class="slider"></span>
                                </label>
                                <span id="statusLabel">Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="modal-col">
                    <div class="panel">
                        <h3>Basic Information</h3>
                        <div class="field">
                            <label>Product Name <span class="required">*</span></label>
                            <input type="text" id="productName" placeholder="Enter product name">
                        </div>
                        <div class="field">
                            <label>Category <span class="required">*</span></label>
                            <select id="productCategory">
                                <option value="" disabled selected>Select category</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Product Code <span class="optional">(Optional)</span></label>
                            <input type="text" id="productCode" placeholder="Enter product code">
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Prices</h3>
                        <div class="field">
                            <label>Selling Price <span class="required">*</span></label>
                            <div class="price-input"><span>₱</span><input type="number" id="sellingPrice" placeholder="0.00" min="0" step="0.01"></div>
                        </div>
                        <div class="settings-row">
                            <div class="field">
                                <label>Cost Price <span class="optional">(Optional)</span></label>
                                <div class="price-input"><span>₱</span><input type="number" id="costPrice" placeholder="0.00" min="0" step="0.01"></div>
                            </div>
                            <div class="field">
                                <label>Compare at Price <span class="optional">(Optional)</span></label>
                                <div class="price-input"><span>₱</span><input type="number" id="comparePrice" placeholder="0.00" min="0" step="0.01"></div>
                            </div>
                        </div>
                        <div class="field">
                            <label>Tax</label>
                            <select id="productTax">
                                <option>No Tax</option>
                                <option>VAT 12%</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" id="cancelAddProduct" type="button">Cancel</button>
                <button class="btn-save" id="saveProductBtn" type="button">Save Product</button>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.category-bar .cat-pill').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.category-bar .cat-pill').forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                const filter = this.getAttribute('data-filter');
                const cards = document.querySelectorAll('.product-card');
                cards.forEach(card => {
                    const categories = (card.getAttribute('data-category') || '').split(' ');
                    card.style.display = categories.includes(filter) ? '' : 'none';
                });
                
                // Re-attach order button handlers after filtering
                attachOrderButtonHandlers();
            });
        });
        document.querySelector('.category-bar .cat-pill.active')?.click();

        const viewToggle = document.getElementById("viewToggle");
        const productGrid = document.querySelector(".product-grid");
        const header = document.querySelector(".product-list-header");
        const icon = viewToggle.querySelector("i");

        viewToggle.addEventListener("click", () => {
            const isListView = productGrid.classList.toggle("list-view");
            header.classList.toggle("active", isListView);

            if (isListView) {
                icon.classList.remove("fa-bars");
                icon.classList.add("fa-table-cells");
            } else {
                icon.classList.remove("fa-table-cells");
                icon.classList.add("fa-bars");
            }
        });

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
            notifList.querySelectorAll(".notif-item.unread").forEach(item => {
                item.classList.remove("unread");
            });
            if (notifBadge) notifBadge.style.display = "none";
        });
        
        const addProductBtn = document.getElementById("addProductBtn");
        const addProductOverlay = document.getElementById("addProductOverlay");
        const closeAddProduct = document.getElementById("closeAddProduct");
        const cancelAddProduct = document.getElementById("cancelAddProduct");
        const saveProductBtn = document.getElementById("saveProductBtn");
        const productCategorySelect = document.getElementById("productCategory");

        function populateCategoryOptions() {
            productCategorySelect.innerHTML = '<option value="" disabled selected>Select category</option>';
            document.querySelectorAll('.category-bar .cat-pill').forEach(pill => {
                const value = pill.getAttribute('data-filter');
                const label = pill.textContent.trim();
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = label;
                productCategorySelect.appendChild(opt);
            });
        }

        function openAddProductModal() {
            populateCategoryOptions();
            addProductOverlay.classList.add("open");
        }

        function closeAddProductModal() {
            addProductOverlay.classList.remove("open");
            resetProductForm();
        }

        addProductBtn.addEventListener("click", openAddProductModal);
        closeAddProduct.addEventListener("click", closeAddProductModal);
        cancelAddProduct.addEventListener("click", closeAddProductModal);

        addProductOverlay.addEventListener("click", (e) => {
            if (e.target === addProductOverlay) closeAddProductModal();
        });
        const INVENTORY_KEY = 'boycold_inventory';

        async function loadInventory() {
            try {
                const response = await fetch('../api/pos_inventory_api.php?action=get_inventory');
                const data = await response.json();
                if (data.success) {
                    return data.inventory;
                }
            } catch (e) {
                console.error('Failed to load inventory:', e);
            }

            // Fallback defaults
            return {
                coffeeBeans: { current: 1000, max: 1000, unit: 'g' },
                milk: { current: 1000, max: 1000, unit: 'ml' },
                matcha: { current: 1000, max: 1000, unit: 'g' },
                chocolate: { current: 1000, max: 1000, unit: 'g' },
                cups: { current: 100, max: 100, unit: 'pcs' }
            };
        }

        async function saveInventory(inv) {
            try {
                const response = await fetch('../api/pos_inventory_api.php?action=update_inventory', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(inv)
                });
                const data = await response.json();
                return data.success;
            } catch (e) {
                console.error('Failed to save inventory:', e);
                return false;
            }
        }

        async function resetInventory() {
            try {
                const response = await fetch('../api/pos_inventory_api.php?action=reset_inventory');
                const data = await response.json();
                if (data.success) {
                    const fullStock = await loadInventory();
                    renderInventoryBar();
                    updateProductCardsStock();
                }
            } catch (e) {
                console.error('Failed to reset inventory:', e);
            }
        }

        function statusInfo(current, max) {
            const percentage = max > 0 ? (current / max) * 100 : 0;

            if (current <= 0) return { label: 'Out of Stock', cls: 'outstock' };
            if (percentage <= 20) return { label: 'Critical Stock', cls: 'critical' };
            if (percentage <= 50) return { label: 'Low Stock', cls: 'low' };
            return { label: 'In Stock', cls: 'instock' };
        }

        function renderInventoryStat(key, current, max, unit) {
            const statusEl = document.getElementById(key + 'StatusText');
            const remainingEl = document.getElementById(key + 'RemainingText');
            const fillEl = document.getElementById(key + 'Fill');
            const percentEl = document.getElementById(key + 'PercentText');
            if (!statusEl || !remainingEl || !fillEl || !percentEl) return;

            const { label, cls } = statusInfo(current, max);
            const percent = max > 0 ? Math.round((current / max) * 100) : 0;

            statusEl.textContent = label;
            statusEl.className = 'stat-status ' + cls;

            remainingEl.textContent = `${current} ${unit} remaining`;

            fillEl.style.width = percent + '%';
            fillEl.className = 'stat-progress-fill ' + cls;

            percentEl.textContent = percent + '%';
        }

        function renderInventoryBar() {
            const inv = loadInventory();
            renderInventoryStat('milk', inv.milk.current, inv.milk.max, inv.milk.unit || 'ml');
            renderInventoryStat('coffeeBeans', inv.coffeeBeans.current, inv.coffeeBeans.max, inv.coffeeBeans.unit || 'g');
            renderInventoryStat('cups', inv.cups.current, inv.cups.max, inv.cups.unit || 'pcs');
        }
        function getCategoryRequirements(category) {
            const cat = (category || '').trim();
            const needs = { cups: true, milk: true, coffeeBeans: false, matcha: false };

            if (cat.includes('coffee') && !cat.includes('non-coffee')) needs.coffeeBeans = true;
            if (cat.includes('matcha')) needs.matcha = true;
            if (cat === 'snacks') needs.milk = false;

            return needs;
        }

        function severityOf(current, max) {
            if (current <= 0) return 3;                         // out of stock
            const pct = max > 0 ? (current / max) * 100 : 0;
            if (pct <= 20) return 3;                             // critical -> treat as unavailable
            if (pct <= 50) return 2;                             // low
            return 1;                                            // in stock
        }

        function updateProductCardsStock() {
            const inv = loadInventory();

            document.querySelectorAll('.product-card').forEach(card => {
                const needs = getCategoryRequirements(card.getAttribute('data-category'));

                let worst = 1;
                if (needs.milk) worst = Math.max(worst, severityOf(inv.milk.current, inv.milk.max));
                if (needs.coffeeBeans) worst = Math.max(worst, severityOf(inv.coffeeBeans.current, inv.coffeeBeans.max));
                if (needs.matcha) worst = Math.max(worst, severityOf(inv.matcha.current, inv.matcha.max));
                if (needs.cups && inv.cups.current <= 0) worst = 3;

                const statusEl = card.querySelector('.drink-status');
                const ingredientSpan = card.querySelector('.drink-ingredient span');
                const cupsValueSpan = card.querySelector('.drink-cups .cups-value') || card.querySelector('.drink-cups span:last-child');
                const servingsValueSpan = card.querySelector('.drink-servings .servings-value');
                const orderBtn = card.querySelector('.btn-order');
                if (!statusEl || !ingredientSpan || !cupsValueSpan) return;

                statusEl.classList.remove('available', 'low', 'unavailable');

                if (worst >= 3) {
                    statusEl.classList.add('unavailable');
                    statusEl.innerHTML = '<span class="status-dot"></span>Unavailable';
                    ingredientSpan.textContent = 'Insufficient';
                } else if (worst === 2) {
                    statusEl.classList.add('low');
                    statusEl.innerHTML = '<span class="status-dot"></span>Low Stock';
                    ingredientSpan.textContent = 'Low';
                } else {
                    statusEl.classList.add('available');
                    statusEl.innerHTML = '<span class="status-dot"></span>Available';
                    ingredientSpan.textContent = 'Sufficient';
                }
                cupsValueSpan.textContent = inv.cups.current;
                if (servingsValueSpan) servingsValueSpan.textContent = inv.cups.current;


                if (orderBtn) {
                    orderBtn.disabled = worst >= 3;
                    orderBtn.style.opacity = worst >= 3 ? '0.4' : '1';
                    orderBtn.style.cursor = worst >= 3 ? 'not-allowed' : 'pointer';
                }
            });
        }

        function attachOrderButtonHandlers() {
            document.querySelectorAll('.product-card').forEach(card => {
                const orderBtn = card.querySelector('.btn-order');
                if (!orderBtn) return;

                // Remove existing handler if present to avoid duplicates
                orderBtn.dataset.navReady = 'false';

                orderBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (orderBtn.disabled) return;

                    const name = card.querySelector('.card-name')?.textContent.trim() || '';
                    const priceText = card.querySelector('.card-price')?.textContent.trim() || '₱0.00';
                    const price = parseFloat(priceText.replace('₱', '').replace(',', '')) || 0;
                    const img = card.querySelector('.card-image img')?.getAttribute('src') || '';
                    const id = card.getAttribute('data-id') || '';
                    const category = card.getAttribute('data-category') || '';

                    const product = { id, name, price, img, category };
                    // Only clear cart if this is a fresh start (not continuing an existing order)
                    // Check if we have items in cart - if yes, we're continuing an order
                    fetch('../api/pos_cart_api.php?action=get_cart')
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.cart && data.cart.length === 0) {
                                // Only clear if cart is empty (fresh start)
                                fetch('../api/pos_cart_api.php?action=clear_cart').catch(() => {});
                            }
                        })
                        .catch(() => {
                            // On error, clear cart to be safe
                            fetch('../api/pos_cart_api.php?action=clear_cart').catch(() => {});
                        });
                    
                    localStorage.setItem('boycold_current_product', JSON.stringify(product));
                    window.location.href = 'ordersum.php';
                });

                orderBtn.dataset.navReady = 'true';
            });
        }

        // Save the defaults on first load, then render
        saveInventory(loadInventory());
        renderInventoryBar();
        document.querySelectorAll('.product-card').forEach(setupListViewStock);
        updateProductCardsStock();
        attachOrderButtonHandlers();

        const resetStockBtn = document.getElementById('resetStockBtn');
        if (resetStockBtn) {
            resetStockBtn.addEventListener('click', () => {
                if (confirm('Reset all ingredient stock back to full?')) {
                    resetInventory();
                }
            });
        }

        // Image upload
        const productImageInput = document.getElementById("productImageInput");
        const imagePreviewBox = document.getElementById("imagePreviewBox");
        const imagePreview = document.getElementById("imagePreview");
        const uploadBox = document.getElementById("uploadBox");
        const deleteImageBtn = document.getElementById("deleteImageBtn");

        productImageInput.addEventListener("change", (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (evt) => {
                imagePreview.src = evt.target.result;
                imagePreviewBox.style.display = "flex";
            };
            reader.readAsDataURL(file);
        });

        deleteImageBtn.addEventListener("click", () => {
            imagePreview.src = "";
            imagePreviewBox.style.display = "none";
            productImageInput.value = "";
        });

        // Add-ons
        const addonList = document.getElementById("addonList");
        const addAddonBtn = document.getElementById("addAddonBtn");

        function createAddonRow() {
            const row = document.createElement("div");
            row.className = "addon-row";
            row.innerHTML = `
                <input type="text" class="addon-name" placeholder="Add-on name">
                <div class="addon-price-wrap">
                    <div class="price-input"><span>₱</span><input type="number" class="addon-price" placeholder="0.00" min="0" step="0.01"></div>
                    <button class="addon-remove" type="button" aria-label="Remove add-on"><i class="fa-solid fa-xmark"></i></button>
                </div>
            `;
            row.querySelector(".addon-remove").addEventListener("click", () => row.remove());
            addonList.appendChild(row);
        }

        addAddonBtn.addEventListener("click", createAddonRow);

        addonList.querySelectorAll(".addon-remove").forEach(btn => {
            btn.addEventListener("click", () => btn.closest(".addon-row").remove());
        });

        // Toggle status label
        const productStatus = document.getElementById("productStatus");
        const statusLabel = document.getElementById("statusLabel");
        productStatus.addEventListener("change", () => {
            statusLabel.textContent = productStatus.checked ? "Active" : "Inactive";
        });

        // Save product
        function resetProductForm() {
            document.getElementById("productName").value = "";
            document.getElementById("productCategory").value = "";
            document.getElementById("productCode").value = "";
            document.getElementById("sellingPrice").value = "";
            document.getElementById("costPrice").value = "";
            document.getElementById("comparePrice").value = "";
            document.getElementById("stockQty").value = "";
            document.getElementById("productTax").value = "No Tax";
            productStatus.checked = true;
            statusLabel.textContent = "Active";
            imagePreview.src = "";
            imagePreviewBox.style.display = "none";
            productImageInput.value = "";
            document.querySelectorAll(".field input, .field select").forEach(el => el.classList.remove("invalid"));
        }

        function slugify(text) {
            return text.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        }

        saveProductBtn.addEventListener("click", () => {
            const nameInput = document.getElementById("productName");
            const categorySelect = document.getElementById("productCategory");
            const priceInput = document.getElementById("sellingPrice");

            let valid = true;
            [nameInput, categorySelect, priceInput].forEach(el => el.classList.remove("invalid"));

            if (!nameInput.value.trim()) {
                nameInput.classList.add("invalid");
                valid = false;
            }
            if (!categorySelect.value) {
                categorySelect.classList.add("invalid");
                valid = false;
            }
            if (!priceInput.value || parseFloat(priceInput.value) <= 0) {
                priceInput.classList.add("invalid");
                valid = false;
            }

            if (!valid) return;

            const name = nameInput.value.trim();
            const category = categorySelect.value;
            const price = parseFloat(priceInput.value).toFixed(2);
            const imgSrc = imagePreview.src || "../img/ChatGPT Jul 1, 2026, 12_58_44 PM 1.png";
            const id = slugify(name) + "-" + Date.now();

            const card = document.createElement("div");
            card.className = "product-card";
            card.setAttribute("data-category", category);
            card.setAttribute("data-id", id);
            card.innerHTML = `
                <div class="card-image">
                    <div class="card-image-placeholder">
                        <img src="${imgSrc}">
                    </div>
                </div>
                <div class="card-info">
                    <div class="card-mid">
                        <p class="card-name">${name}</p>
                    </div>
                    <div class="card-footer">
                        <p class="card-price">₱${price}</p>
                        <div class="drink-stock">
                            <p class="drink-status available">
                                <span class="status-dot"></span>
                                Available
                            </p>

                            <p class="drink-ingredient">
                                Ingredients: <span>Sufficient</span>
                            </p>

                            <p class="drink-cups">
                                Cups: <span>40 pcs</span>
                            </p>
                        </div>
                        <button class="card-btn btn-order" aria-label="Add to order">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            `;

            productGrid.appendChild(card);
            setupListViewStock(card);

            updateProductCardsStock();
            attachOrderButtonHandlers();

            const activeFilter = document.querySelector('.category-bar .cat-pill.active')?.getAttribute('data-filter');
            if (activeFilter && category !== activeFilter) {
                card.style.display = "none";
            }

            closeAddProductModal();
        });
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');

                // Only intercept placeholder links; let real page links navigate normally
                if (href === '#') {
                    e.preventDefault();
                }

                document.querySelectorAll('.sidebar-nav a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        function setupListViewStock(card) {
            if (card.dataset.listViewReady === "true") return;

            const drinkStock = card.querySelector('.drink-stock');
            const cupsEl = card.querySelector('.drink-cups');
            if (!drinkStock || !cupsEl) return;

            const cupsSpan = cupsEl.querySelector('span');
            const cupsValue = cupsSpan ? (cupsSpan.textContent.match(/\d+/) || ['0'])[0] : '0';

            cupsEl.innerHTML = `<span class="stock-label">Cups: </span><span>${cupsValue}</span>`;

            const servingsEl = document.createElement('p');
            servingsEl.className = 'drink-servings';
            servingsEl.innerHTML = `<span class="stock-label">Servings: </span><span>${cupsValue}</span>`;
            drinkStock.insertBefore(servingsEl, cupsEl);

            card.dataset.listViewReady = "true";
        }

    </script>
    <script src="order-notify.js"></script>
</body>
</html>