<?php
session_start();
require_once '../config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$stmt = $connect->prepare("SELECT Firstname, Lastname, user_name, email, avatar FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$fullName  = htmlspecialchars($user['Firstname'] . ' ' . $user['Lastname']);
$userEmail = htmlspecialchars($user['email']);
$avatar    = $user['avatar'] ? htmlspecialchars($user['avatar']) : '';
$userName  = trim((string) ($user['user_name'] ?? ''));
$_SESSION['user_name']  = $userName;
$_SESSION['user_email'] = $user['email'];

// Handle cancellation before rendering the page so fetch() always receives JSON.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    header('Content-Type: application/json');

    $orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid order.']);
        exit;
    }

    $stmt = $connect->prepare(
        "SELECT o.user_name, o.status
         FROM orders o
         INNER JOIN users u ON u.user_name = o.user_name
         WHERE o.id = ? AND u.id = ?"
    );
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order || strcasecmp(trim((string) ($order['user_name'] ?? '')), $userName) !== 0) {
        echo json_encode(['success' => false, 'error' => 'Order not found.']);
        exit;
    }

    if (in_array($order['status'], ['ready', 'delivered', 'completed', 'cancelled'], true)) {
        echo json_encode(['success' => false, 'error' => 'This order can no longer be cancelled.']);
        exit;
    }

    $stmt = $connect->prepare(
        "UPDATE orders o
         INNER JOIN users u ON u.user_name = o.user_name
         SET o.status = 'cancelled'
         WHERE o.id = ? AND u.id = ?
           AND o.status NOT IN ('ready', 'delivered', 'completed', 'cancelled')"
    );
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $updated = $stmt->affected_rows > 0;
    $stmt->close();

    echo json_encode([
        'success' => $updated,
        'error' => $updated ? null : 'Unable to cancel this order right now.'
    ]);
    exit;
}

// ── Get this user's most recent order ──────────────────────────────
$stmt = $connect->prepare(
    "SELECT o.*
     FROM orders o
     INNER JOIN users u ON u.user_name = o.user_name
     WHERE u.id = ?
     ORDER BY o.created_at DESC LIMIT 1"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$latestOrder = $stmt->get_result()->fetch_assoc();
$stmt->close();

$orderItems = [];
if ($latestOrder) {
    $stmt = $connect->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $latestOrder['id']);
    $stmt->execute();
    $orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$isCancelled  = $latestOrder && $latestOrder['status'] === 'cancelled';
$hasOrder     = $latestOrder && !$isCancelled;
$hasActiveOrder = $hasOrder && !in_array($latestOrder['status'], ['delivered', 'completed'], true);
$canCancel = $hasOrder && !$isCancelled && !in_array($latestOrder['status'], ['delivered', 'ready', 'completed'], true);

// ── Payment / step logic ──────────────────────────────
$paymentMethodLabel = '';
$paymentStatusLabel = '';
$paymentStepLabel = 'Payment Confirmed';
if ($hasOrder) {
    $paymentMethodKey = strtolower($latestOrder['payment_method'] ?? '');
    $paymentStatusKey = strtolower($latestOrder['payment_status'] ?? '');
    $isCodPayment = ($paymentMethodKey === 'cod');
    $paymentMethodLabel = [
        'gcash' => 'GCash',
        'cod'   => 'Cash on Delivery',
    ][$paymentMethodKey] ?? ucfirst($paymentMethodKey);
    if ($isCodPayment && $paymentStatusKey !== 'paid' && $paymentStatusKey !== 'cancelled') {
        $paymentStatusLabel = 'Pay after delivery';
        $paymentStepLabel = 'Payment on Delivery';
    } else {
        $paymentStatusLabel = ucfirst($paymentStatusKey);
        $paymentStepLabel = $isCodPayment ? 'Payment Collected' : 'Payment Confirmed';
    }
}

// Define different status flows based on payment method
$stepReached = [false, false, false, false, false];
$stepLabels = [];
$stepIcons = [];
if ($hasOrder) {
    $status        = $latestOrder['status'];
    $paymentStatus = $latestOrder['payment_status'];
    $paymentMethodKey = strtolower($latestOrder['payment_method'] ?? '');
    
    if ($paymentMethodKey === 'gcash') {
        // GCash flow: Order Confirm → Payment Pending → Preparing → Out for Delivery → Delivered
        $stepReached[0] = true;
        $stepReached[1] = in_array($status, ['confirmed', 'preparing', 'ready', 'delivered', 'completed']);
        $stepReached[2] = ($paymentStatus === 'paid');
        $stepReached[3] = in_array($status, ['preparing', 'ready', 'delivered', 'completed']);
        $stepReached[4] = in_array($status, ['ready', 'delivered', 'completed']);
        $stepLabels = [
            $status === 'pending' ? 'Order Pending' : 'Order Confirmed',
            $paymentStepLabel,
            'Preparing',
            'Out for Delivery',
            'Delivered'
        ];
        $stepIcons = ['fa-clock', 'fa-clipboard-check', 'fa-credit-card', 'fa-truck', 'fa-house'];
    } else {
        // COD flow: Order Confirm → Preparing → Out for Delivery → Payment Confirm → Delivered
        $stepReached[0] = true;
        $stepReached[1] = in_array($status, ['preparing', 'ready', 'delivered', 'completed']);
        $stepReached[2] = in_array($status, ['ready', 'delivered', 'completed']);
        $stepReached[3] = ($paymentStatus === 'paid');
        $stepReached[4] = in_array($status, ['delivered', 'completed']);
        $stepLabels = [
            $status === 'pending' ? 'Order Pending' : 'Order Confirmed',
            'Preparing',
            'Out for Delivery',
            'Payment Confirm',
            'Delivered'
        ];
        $stepIcons = ['fa-clock', 'fa-mug-hot', 'fa-truck', 'fa-credit-card', 'fa-house'];
    }
} else {
    $stepLabels = ['Order Pending', 'Payment Pending', 'Preparing', 'Out for Delivery', 'Delivered'];
    $stepIcons = ['fa-clock', 'fa-clipboard-check', 'fa-credit-card', 'fa-truck', 'fa-house'];
}

$reachedCount = array_sum($stepReached);
$fillPercent  = $reachedCount > 0 ? round((($reachedCount - 1) / (count($stepReached) - 1)) * 100) : 0;

function step_class(bool $reached)
{
    return $reached ? 'step-circle active' : 'step-circle';
}
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
                <li><a href="cart.php" class="cart-link">
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
            <?php if (!$hasOrder): ?>
                <div class="no-order-banner">
                    <div class="no-order-banner-icon">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <div class="no-order-banner-text">
                        <?php if ($isCancelled): ?>
                            <strong>Order Cancelled</strong>
                            <span>Your last order (#<?= (int) $latestOrder['id'] ?>) was cancelled. Feel free to place a new one!</span>
                        <?php else: ?>
                            <strong>No Active Order</strong>
                            <span>You haven't placed an order yet. Browse our menu and start your order!</span>
                        <?php endif; ?>
                    </div>
                    <a href="../user/Menu.php" class="no-order-banner-btn">Order Now</a>
                </div>
            <?php else: ?>
                <?php if ($hasActiveOrder): ?>
                    <div class="no-order-banner">
                        <div class="no-order-banner-icon">
                            <i class="fa-solid fa-hourglass-half"></i>
                        </div>
                        <div class="no-order-banner-text">
                            <strong>Order In Progress</strong>
                            <span>You can place a new order once this one has been accepted and delivered.</span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="progress-tracker">
                <div class="progress-line-fill" style="--fill-percent: <?= $fillPercent ?>;"></div>
                <?php foreach ($stepLabels as $index => $label): ?>
                <div class="progress-step">
                    <div class="<?= step_class($stepReached[$index]) ?>">
                        <?php if (isset($stepIcons[$index]) && strpos($stepIcons[$index], 'fa-') === 0): ?>
                            <i class="fa-solid <?= htmlspecialchars($stepIcons[$index]) ?>"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-clock"></i>
                        <?php endif; ?>
                    </div>
                    <div class="step-label"><?= htmlspecialchars($label) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="order-summary">
                <div class="section-label">Order Summary</div>
                <?php if (!$hasOrder): ?>
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
                <?php else: ?>
                    <?php foreach ($orderItems as $item): ?>
                        <div class="order-item-row">
                            <?php if (!empty($item['product_image'])): ?>
                                <img src="../<?= htmlspecialchars(ltrim($item['product_image'], '/')) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="order-item-img">
                            <?php endif; ?>
                            <div class="order-item-info">
                                <span class="order-item-name"><?= (int) $item['quantity'] ?>x <?= htmlspecialchars($item['product_name']) ?></span>
                                <?php if (!empty($item['milk']) || !empty($item['addons'])): ?>
                                    <span class="order-item-opts">
                                        <?= htmlspecialchars(trim(($item['milk'] ?? '') . (!empty($item['milk']) && !empty($item['addons']) ? ', ' : '') . ($item['addons'] ?? ''))) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="order-item-price">₱<?= number_format($item['line_total'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="order-divider"></div>
                    <div class="order-total">
                        <span>Total:</span>
                        <strong>₱<?= number_format($latestOrder['total'], 2) ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Delivery Details -->
            <div class="delivery-details">
                <div class="section-label">Order Details</div>
                <?php if (!$hasOrder): ?>
                    <div class="empty-delivery">
                        <div class="empty-delivery-icon-wrap">
                            <i class="fa-solid fa-map-location-dot"></i>
                        </div>
                        <span class="empty-delivery-title">No delivery details yet.</span>
                        <span class="empty-delivery-sub">Place an order to see delivery information here.</span>
                    </div>
                <?php else: ?>
                    <div class="delivery-detail-row">
                        <span class="delivery-detail-label">Order Type</span>
                        <span class="delivery-detail-value"><?= htmlspecialchars(ucfirst($latestOrder['order_type'])) ?></span>
                    </div>
                    <?php if (!empty($latestOrder['address'])): ?>
                        <div class="delivery-detail-row">
                            <span class="delivery-detail-label">Address</span>
                            <span class="delivery-detail-value"><?= htmlspecialchars($latestOrder['address']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="delivery-detail-row">
                        <span class="delivery-detail-label">Payment Method</span>
                        <span class="delivery-detail-value"><?= htmlspecialchars($paymentMethodLabel) ?></span>
                    </div>
                    <div class="delivery-detail-row">
                        <span class="delivery-detail-label">Payment Status</span>
                        <span class="delivery-detail-value"><?= htmlspecialchars($paymentStatusLabel) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Need Help -->
            <div class="need-help">
                <div class="section-label">Need Help?</div>

                <button class="help-btn" onclick="openRiderModal()">
                    <div class="help-btn-icon">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.4508 7.22542C14.4508 6.27657 14.264 5.337 13.9008 4.46037C13.5377 3.58374 13.0055 2.78722 12.3346 2.11628C11.6636 1.44533 10.8671 0.913114 9.99047 0.550003C9.11384 0.186891 8.17428 0 7.22542 0V1.44508C8.36862 1.44506 9.48615 1.78401 10.4367 2.41909C11.3873 3.05417 12.1282 3.95686 12.5657 5.013C12.8563 5.71442 13.0058 6.46621 13.0058 7.22542H14.4508ZM0 5.78034V2.16763C0 1.976 0.0761247 1.79222 0.211628 1.65671C0.347131 1.52121 0.530912 1.44508 0.722542 1.44508H4.33525C4.52688 1.44508 4.71067 1.52121 4.84617 1.65671C4.98167 1.79222 5.0578 1.976 5.0578 2.16763V5.0578C5.0578 5.24943 4.98167 5.43321 4.84617 5.56871C4.71067 5.70421 4.52688 5.78034 4.33525 5.78034H2.89017C2.89017 7.31338 3.49917 8.78363 4.58319 9.86765C5.66721 10.9517 7.13747 11.5607 8.67051 11.5607V10.1156C8.67051 9.92396 8.74663 9.74018 8.88214 9.60468C9.01764 9.46917 9.20142 9.39305 9.39305 9.39305H12.2832C12.4748 9.39305 12.6586 9.46917 12.7941 9.60468C12.9296 9.77018 13.0058 9.92396 13.0058 10.1156V13.7283C13.0058 13.9199 12.9296 14.1037 12.7941 14.2392C12.6586 14.3747 12.4748 14.4508 12.2832 14.4508H8.67051C3.88222 14.4508 0 10.5686 0 5.78034Z" fill="white" />
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

                <?php if ($canCancel): ?>
                    <button class="help-btn" type="button" onclick="openCancelModal()">
                        <div class="help-btn-icon"><i class="fa-solid fa-ban"></i></div>
                        <div class="help-btn-text">
                            <span class="help-btn-title">Cancel Order</span>
                            <span class="help-btn-sub">You can cancel your order only before it's out for delivery</span>
                        </div>
                        <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0.0705161 0.542819C-0.0940739 1.02779 0.0392648 1.61712 0.368445 1.85961L5.84366 5.89286L0.368445 9.9261C0.0392648 10.1686 -0.0940739 10.7579 0.0705161 11.2429C0.235106 11.7279 0.635122 11.9243 0.964302 11.6818L7.63124 6.77072C7.85624 6.60497 8 6.26426 8 5.89286C8 5.52145 7.85833 5.18075 7.63124 5.015L0.964302 0.103889C0.635122 -0.138596 0.235106 0.0578477 0.0705161 0.542819Z" fill="#483121" />
                        </svg>
                    </button>
                <?php endif; ?>
            </div>

        </div><!-- end .order-card -->
    </main>

    <!-- Rider Modal -->
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

    <!-- Report Modal -->
    <div class="modal-overlay" id="reportModal" onclick="closeReportModal(event)">
        <div class="modal">
            <button class="modal-close" onclick="closeReportModalDirect()">&times;</button>
            <div class="modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="modal-title">Report a Problem</div>
            <div class="modal-subtitle">Let us know what went wrong</div>
            <div class="report-form">
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
                            <div class="dropdown-option" onclick="selectIssue('Cannot Contact the Rider')">Cannot Contact the Rider</div>
                            <div class="dropdown-option" onclick="selectIssue('Wrong Order')">Wrong Order</div>
                            <div class="dropdown-option" onclick="selectIssue('Other')">Other</div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="form-label">Tell Us More</label>
                    <div class="report-textarea-wrap">
                        <textarea class="report-textarea" id="reportTextarea" placeholder="Please provide more details on your concern" maxlength="500" oninput="updateCharCount(this)"></textarea>
                        <div class="preview-list" id="previewList"></div>
                        <div class="textarea-footer">
                            <div class="attach-btn" onclick="toggleAttachMenu(event)">
                                <i class="fa-solid fa-paperclip"></i>
                                <div class="attach-popup" id="attachPopup">
                                    <div class="attach-option" onclick="triggerCamera()">
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3.79833 0.625C3.53167 0.625 3.30792 0.809583 3.2575 1.05667C3.17625 1.45667 2.82125 1.77167 2.39417 1.77542C1.8325 1.80042 1.53167 1.86917 1.30667 2.01667C1.13977 2.12664 0.995849 2.26801 0.882917 2.43292C0.767917 2.60167 0.69875 2.81792 0.662083 3.16917C0.625417 3.52583 0.625 3.98583 0.625 4.63083C0.625 5.27583 0.625 5.73542 0.6625 6.09208C0.69875 6.44333 0.767917 6.65958 0.883333 6.82875C0.995 6.99292 1.13875 7.13458 1.30708 7.245C1.48083 7.35875 1.70333 7.4275 2.06292 7.46333C2.42708 7.49958 2.89625 7.5 3.55333 7.5H5.405C6.06167 7.5 6.53083 7.5 6.89542 7.46333C7.255 7.4275 7.4775 7.35917 7.65125 7.245C7.81958 7.13458 7.96375 6.99292 8.07542 6.82833C8.19042 6.65958 8.25958 6.44333 8.29625 6.09208C8.33292 5.73542 8.33333 5.27542 8.33333 4.63083C8.33333 3.98625 8.33333 3.52583 8.29583 3.16917C8.25958 2.81792 8.19042 2.60167 8.075 2.43292C7.96211 2.26786 7.81819 2.12634 7.65125 2.01625C7.42708 1.86917 7.12625 1.80042 6.56375 1.77542C6.13708 1.77125 5.78208 1.45708 5.70083 1.05667C5.67417 0.933446 5.60579 0.823205 5.50726 0.744559C5.40872 0.665913 5.28607 0.623683 5.16 0.625H3.79833ZM4.47917 3.54167C4.23053 3.54167 3.99207 3.64044 3.81625 3.81625C3.64044 3.99207 3.54167 4.23053 3.54167 4.47917C3.54167 4.72781 3.64044 4.96626 3.81625 5.14208C3.99207 5.31789 4.23053 5.41667 4.47917 5.41667C4.72781 5.41667 4.96626 5.31789 5.14208 5.14208C5.31789 4.96626 5.41667 4.72781 5.41667 4.47917C5.41667 4.23053 5.31789 3.99207 5.14208 3.81625C4.96626 3.64044 4.72781 3.54167 4.47917 3.54167ZM2.91667 4.47917C2.91667 4.06477 3.08129 3.66734 3.37431 3.37431C3.66734 3.08129 4.06477 2.91667 4.47917 2.91667C4.89357 2.91667 5.291 3.08129 5.58402 3.37431C5.87705 3.66734 6.04167 4.06477 6.04167 4.47917C6.04167 4.89357 5.87705 5.291 5.58402 5.58402C5.291 5.87705 4.89357 6.04167 4.47917 6.04167C4.06477 6.04167 3.66734 5.87705 3.37431 5.58402C3.08129 5.291 2.91667 4.89357 2.91667 4.47917ZM6.66667 3.22917C6.66667 3.14629 6.69959 3.0668 6.7582 3.0082C6.8168 2.94959 6.89629 2.91667 6.97917 2.91667H7.39583C7.47871 2.91667 7.5582 2.94959 7.6168 3.0082C7.67541 3.0668 7.70833 3.14629 7.70833 3.22917C7.70833 3.31205 7.67541 3.39153 7.6168 3.45014C7.5582 3.50874 7.47871 3.54167 7.39583 3.54167H6.97917C6.89629 3.54167 6.8168 3.50874 6.7582 3.45014C6.69959 3.39153 6.66667 3.31205 6.66667 3.22917Z" fill="black" />
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
                    <div class="modal-actions">
                        <button class="btn-cancel" onclick="closeReportModalDirect()">Cancel</button>
                        <button class="btn-submit" onclick="submitReport()">Submit Report</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="file" id="reportFileInput" accept="image/*" multiple style="display:none;" onchange="handleReportFiles(this.files)">

    <!-- Camera Capture Modal -->
    <div class="camera-modal-overlay" id="cameraModal">
        <div class="camera-video-wrap">
            <video id="cameraVideo" autoplay playsinline muted></video>
            <div class="camera-controls">
                <button class="camera-cancel-btn" type="button" onclick="closeCameraModal()">Cancel</button>
                <button class="camera-capture-btn" type="button" onclick="capturePhoto()" aria-label="Capture photo"></button>
            </div>
        </div>
        <div class="camera-error" id="cameraError" style="display:none;"></div>
    </div>
    <canvas id="cameraCanvas" style="display:none;"></canvas>

    <!-- Cancel Order Modal -->
    <div class="modal-overlay" id="cancelModal" onclick="closeCancelModal(event)">
        <div class="modal">
            <button class="modal-close" onclick="closeCancelModalDirect()">&times;</button>
            <div class="modal-icon">
                <img src="../picture/ChatGPT Image May 16, 2026, 09_14_38 PM 1.png" alt="Cancel Order">
            </div>
            <br><br>
            <div class="modal-title">Cancel Order</div>
            <div class="modal-subtitle">Are you sure you want to cancel your order?</div>
            <p style="text-align:center; color:#888; margin-bottom:20px;">Your order process will be stopped.</p>
            <div class="modal-actions">
                <button class="btn-cancel" type="button" onclick="closeCancelModalDirect()">No</button>
                <button class="btn-submit" type="button" id="confirmCancelBtn" onclick="confirmCancel()" style="background:#c0392b;">Yes</button>
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

    <script>
        <?php if ($hasOrder && ($latestOrder['status'] ?? '') === 'pending'): ?>
        setTimeout(() => {
            window.location.reload();
        }, 5000);
        <?php endif; ?>
        // ... (existing JavaScript unchanged) ...

        // ── Cancel Order functions ──────────────────────────────
        function openCancelModal() {
            document.getElementById('cancelModal').classList.add('open');
        }

        function closeCancelModal(e) {
            if (e.target === document.getElementById('cancelModal')) closeCancelModalDirect();
        }

        function closeCancelModalDirect() {
            document.getElementById('cancelModal').classList.remove('open');
        }

        async function confirmCancel() {
            const orderId = Number(<?= json_encode($latestOrder['id'] ?? 0) ?>);
            const confirmBtn = document.getElementById('confirmCancelBtn');
            if (!orderId || confirmBtn?.disabled) return;

            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Cancelling...';
            }

            try {
                const body = new URLSearchParams({
                    cancel_order: '1',
                    order_id: String(orderId)
                });
                const response = await fetch('status.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body
                });

                const raw = await response.text();
                let data;
                try {
                    data = JSON.parse(raw);
                } catch (err) {
                    throw new Error('Invalid server response while cancelling the order.');
                }

                if (!response.ok || !data.success) {
                    alert(data.error || 'Could not cancel order.');
                    return;
                }

                alert('Order cancelled successfully.');
                location.reload();
            } catch (err) {
                alert(err.message || 'Network error. Please try again.');
            } finally {
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Yes';
                }
            }
        }

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
            document.getElementById('reportModal').classList.remove('open');
            document.getElementById('selectDisplay').textContent = 'Select an issue';
            document.getElementById('selectDisplay').style.color = '#aaa';
            document.getElementById('selectWrapper').querySelectorAll('.dropdown-option').forEach(o => o.classList.remove('selected'));
            document.getElementById('reportTextarea').value = '';
            document.getElementById('charCount').textContent = '0/500';
            document.getElementById('selectWrapper').classList.remove('open');
            document.getElementById('attachPopup').classList.remove('open');
            document.getElementById('reportFileInput').value = '';
            reportAttachments = [];
            renderReportPreviews();
            closeCameraModal();
        }

        /* ── Attach menu (report modal) ── */
        let reportAttachments = [];

        function toggleAttachMenu(e) {
            e.stopPropagation();
            document.getElementById('attachPopup').classList.toggle('open');
        }

        document.addEventListener('click', function(e) {
            const attachBtn = document.querySelector('.attach-btn');
            const popup = document.getElementById('attachPopup');
            if (popup && attachBtn && !attachBtn.contains(e.target)) {
                popup.classList.remove('open');
            }
        });

        function addReportAttachment(dataUrl) {
            reportAttachments.push({ id: Date.now() + '-' + Math.random().toString(36).slice(2), dataUrl });
            renderReportPreviews();
        }

        function removeReportAttachment(id) {
            reportAttachments = reportAttachments.filter(a => a.id !== id);
            renderReportPreviews();
        }

        function renderReportPreviews() {
            const list = document.getElementById('previewList');
            list.innerHTML = reportAttachments.map(a =>
                '<div class="preview-thumb">' +
                    '<img src="' + a.dataUrl + '" alt="Attached photo">' +
                    '<button type="button" class="preview-remove" onclick="removeReportAttachment(\'' + a.id + '\')">&times;</button>' +
                '</div>'
            ).join('');
        }

        /* ── Upload a photo ── */
        function triggerUpload() {
            document.getElementById('attachPopup').classList.remove('open');
            document.getElementById('reportFileInput').click();
        }

        function handleReportFiles(fileList) {
            Array.from(fileList).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = function(e) {
                    addReportAttachment(e.target.result);
                };
                reader.readAsDataURL(file);
            });
            document.getElementById('reportFileInput').value = '';
        }

        /* ── Take a photo (camera capture) ── */
        let cameraStream = null;

        function triggerCamera() {
            document.getElementById('attachPopup').classList.remove('open');
            const modal = document.getElementById('cameraModal');
            const video = document.getElementById('cameraVideo');
            const errorEl = document.getElementById('cameraError');
            errorEl.style.display = 'none';
            errorEl.textContent = '';
            modal.classList.add('open');

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                errorEl.textContent = 'Camera is not supported on this device. Please upload a photo instead.';
                errorEl.style.display = 'block';
                return;
            }

            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(stream => {
                    cameraStream = stream;
                    video.srcObject = stream;
                })
                .catch(() => {
                    errorEl.textContent = 'Unable to access your camera. Please check permissions or upload a photo instead.';
                    errorEl.style.display = 'block';
                });
        }

        function closeCameraModal() {
            const modal = document.getElementById('cameraModal');
            if (modal) modal.classList.remove('open');
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
        }

        function capturePhoto() {
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            if (!video.videoWidth) return;
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            addReportAttachment(canvas.toDataURL('image/jpeg', 0.9));
            closeCameraModal();
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
    </script>
</body>

</html>