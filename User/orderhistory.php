<?php
session_start();
require_once '../config/db_config.php';

// Session guard
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: ../login.php');
    exit;
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Fetch latest user data
$stmt = $connect->prepare(
    "SELECT firstname, lastname, email, phone, address, avatar, card_no FROM users WHERE id = ?"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$email    = htmlspecialchars($user['email']);
$avatar   = $user['avatar']  ? htmlspecialchars($user['avatar'])  : '';
$phone    = $user['phone']   ? htmlspecialchars($user['phone'])   : '';
$address  = $user['address'] ? htmlspecialchars($user['address']) : '';
$cardNo   = $user['card_no'] ? htmlspecialchars($user['card_no']) : '—';

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
                <li><a href="status.php">ORDER</a></li>
                <li><a href="../store/store.php">STORES</a></li>
                <li class="sidebar-nav-only-not"><a href="status.php">ORDERS</a></li>
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
                        <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar" onerror="this.style.display='none'; const icon=this.parentElement.querySelector('.fa-user'); if(icon) icon.style.display='';">
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
                <button class="tab-btn" data-tab="pending">Pending</button>
                <button class="tab-btn" data-tab="cancelled">Cancelled</button>
            </div>

            <!-- ORDER LIST -->
            <div class="history-list" id="historyList">
                <div class="no-orders-state" id="historyLoading">
                    <div class="no-orders-icon-wrap">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </div>
                    <p class="no-orders-title">Loading your orders…</p>
                </div>
            </div>

        </div>
    </main>

    <!-- ORDER DETAIL MODAL -->
    <div class="order-modal-overlay" id="orderModalOverlay" onclick="closeOrderModal(event)">
        <div class="order-modal" id="orderModal">
            <button class="order-modal-close" onclick="closeOrderModalDirect()">&times;</button>
            <div class="order-modal-title" id="modalOrderTitle">Order #1234</div>
            <div class="order-modal-meta">
                <span class="order-id" id="modalOrderId">#1234</span>
                <span class="order-date" id="modalOrderDate">May 4, 2026 • 01:30 PM</span>
                <span class="order-modal-status" id="modalOrderStatus">Pending</span>
            </div>
            <div class="order-modal-items" id="modalOrderItems">
                <!-- items rendered by JS -->
            </div>
            <div class="order-modal-totals" id="modalOrderTotals">
                <!-- totals rendered by JS -->
            </div>
            <div class="order-modal-footer" id="modalOrderFooter">
                <!-- payment method, address, etc. rendered by JS -->
            </div>
        </div>
    </div>

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

        // ── Order history: fetch + render ──────────────────────────
        let allOrders = [];
        let currentTab = 'all';

        const STATUS_LABELS = {
            pending: 'Pending',
            confirmed: 'Confirmed',
            preparing: 'Preparing',
            ready: 'Ready',
            delivered: 'Delivered',
            completed: 'Completed',
            cancelled: 'Cancelled'
        };

        function escapeHtml(str) {
            const d = document.createElement('div');
            d.textContent = str ?? '';
            return d.innerHTML;
        }

        function formatDate(dateStr) {
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d)) return dateStr;
            return d.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                }) +
                ' · ' + d.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit'
                });
        }

        function renderOrders() {
            const list = document.getElementById('historyList');
            let orders = allOrders;

            if (orders.length === 0) {
                list.innerHTML = `
                <div class="no-orders-state">
                    <div class="no-orders-icon-wrap"><i class="fa-solid fa-bag-shopping"></i></div>
                    <p class="no-orders-title">No orders yet</p>
                    <p class="no-orders-desc">When you place an order, it will appear here.</p>
                    <a href="menu.php" class="empty-cart-cta">Browse Menu</a>
                </div>`;
                return;
            }

            list.innerHTML = orders.map(o => `
            <div class="order-card" data-order-id="${o.id}">
                <div class="order-card-top">
                    <div>
                        <p class="order-id">Order #${o.id}</p>
                        <p class="order-date">${formatDate(o.created_at)}</p>
                    </div>
                    <span class="order-status-badge status-${escapeHtml(o.status)}">${STATUS_LABELS[o.status] || o.status}</span>
                </div>
                <div class="order-card-mid">
                    <span>${escapeHtml(o.order_type || '')}</span>
                    <span>•</span>
                    <span>${o.payment_method === 'cod' ? 'Cash on Delivery' : 'GCash'} (${o.payment_status})</span>
                </div>
                <div class="order-card-bottom">
                    <span class="order-total">₱${parseFloat(o.total).toFixed(2)}</span>
                    <div class="order-card-actions">
                        <button class="order-detail-btn" onclick="viewOrderDetail(${o.id})">View Details</button>
                        ${o.status === 'pending' ? `<button class="order-cancel-btn" onclick="cancelOrder(${o.id})">Cancel</button>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
        }

        async function fetchOrders() {
            const list = document.getElementById('historyList');
            try {
                const res = await fetch('../api/orders_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'list',
                        status: currentTab === 'all' ? '' : currentTab
                    })
                });
                const data = await res.json();
                if (!data.success) {
                    list.innerHTML = `<div class="no-orders-state"><p class="no-orders-title">Couldn't load your orders</p><p class="no-orders-desc">${escapeHtml(data.error || 'Please try again later.')}</p></div>`;
                    return;
                }
                allOrders = data.orders || [];
                renderOrders();
            } catch (err) {
                list.innerHTML = `<div class="no-orders-state"><p class="no-orders-title">Network error</p><p class="no-orders-desc">Please check your connection and try again.</p></div>`;
            }
        }

        // ── View Order Detail (Modal) ──────────────────────────────
        async function viewOrderDetail(orderId) {
            try {
                const res = await fetch('../api/orders_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'detail',
                        order_id: orderId
                    })
                });
                const data = await res.json();
                if (!data.success) {
                    alert(data.error || 'Could not load order details.');
                    return;
                }
                const order = data.order;
                const items = order.items || [];

                // Populate modal
                document.getElementById('modalOrderId').textContent = '#' + order.id;
                document.getElementById('modalOrderTitle').textContent = 'Order #' + order.id;
                document.getElementById('modalOrderDate').textContent = formatDate(order.created_at);

                const statusBadge = document.getElementById('modalOrderStatus');
                statusBadge.textContent = STATUS_LABELS[order.status] || order.status;
                statusBadge.className = 'order-modal-status status-' + escapeHtml(order.status);

                // Items
                const itemsContainer = document.getElementById('modalOrderItems');
                if (items.length === 0) {
                    itemsContainer.innerHTML = '<p style="text-align:center;color:#aaa;">No items in this order.</p>';
                } else {
                    itemsContainer.innerHTML = items.map(item => `
                    <div class="order-modal-item">
                        ${item.product_image ? `<img class="order-modal-item-img" src="${escapeHtml(item.product_image)}" alt="${escapeHtml(item.product_name)}" onerror="this.style.display='none'">` : ''}
                        <div class="order-modal-item-info">
                            <div class="order-modal-item-name">${escapeHtml(item.product_name)}</div>
                            ${item.milk || item.addons ? `<div class="order-modal-item-meta">${escapeHtml(item.milk || '')} ${item.milk && item.addons ? '•' : ''} ${escapeHtml(item.addons || '')}</div>` : ''}
                            <span class="order-modal-item-qty">Qty: ${item.quantity}</span>
                        </div>
                        <span class="order-modal-item-price">₱${parseFloat(item.line_total).toFixed(2)}</span>
                    </div>
                `).join('');
                }

                // Totals
                const totalsContainer = document.getElementById('modalOrderTotals');
                const subtotal = parseFloat(order.subtotal || 0);
                const delivery = parseFloat(order.delivery_fee || 0);
                const tax = parseFloat(order.tax || 0);
                const total = parseFloat(order.total || 0);

                totalsContainer.innerHTML = `
                <div class="order-modal-total-row"><span>Subtotal</span><span>₱${subtotal.toFixed(2)}</span></div>
                <div class="order-modal-total-row"><span>Delivery Fee</span><span>₱${delivery.toFixed(2)}</span></div>
                <div class="order-modal-total-row"><span>Tax</span><span>₱${tax.toFixed(2)}</span></div>
                <div class="order-modal-total-row total"><span>TOTAL</span><span>₱${total.toFixed(2)}</span></div>
            `;

                // Footer: payment method and address
                const footer = document.getElementById('modalOrderFooter');
                const paymentLabel = order.payment_method === 'cod' ? 'Cash on Delivery' : 'GCash';
                footer.innerHTML = `
                <div><span class="label">Payment Method</span> <span class="value">${paymentLabel}</span></div>
                <div><span class="label">Payment Status</span> <span class="value">${escapeHtml(order.payment_status || '')}</span></div>
                ${order.address ? `<div><span class="label">Delivery Address</span> <span class="value address">${escapeHtml(order.address)}</span></div>` : ''}
            `;

                // Show modal
                document.getElementById('orderModalOverlay').classList.add('open');
            } catch (err) {
                alert('Network error loading order details.');
            }
        }

        function closeOrderModal(e) {
            if (e.target === document.getElementById('orderModalOverlay')) {
                closeOrderModalDirect();
            }
        }

        function closeOrderModalDirect() {
            document.getElementById('orderModalOverlay').classList.remove('open');
        }

        // ── Cancel Order ────────────────────────────────────────────
        async function cancelOrder(orderId) {
            if (!confirm('Cancel this order?')) return;
            try {
                const res = await fetch('../api/orders_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'cancel',
                        order_id: orderId
                    })
                });
                const data = await res.json();
                if (!data.success) {
                    alert(data.error || 'Could not cancel order.');
                    return;
                }
                fetchOrders(); // refresh list
            } catch (err) {
                alert('Network error. Please try again.');
            }
        }

        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentTab = this.dataset.tab;
                fetchOrders();
            });
        });

        fetchOrders();

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeOrderModalDirect();
        });
    </script>

</body>

</html>