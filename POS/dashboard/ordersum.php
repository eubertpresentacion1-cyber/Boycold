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

// Get employee name for receipt
$employeeName = isset($_SESSION['employee_name']) ? $_SESSION['employee_name'] : 'Unknown Cashier';

// Get current shift info
$currentShiftInfo = null;
if ($shiftResult) {
    $shiftInfoStmt = $connect->prepare("SELECT id, opened_at FROM shift_logs WHERE id = ?");
    $shiftInfoStmt->bind_param('i', $shiftResult['id']);
    $shiftInfoStmt->execute();
    $currentShiftInfo = $shiftInfoStmt->get_result()->fetch_assoc();
    $shiftInfoStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dash-css/pos-menu.css">
    <link rel="stylesheet" href="dash-css/order-notify.css">
    <link rel="stylesheet" href="dash-css/ordersum.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Order</title>
</head>
<body>

    <div class="app-shell" id="appShell">

        <!-- SIDEBAR (identical to posmenu.html) -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true">
                    <img src="../img/ChatGPT Image Jul 1, 2026, 12_58_44 PM 1.png" alt="">
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
                            <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                            <span class="nav-icon"><i class="fa-regular fa-clock"></i></span>
                            <span class="nav-label">Order History</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="pos-settings.php">
                            <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
                            <span class="nav-label">POS Settings</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-divider"></div>

                <ul>
                    <li>
                        <a href="pos-shift.php">
                            <span class="nav-icon"><i class="fa-regular fa-folder-open"></i></span>
                            <span class="nav-label">Open / Close Shift</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <span class="nav-icon"><i class="fa-regular fa-credit-card"></i></span>
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

                <div class="notif-wrap">
                    <button class="icon-btn" id="notifBtn" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <span class="icon-badge" id="notifBadge">3</span>
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
                        </div>
                        <a href="#" class="notif-footer">View all notifications <i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                </div>

                <div class="header-divider"></div>

                <button class="profile-btn">
                    <div class="profile-avatar">A</div>
                    <span class="profile-name"><?= htmlspecialchars($branchName) ?></span>
                    <i class="fa-solid fa-chevron-down profile-caret"></i>
                </button>
            </div>

            <!-- ORDER CUSTOMIZATION PAGE -->
            <div class="order-page" id="orderPage">
                <a href="pos-menu.php" class="back-link" id="backToMenu">
                    <i class="fa-solid fa-arrow-left"></i> Back to Menu
                </a>

                <div class="order-layout" id="orderLayout">

                    <!-- LEFT: PRODUCT + OPTIONS -->
                    <div class="order-left">

                        <!-- CUSTOMIZATION VIEW -->
                        <div id="customizeView">

                            <div class="product-display">
                                <div class="product-photo">
                                    <img id="productImg" src="" alt="">
                                </div>
                                <div class="product-title-block">
                                    <h2 id="productName">Loading...</h2>
                                    <p class="product-price" id="productPrice">₱0.00</p>
                                </div>
                            </div>

                            <div class="options-panel">

                                <div class="option-group">
                                    <div class="option-label"><i class="fa-solid fa-mug-saucer"></i> Milk Choice</div>
                                    <div class="option-buttons" id="milkOptions">
                                        <button class="option-btn active" data-value="Original" data-price="0" type="button">Original</button>
                                        <button class="option-btn" data-value="Oat Milk" data-price="15" type="button">Oat Milk <span>+₱15</span></button>
                                    </div>
                                </div>

                                <div class="option-group">
                                    <div class="option-label"><i class="fa-solid fa-plus"></i> Add - ons</div>
                                    <div class="option-buttons multi" id="addonOptions">
                                        <button class="option-btn" data-value="Espresso Shot" data-price="15" type="button">Espresso Shot <span>+₱15</span></button>
                                        <button class="option-btn" data-value="Whipped Cream" data-price="15" type="button">Whipped Cream <span>+₱15</span></button>
                                        <button class="option-btn" data-value="Chocolate Drizzle" data-price="15" type="button">Chocolate Drizzle <span>+₱15</span></button>
                                    </div>
                                </div>

                                <div class="option-group">
                                    <div class="option-label"><i class="fa-solid fa-bag-shopping"></i> Order type</div>
                                    <div class="option-buttons" id="orderTypeOptions">
                                        <button class="option-btn active" data-value="Dine In" type="button">Dine In</button>
                                        <button class="option-btn" data-value="Take Out" type="button">Take Out</button>
                                    </div>
                                </div>

                                <div class="option-group">
                                    <div class="option-label"><i class="fa-solid fa-cart-shopping"></i> Quantity</div>
                                    <div class="qty-stepper">
                                        <button id="qtyMinus" type="button" aria-label="Decrease quantity">−</button>
                                        <span id="qtyValue">1</span>
                                        <button id="qtyPlus" type="button" aria-label="Increase quantity">+</button>
                                    </div>
                                </div>

                            </div>

                            <div class="total-bar">
                                <div>
                                    <p class="total-label">Total:</p>
                                    <p class="total-value" id="totalValue">₱0.00</p>
                                </div>
                                <div class="button-group">
                                    <button class="add-order-btn" id="addOrderBtn" type="button">Add Order</button>
                                    <button class="confirm-btn" id="confirmBtn" type="button">Confirm</button>
                                </div>
                            </div>
                        </div>
                        <!-- closes #customizeView -->

                        <div id="checkoutWrapper" style="display: none;">

                            <div id="checkoutView">

                                <div class="checkout-header">
                                    <h2>Check Out</h2>
                                    <p>Choose payment method and complete the order</p>
                                </div>

                                <div class="checkout-panel">
                                    <h3>Payment Method</h3>
                                    <div class="payment-methods">
                                        <button class="payment-option active" data-method="cash" type="button">
                                            <span class="payment-check"><i class="fa-solid fa-check"></i></span>
                                            <svg width="43" height="34" viewBox="0 0 43 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M10 23.5H3.25C2.65326 23.5 2.08097 23.2629 1.65901 22.841C1.23705 22.419 1 21.8467 1 21.25V3.25C1 2.65326 1.23705 2.08097 1.65901 1.65901C2.08097 1.23705 2.65326 1 3.25 1H30.25C30.8467 1 31.419 1.23705 31.841 1.65901C32.2629 2.08097 32.5 2.65326 32.5 3.25V10" stroke="#9E6741" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M10 12.25C10 11.6533 10.2371 11.081 10.659 10.659C11.081 10.2371 11.6533 10 12.25 10H39.25C39.8467 10 40.419 10.2371 40.841 10.659C41.2629 11.081 41.5 11.6533 41.5 12.25V30.25C41.5 30.8467 41.2629 31.419 40.841 31.841C40.419 32.2629 39.8467 32.5 39.25 32.5H12.25C11.6533 32.5 11.081 32.2629 10.659 31.841C10.2371 31.419 10 30.8467 10 30.25V12.25Z" stroke="#9E6741" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M21.25 21.25C21.25 22.4435 21.7241 23.5881 22.568 24.432C23.4119 25.2759 24.5565 25.75 25.75 25.75C26.9435 25.75 28.0881 25.2759 28.932 24.432C29.7759 23.5881 30.25 22.4435 30.25 21.25C30.25 20.0565 29.7759 18.9119 28.932 18.068C28.0881 17.2241 26.9435 16.75 25.75 16.75C24.5565 16.75 23.4119 17.2241 22.568 18.068C21.7241 18.9119 21.25 20.0565 21.25 21.25Z" stroke="#9E6741" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <span class="payment-name">Cash</span>
                                            <span class="payment-sub">Pay with cash</span>
                                        </button>
                                        <button class="payment-option" data-method="gcash" type="button">
                                            <span class="payment-check"><i class="fa-solid fa-check"></i></span>
                                            <img src="../img/gcash2821.logowik.com 1.png" alt="">
                                            <span class="payment-name">GCash</span>
                                            <span class="payment-sub">Pay with GCash</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="checkout-panel" id="cashPaymentPanel">
                                    <h3>Cash Payment</h3>
                                    <div class="field">
                                        <label>Amount Tendered</label>
                                        <div class="price-input">
                                            <span>₱</span>
                                            <input type="number" id="amountTendered" placeholder="Enter amount tendered" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="change-block">
                                        <p class="change-label">Change</p>
                                        <p class="change-value" id="changeValue">₱0.00</p>
                                    </div>
                                </div>

                                <div class="checkout-panel gcash-panel" id="gcashPaymentPanel" style="display: none;">
                                    <h3>GCash Payment</h3>
                                    <p class="gcash-note">Ask the customer to scan the QR code or send payment to the store's GCash number, then confirm below.</p>
                                </div>

                            </div>
                            <!-- closes #checkoutView -->

                            <div class="checkout-footer">
                                <button class="cancel-order-btn" id="cancelOrderBtn" type="button">Cancel Order</button>
                                <button class="complete-payment-btn" id="completePaymentBtn" type="button" style="opacity:1; pointer-events:auto; cursor:pointer;">Complete Payment</button>
                            </div>

                        </div>
                    </div>

                    <!-- RIGHT: ORDER SUMMARY -->
                    <div class="order-right">
                        <div class="summary-header">
                            <span id="summaryTitle">Order Summary (0)</span>
                            <a href="#" id="clearAllBtn" class="clear-all"><i class="fa-regular fa-trash-can"></i> Clear All</a>
                        </div>

                        <div class="summary-list" id="summaryList">
                            <p class="summary-empty" id="summaryEmpty">No items yet. Add something from the menu!</p>
                        </div>

                        <div class="summary-totals">
                            <div class="row"><span>Subtotal</span><span id="subtotalValue">₱0.00</span></div>
                            <div class="row total-row"><span>Total</span><span id="grandTotalValue">₱0.00</span></div>
                        </div>

                        <button class="checkout-btn" id="checkoutBtn" type="button">
                            Proceed to Checkout <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>

                </div>
                <div class="receipt-page" id="receiptOverlay">
                    <div class="receipt-header">
                        <h2>Receipt Preview</h2>
                        <p>Review the receipt details below. Click "Print Receipt" to generate and print.</p>
                    </div>

                    <div class="receipt-paper" id="receiptPaper">

                        <div class="receipt-logo">
                            <img src="../img/ChatGPT Image Jul 1, 2026, 12_58_44 PM 1.png" alt="BoyCold Cafe">
                        </div>
                        <h1 class="receipt-brand">Boycold Cafe</h1>
                        <p class="receipt-address">123 Calle Jose Highway, Sta Barbara</p>
                        <p class="receipt-address">Baliuag, Bulacan</p>

                        <div class="receipt-divider"></div>

                        <h2 class="receipt-title">RECEIPT</h2>

                        <div class="receipt-info">
                            <div class="receipt-info-row"><span>Receipt No.</span><span>:</span><span id="receiptNo">-</span></div>
                            <div class="receipt-info-row"><span>Date</span><span>:</span><span id="receiptDate">-</span></div>
                            <div class="receipt-info-row"><span>Time</span><span>:</span><span id="receiptTime">-</span></div>
                            <div class="receipt-info-row"><span>Shift</span><span>:</span><span id="receiptShift">-</span></div>
                            <div class="receipt-info-row"><span>Branch</span><span>:</span><span id="receiptBranch">-</span></div>
                            <div class="receipt-info-row"><span>Cashier</span><span>:</span><span id="receiptCashier">-</span></div>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="receipt-items">
                            <div class="receipt-items-header">
                                <span>Item</span>
                                <span>Qty</span>
                                <span>Price</span>
                                <span>Amount</span>
                            </div>
                            <div id="receiptItemsList"><!-- rows injected by ordersum.js --></div>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="receipt-totals">
                            <div class="receipt-totals-row"><span>Subtotal</span><span id="receiptSubtotal">₱0.00</span></div>
                            <div class="receipt-totals-row receipt-discount"><span>Discount</span><span id="receiptDiscount">₱0.00</span></div>
                        </div>

                        <div class="receipt-grandtotal">
                            <span>TOTAL</span>
                            <span id="receiptGrandTotal">₱0.00</span>
                        </div>

                        <div class="receipt-divider"></div>

                        <div class="receipt-payment">
                            <div class="receipt-info-row"><span>Payment Method</span><span>:</span><span id="receiptMethod">Cash</span></div>
                            <div class="receipt-info-row" id="receiptTenderedRow"><span>Amount Tendered</span><span>:</span><span id="receiptTendered">₱0.00</span></div>
                            <div class="receipt-info-row" id="receiptChangeRow"><span>Change</span><span>:</span><span id="receiptChange" class="receipt-change">₱0.00</span></div>
                        </div>

                        <div class="receipt-divider"></div>

                        <p class="receipt-thanks">Thank you and see you again!</p>
                    </div>

                    <div class="receipt-actions">
                        <button type="button" class="new-order-btn" id="newOrderBtn">Back to Menu</button>
                        <button class="print-receipt-btn" id="printReceiptBtn" type="button"><i class="fa-solid fa-print"></i> Print Receipt</button>
                    </div>

                </div>

            </div>
        </div>
    </div>

    

    <script>
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

        // ── Load the product picked on posmenu.html ──
        const productRaw = localStorage.getItem('boycold_current_product');
        let currentProduct = { id: '', name: 'Unknown Item', price: 0, img: '', category: '' };

        if (productRaw) {
            try { currentProduct = JSON.parse(productRaw); } catch (e) {}
        } else {
            // No product selected — send back to the menu
            window.location.href = 'pos-menu.php';
        }

        document.getElementById('productImg').src = currentProduct.img;
        document.getElementById('productName').textContent = currentProduct.name;
        document.getElementById('productPrice').textContent = `₱${currentProduct.price.toFixed(2)}`;

        // Bites/snacks customization rules:
        // - Waffles, quesadillas, beef nachos, and the Messy Tuna Spinach
        //   are served as-is — no milk or add-on options at all.
        // - French Fries gets a set of flavor add-ons (cheese sauce, cheese
        //   powder, BBQ powder, sour cream powder), all the same price.
        // - Both "poppers" items (Chicken Poppers, and the Fries and
        //   Chicken Poppers combo) only offer a cheese sauce dip, priced
        //   differently from the fries flavor add-ons.
        // Everything else (coffee/drinks) keeps the default Milk Choice +
        // generic Add-ons already in the markup.
        const FRIES_ADDONS = [
            { value: 'Cheese Sauce', price: 30 },
            { value: 'Cheese Powder', price: 30 },
            { value: 'BBQ Powder', price: 30 },
            { value: 'Sour Cream Powder', price: 30 }
        ];
        const POPPERS_ADDONS = [
            { value: 'Cheese Sauce', price: 40 }
        ];

        function getProductType(product) {
            const category = (product.category || '').trim().toLowerCase();
            if (category !== 'snacks') return 'default';

            const name = (product.name || '').toLowerCase();
            const id = (product.id || '').toLowerCase();
            const has = (needle) => name.includes(needle) || id.includes(needle);

            if (has('waffle') || has('quesadilla') || has('nachos') || has('tuna')) return 'no-customization';
            if (has('poppers')) return 'poppers'; // Chicken Poppers + Fries and Chicken Poppers
            if (has('fries')) return 'fries'; // French Fries only (poppers combo already matched above)
            return 'default';
        }

        // Kept for the spots that only care about the hide-everything case.
        function isNoCustomizationProduct(product) {
            return getProductType(product) === 'no-customization';
        }

        const productType = getProductType(currentProduct);
        const isNoCustomization = productType === 'no-customization';
        const hideMilkOnly = productType === 'fries' || productType === 'poppers';

        if (isNoCustomization) {
            document.querySelector('.option-group:nth-of-type(1)').style.display = 'none'; // Milk Choice
            document.querySelector('.option-group:nth-of-type(2)').style.display = 'none'; // Add-ons
        } else if (hideMilkOnly) {
            document.querySelector('.option-group:nth-of-type(1)').style.display = 'none'; // Milk Choice
        }

        // ── Option state ──
        let selectedMilk = { value: 'Original', price: 0 };
        let selectedAddons = [];
        let selectedOrderType = 'Dine In';
        let quantity = 1;

        // Milk (single-select)
        const milkOptions = document.getElementById('milkOptions');
        milkOptions.querySelectorAll('.option-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                milkOptions.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedMilk = { value: btn.dataset.value, price: parseFloat(btn.dataset.price) || 0 };
                updateTotal();
            });
        });

        // Add-ons (multi-select) — swap in the fries/poppers add-on set
        // when applicable, otherwise use the default buttons already in
        // the markup.
        const addonOptions = document.getElementById('addonOptions');

        function bindAddonButtonEvents() {
            addonOptions.querySelectorAll('.option-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    btn.classList.toggle('active');
                    const value = btn.dataset.value;
                    const price = parseFloat(btn.dataset.price) || 0;
                    if (btn.classList.contains('active')) {
                        selectedAddons.push({ value, price });
                    } else {
                        selectedAddons = selectedAddons.filter(a => a.value !== value);
                    }
                    updateTotal();
                });
            });
        }

        function renderAddonButtons(items) {
            addonOptions.innerHTML = items.map(item =>
                `<button class="option-btn" data-value="${item.value}" data-price="${item.price}" type="button">${item.value} <span>+₱${item.price}</span></button>`
            ).join('');
        }

        if (productType === 'fries') {
            renderAddonButtons(FRIES_ADDONS);
        } else if (productType === 'poppers') {
            renderAddonButtons(POPPERS_ADDONS);
        }
        bindAddonButtonEvents();

        // Order type (single-select)
        const orderTypeOptions = document.getElementById('orderTypeOptions');
        orderTypeOptions.querySelectorAll('.option-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                orderTypeOptions.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedOrderType = btn.dataset.value;
            });
        });

        // Quantity stepper
        const qtyValue = document.getElementById('qtyValue');
        document.getElementById('qtyMinus').addEventListener('click', () => {
            if (quantity > 1) {
                quantity--;
                qtyValue.textContent = quantity;
                updateTotal();
            }
        });
        document.getElementById('qtyPlus').addEventListener('click', () => {
            quantity++;
            qtyValue.textContent = quantity;
            updateTotal();
        });

        function calculateItemTotal() {
            if (isNoCustomizationProduct(currentProduct)) {
                return currentProduct.price * quantity;
            }
            
            const addonsTotal = selectedAddons.reduce((sum, a) => sum + a.price, 0);
            return (currentProduct.price + selectedMilk.price + addonsTotal) * quantity;
        }

        function updateTotal() {
            document.getElementById('totalValue').textContent = `₱${calculateItemTotal().toFixed(2)}`;
        }
        updateTotal();

        // ── Cart (persisted in database via API) ──
        async function getCart() {
            try {
                const response = await fetch('../api/pos_cart_api.php?action=get_cart');
                const data = await response.json();
                if (data.success) {
                    return data.cart;
                }
                return [];
            } catch (e) {
                console.error('Failed to fetch cart:', e);
                return [];
            }
        }

        async function saveCartItem(item) {
            try {
                const response = await fetch('../api/pos_cart_api.php?action=add_to_cart', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(item)
                });
                const data = await response.json();
                return data.success;
            } catch (e) {
                console.error('Failed to save cart item:', e);
                return false;
            }
        }

        async function removeCartItem(cartItemId) {
            try {
                const response = await fetch(`../api/pos_cart_api.php?action=remove_from_cart&cart_item_id=${cartItemId}`);
                const data = await response.json();
                return data.success;
            } catch (e) {
                console.error('Failed to remove cart item:', e);
                return false;
            }
        }

        async function clearCart() {
            try {
                const response = await fetch('../api/pos_cart_api.php?action=clear_cart');
                const data = await response.json();
                return data.success;
            } catch (e) {
                console.error('Failed to clear cart:', e);
                return false;
            }
        }

        async function renderSummary() {
            const cart = await getCart();
            const summaryList = document.getElementById('summaryList');
            const summaryEmpty = document.getElementById('summaryEmpty');
            document.getElementById('summaryTitle').textContent = `Order Summary (${cart.length})`;

            summaryList.querySelectorAll('.summary-item').forEach(el => el.remove());

            if (cart.length === 0) {
                summaryEmpty.style.display = 'block';
            } else {
                summaryEmpty.style.display = 'none';
                cart.forEach((item, index) => {
                    const el = document.createElement('div');
                    el.className = 'summary-item';
                    const addons = Array.isArray(item.addons)
                        ? item.addons
                        : typeof item.addons === 'string'
                            ? item.addons.split(',').map(value => ({ value: value.trim(), price: 0 })).filter(a => a.value)
                            : [];
                    
                    // For waffles/quesadillas/nachos, don't show milk/addons, just quantity.
                    // For fries/poppers, show addons but skip the milk line (doesn't apply to food).
                    const itemType = getProductType(item);
                    let detailsHtml = '';
                    if (itemType === 'no-customization') {
                        detailsHtml = `
                            <ul>
                                <li>Order Type: ${item.orderType}</li>
                                <li>Qty: ${item.qty}</li>
                            </ul>
                        `;
                    } else {
                        const addonsText = addons.length
                            ? addons.map(a => a.value).join(', ')
                            : 'No Add-ons';
                        const milkLine = itemType === 'default' ? `<li>${item.milk}</li>` : '';
                        detailsHtml = `
                            <ul>
                                ${milkLine}
                                <li>${addonsText}</li>
                                <li>Order Type: ${item.orderType}</li>
                                <li>Qty: ${item.qty}</li>
                            </ul>
                        `;
                    }
                    
                    el.innerHTML = `
                        <img src="${item.img}" alt="">
                        <div class="summary-item-info">
                            <div class="summary-item-top">
                                <p class="summary-item-name">${item.name}</p>
                                <p class="summary-item-price">₱${item.itemTotal.toFixed(2)}</p>
                            </div>
                            ${detailsHtml}
                        </div>
                        <button class="summary-remove" data-index="${index}" data-cart-id="${item.id}" aria-label="Remove item"><i class="fa-solid fa-xmark"></i></button>
                    `;
                    summaryList.appendChild(el);
                });
            }

            const subtotal = cart.reduce((sum, item) => sum + (parseFloat(item.itemTotal) || 0), 0);
            document.getElementById('subtotalValue').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('grandTotalValue').textContent = `₱${subtotal.toFixed(2)}`;

            summaryList.querySelectorAll('.summary-remove').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const cartId = btn.dataset.cartId;
                    const success = await removeCartItem(cartId);
                    if (success) {
                        renderSummary();
                    }
                });
            });
        }

        // Confirm — add current customization to cart
        document.getElementById('confirmBtn').addEventListener('click', async () => {
            // Prevent adding items with no price (placeholder products)
            if (!currentProduct.id || currentProduct.price <= 0) {
                alert('Please select a product from the menu first.');
                window.location.href = 'pos-menu.php';
                return;
            }
            
            const confirmProductType = getProductType(currentProduct);
            const isNoCustomization = confirmProductType === 'no-customization';
            const hideMilk = isNoCustomization || confirmProductType === 'fries' || confirmProductType === 'poppers';
            
            const cartItem = {
                product_id: currentProduct.id,
                name: currentProduct.name,
                img: currentProduct.img,
                category: currentProduct.category,
                basePrice: currentProduct.price,
                milk: hideMilk ? '' : selectedMilk.value,
                milkPrice: hideMilk ? 0 : selectedMilk.price,
                addons: isNoCustomization ? [] : selectedAddons,
                orderType: selectedOrderType,
                qty: quantity,
                itemTotal: calculateItemTotal()
            };
            
            const success = await saveCartItem(cartItem);
            if (success) {
                renderSummary();

                // Reset the form for the next customization
                quantity = 1;
                qtyValue.textContent = 1;
                selectedAddons = [];
                addonOptions.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                milkOptions.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                milkOptions.querySelector('.option-btn').classList.add('active');
                selectedMilk = { value: 'Original', price: 0 };
                orderTypeOptions.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                orderTypeOptions.querySelector('.option-btn').classList.add('active');
                selectedOrderType = 'Dine In';
                updateTotal();
            } else {
                alert('Failed to add item to cart. Please try again.');
            }
        });

        // Clear all
        document.getElementById('clearAllBtn').addEventListener('click', async (e) => {
            e.preventDefault();
            const cart = await getCart();
            if (cart.length === 0) return;
            if (confirm('Clear all items from the order summary?')) {
                const success = await clearCart();
                if (success) {
                    renderSummary();
                }
            }
        });

        // Add Order - go back to menu to add more items to current order
        document.getElementById('addOrderBtn').addEventListener('click', async (e) => {
            e.preventDefault();
            // Save current cart state and go back to menu
            window.location.href = 'pos-menu.php';
        });

        const customizeView = document.getElementById('customizeView');
        const checkoutWrapper = document.getElementById('checkoutWrapper');
        const checkoutBtnEl = document.getElementById('checkoutBtn');
        const backToMenu = document.getElementById('backToMenu');
        const appShell = document.getElementById('appShell');
        const orderLayout = document.getElementById('orderLayout');

        // ── Clear cart when going back to menu ──
        // When user clicks "Back to Menu", clear the current product selection
        // but preserve the cart for continuing the order
        if (backToMenu) {
            backToMenu.addEventListener('click', (e) => {
                // Clear the current product selection
                localStorage.removeItem('boycold_current_product');
                // Cart is preserved in database so user can continue if they return
            });
        }

        // ── Printable receipt overlay ──
        // NOTE: this id must match the id on the ".receipt-page" element in the
        // HTML. Previously the element had no id at all (only the class
        // "receipt-page"), so this lookup returned null and every call below threw
        // a TypeError — which also meant the receipt markup had no default
        // "display: none" applied to it via JS/CSS and stayed visible underneath
        // the order customization and checkout screens the whole time.
        const receiptOverlay = document.getElementById('receiptOverlay');

        checkoutBtnEl.addEventListener('click', () => {
            if (getCart().length === 0) {
                alert('Your order summary is empty.');
                return;
            }
            customizeView.style.display = 'none';
            checkoutWrapper.style.display = 'block';
            checkoutBtnEl.style.display = 'none';
            receiptOverlay.style.display = 'none';
        });

        document.getElementById('cancelOrderBtn').addEventListener('click', async () => {
            if (confirm('Cancel this order? This will clear your order summary.')) {
                await clearCart();
                renderSummary();
                checkoutWrapper.style.display = 'none';
                customizeView.style.display = 'block';
                checkoutBtnEl.style.display = 'flex';
                receiptOverlay.style.display = 'none';
            }
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
            return {
                coffeeBeans: { current: 1000, max: 1000, unit: 'g' },
                milk: { current: 5000, max: 5000, unit: 'ml' },
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

        // Tune these per-cup consumption amounts as needed
        const RECIPE_PER_CUP = {
            cups: 1,
            milkMl: 150,
            coffeeBeansG: 18,
            matchaG: 5
        };

        async function deductInventoryForOrder(cart) {
            const inv = await loadInventory();

            cart.forEach(item => {
                const qty = item.qty || 1;
                const category = (item.category || '').trim();

                if (inv.cups) {
                    inv.cups.current = Math.max(0, inv.cups.current - RECIPE_PER_CUP.cups * qty);
                }
                if (inv.milk) {
                    inv.milk.current = Math.max(0, inv.milk.current - RECIPE_PER_CUP.milkMl * qty);
                }
                if (inv.coffeeBeans && category.includes('coffee')) {
                    inv.coffeeBeans.current = Math.max(0, inv.coffeeBeans.current - RECIPE_PER_CUP.coffeeBeansG * qty);
                }
                if (inv.matcha && category.includes('matcha')) {
                    inv.matcha.current = Math.max(0, inv.matcha.current - RECIPE_PER_CUP.matchaG * qty);
                }
            });

            saveInventory(inv);
        }

        // Force-enable Complete Payment regardless of whatever CSS/disabled
        // state it starts in — it was showing up unclickable/disabled-looking
        // even though nothing in this file ever set it disabled.
        const completePaymentBtnEl = document.getElementById('completePaymentBtn');
        completePaymentBtnEl.disabled = false;
        completePaymentBtnEl.removeAttribute('disabled');
        completePaymentBtnEl.style.opacity = '1';
        completePaymentBtnEl.style.pointerEvents = 'auto';
        completePaymentBtnEl.style.cursor = 'pointer';

        completePaymentBtnEl.addEventListener('click', async () => {
            const activeMethod = document.querySelector('.payment-option.active')?.dataset?.method || 'cash';
            const cart = await getCart();
            const total = await getOrderTotal();
            let tendered = 0;

            if (cart.length === 0) {
                alert('Your order summary is empty.');
                return;
            }

            if (activeMethod === 'cash') {
                tendered = parseFloat(amountTendered.value) || 0;
                if (tendered < total) {
                    alert('Amount tendered is less than the total amount due.');
                    return;
                }
            }

            try {
                const saved = await savePosOrder(cart, total, activeMethod);
                if (!saved) {
                    return;
                }

                await deductInventoryForOrder(cart);

                // Build and show the receipt with the order that was just paid for,
                // then swap the POS interface out for the centered receipt overlay.
                const savedTotal = parseFloat(saved.total) || total;
                showReceipt(cart, savedTotal, activeMethod, tendered, saved);
                backToMenu.style.display = 'none';
                orderLayout.style.display = 'none';

                // Reset everything for the next order
                await clearCart();
                renderSummary();
                checkoutWrapper.style.display = 'none';
                customizeView.style.display = 'block';
                checkoutBtnEl.style.display = 'block';
                amountTendered.value = '';
                changeValue.textContent = '₱0.00';
            } catch (err) {
                console.error('Unable to complete payment:', err);
                alert('Unable to complete payment. ' + (err?.message || 'Please try again.'));
            }
        });

        // Payment method toggle
        const paymentOptions = document.querySelectorAll('.payment-option');
        const cashPanel = document.getElementById('cashPaymentPanel');
        const gcashPanel = document.getElementById('gcashPaymentPanel');

        paymentOptions.forEach(btn => {
            btn.addEventListener('click', () => {
                paymentOptions.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (btn.dataset.method === 'cash') {
                    cashPanel.style.display = 'block';
                    gcashPanel.style.display = 'none';
                } else {
                    cashPanel.style.display = 'none';
                    gcashPanel.style.display = 'block';
                }
            });
        });

        // Cash tendered -> change calculation
        const amountTendered = document.getElementById('amountTendered');
        const changeValue = document.getElementById('changeValue');

        async function getOrderTotal() {
            const cart = await getCart();
            return cart.reduce((sum, item) => sum + (parseFloat(item.itemTotal) || 0), 0);
        }

        function normalizePosOrderType(orderType) {
            const normalized = String(orderType || '').trim().toLowerCase();
            if (normalized === 'dine in') return 'dine-in';
            if (normalized === 'take out') return 'takeout';
            if (normalized === 'pick up') return 'pickup';
            if (normalized === 'takeout' || normalized === 'delivery' || normalized === 'pickup' || normalized === 'dine-in') {
                return normalized;
            }
            return normalized.replace(/\s+/g, '-');
        }

        async function savePosOrder(cart, total, method) {
            const payload = {
                items: cart.map(item => {
                    // unitPrice must reflect the FULL per-unit price the customer
                    // was actually charged (base + milk upgrade + add-ons), since
                    // pos-order-api.php recomputes subtotal/total server-side as
                    // unitPrice * qty. Sending only basePrice here was silently
                    // dropping milk/add-on charges from the saved order total.
                    const qty = parseInt(item.qty || 1, 10) || 1;
                    const addonsTotal = (Array.isArray(item.addons) ? item.addons : [])
                        .reduce((sum, a) => sum + (parseFloat(a.price) || 0), 0);
                    const basePrice = parseFloat(item.basePrice || 0) || 0;
                    const milkPrice = parseFloat(item.milkPrice || 0) || 0;
                    const effectiveUnitPrice = basePrice + milkPrice + addonsTotal;

                    return {
                        name: item.name,
                        image: item.img,
                        unitPrice: effectiveUnitPrice,
                        qty: qty,
                        milk: item.milk || '',
                        addons: item.addons || [],
                        orderType: normalizePosOrderType(item.orderType || 'Dine In'),
                        notes: item.notes || ''
                    };
                }),
                order_type: normalizePosOrderType(cart[0]?.orderType || 'Dine In'),
                payment_method: method === 'cash' ? 'cod' : 'gcash',
                delivery_fee: 0,
                tax: 0,
                notes: ''
            };

            try {
                const res = await fetch('../pos-order-api.php', {
                    method: 'POST',
                    // use include so cookies are sent even if frontend is on a different port (e.g. localhost:3000)
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('pos-order-api returned non-JSON response:', text);
                    alert('Unable to save POS order. Server returned invalid response:\n' + text);
                    return null;
                }
                if (!data.success) {
                    throw new Error(data.error || 'POS order save failed');
                }
                return data;
            } catch (err) {
                alert('Unable to save POS order. ' + err.message);
                return null;
            }
        }

        amountTendered.addEventListener('input', async () => {
            const tendered = parseFloat(amountTendered.value) || 0;
            const total = await getOrderTotal();
            const change = tendered - total;
            changeValue.textContent = `₱${(change > 0 ? change : 0).toFixed(2)}`;
            changeValue.classList.toggle('negative', change < 0);
        });

        // Render the summary panel on load so cart persists across page loads
        renderSummary();

        // ── Receipt building/rendering ──

        async function nextReceiptNumber() {
            try {
                const response = await fetch('../api/pos_cart_api.php?action=get_receipt_number');
                const data = await response.json();
                if (data.success) {
                    // Increment the counter after getting the number
                    await fetch('../api/pos_cart_api.php?action=increment_receipt_counter');
                    return data.receipt_number;
                }
                return 'BC-000000001';
            } catch (e) {
                console.error('Failed to get receipt number:', e);
                return 'BC-000000001';
            }
        }

        function getSavedOrderDate(savedOrder) {
            if (savedOrder && savedOrder.created_at) {
                const parsed = new Date(String(savedOrder.created_at).replace(' ', 'T'));
                if (!isNaN(parsed.getTime())) return parsed;
            }
            return new Date();
        }

        function buildReceiptNumber(savedOrder, cart) {
            if (savedOrder && savedOrder.order_no) {
                return savedOrder.order_no;
            }

            if (savedOrder && savedOrder.order_id) {
                const typeCodes = {
                    delivery: 'DEL',
                    pickup: 'PU',
                    'dine-in': 'DI',
                    takeout: 'TO'
                };
                const orderType = normalizePosOrderType(savedOrder.order_type || cart[0]?.orderType || 'Dine In');
                const date = getSavedOrderDate(savedOrder);
                const typeCode = typeCodes[orderType] || 'GEN';
                return `POS-${typeCode}-${date.getFullYear()}-${String(savedOrder.order_id).padStart(5, '0')}`;
            }

            return nextReceiptNumber();
        }

        function getShiftLabel(date) {
            const hour = date.getHours();
            if (hour < 12) return 'Morning Shift';
            if (hour < 18) return 'Afternoon Shift';
            return 'Evening Shift';
        }

        function formatReceiptDate(date) {
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: '2-digit' });
        }

        function formatReceiptTime(date) {
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }).toLowerCase();
        }

        function buildReceiptItemsHTML(cart) {
            return cart.map(item => {
                const qty = Number(item.qty || 1) || 1;
                const basePrice = Number(item.basePrice || item.unitPrice || 0) || 0;
                const itemTotal = Number(item.itemTotal || (basePrice * qty) || 0) || 0;
                const addons = Array.isArray(item.addons) ? item.addons : [];

                const rows = [`
                    <div class="receipt-item-row">
                        <span>${String(item.name || 'Item').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                        <span>${qty}</span>
                        <span>₱${basePrice.toFixed(2)}</span>
                        <span>₱${itemTotal.toFixed(2)}</span>
                    </div>
                `];

                if (item.milk && item.milk !== 'Original') {
                    rows.push(`
                        <div class="receipt-addon-row">
                            <span class="receipt-addon-name">${String(item.milk).replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                            <span>${qty}</span>
                            <span>₱${(Number(item.milkPrice || 0) || 0).toFixed(2)}</span>
                            <span></span>
                        </div>
                    `);
                }

                addons.forEach(addon => {
                    const addonValue = addon?.value || addon?.name || addon || '';
                    const addonPrice = Number(addon?.price || 0) || 0;
                    rows.push(`
                        <div class="receipt-addon-row">
                            <span class="receipt-addon-name">${String(addonValue).replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                            <span>${qty}</span>
                            <span>₱${addonPrice.toFixed(2)}</span>
                            <span></span>
                        </div>
                    `);
                });

                return rows.join('');
            }).join('');
        }

        function showReceipt(cart, total, method, tendered, savedOrder = null) {
            const now = getSavedOrderDate(savedOrder);

            document.getElementById('receiptNo').textContent = buildReceiptNumber(savedOrder, cart);
            document.getElementById('receiptDate').textContent = formatReceiptDate(now);
            document.getElementById('receiptTime').textContent = formatReceiptTime(now);
            document.getElementById('receiptShift').textContent = getShiftLabel(now);

            // Set branch and cashier info
            document.getElementById('receiptBranch').textContent = '<?= htmlspecialchars($branchName) ?>';
            document.getElementById('receiptCashier').textContent = '<?= htmlspecialchars($employeeName) ?>';

            document.getElementById('receiptItemsList').innerHTML = buildReceiptItemsHTML(cart);

            document.getElementById('receiptSubtotal').textContent = `₱${total.toFixed(2)}`;
            document.getElementById('receiptDiscount').textContent = `₱0.00`;
            document.getElementById('receiptGrandTotal').textContent = `₱${total.toFixed(2)}`;

            document.getElementById('receiptMethod').textContent = method === 'cash' ? 'Cash' : 'GCash';

            const tenderedRow = document.getElementById('receiptTenderedRow');
            const changeRow = document.getElementById('receiptChangeRow');
            if (method === 'cash') {
                tenderedRow.style.display = 'grid';
                changeRow.style.display = 'grid';
                document.getElementById('receiptTendered').textContent = `₱${tendered.toFixed(2)}`;
                document.getElementById('receiptChange').textContent = `₱${(tendered - total).toFixed(2)}`;
            } else {
                tenderedRow.style.display = 'none';
                changeRow.style.display = 'none';
            }

            receiptOverlay.style.display = 'flex';
        }
        
        // Print Receipt — was previously wired to nothing, so clicking it did nothing.
        document.getElementById('printReceiptBtn').addEventListener('click', () => {
            window.print();
        });

        // Back to Menu — was previously wired to window.print() instead of
        // navigating anywhere, so the cashier got stuck on the receipt screen
        // after completing payment. Clear the cart/current product (the order
        // is already saved) and return to the POS menu.
        document.getElementById('newOrderBtn').addEventListener('click', async () => {
            await clearCart();
            localStorage.removeItem('boycold_current_product');
            window.location.href = 'pos-menu.php';
        });
        

    </script>
    <script src="order-notify.js"></script>
</body>
</html>