<?php
session_start();
require_once '../config/db_config.php';

// Session guard
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user data for sidebar display
$stmt = $connect->prepare("SELECT firstname, lastname, email, phone, address, avatar, card_no FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$email    = htmlspecialchars($user['email']);
$avatar   = $user['avatar'] ?? '';


// Keep session data in sync
if ($avatar) $_SESSION['user_avatar'] = $avatar;
$_SESSION['user_name']  = $user['firstname'] . ' ' . $user['lastname'];
$_SESSION['user_email'] = $user['email'];
$phone    = $user['phone']   ? htmlspecialchars($user['phone'])   : '';
$address  = $user['address'] ? htmlspecialchars($user['address']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="icon" href="../picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Checkout</title>
</head>
<body>

    <div class="background"></div>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    
    <div class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li class="sidebar-nav-only"><a href="home.php">HOME</a></li>
                <li class="sidebar-nav-only"><a href="menu.php">MENU</a></li>
                <li class="sidebar-nav-only"><a href="favorites.php">FAVORITES</a></li>
                <li class="sidebar-nav-only"><a href="../order/status.php">ORDERS</a></li>
                <li class="sidebar-nav-only"><a href="stores.php">FIND A STORE</a></li>
            </ul>
        </nav>
        <div class="sidebar-user">
            <a href="account.php" class="sidebar-avatar-link">
                <div class="sidebar-avatar" id="sidebarAvatarWrap">
                    <?php if ($avatar): ?>
                        <img id="sidebarAvatarImg" src="<?= $avatar ?>" alt="avatar" style="display:block;">
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon" style="display:none;"></i>
                    <?php else: ?>
                        <img id="sidebarAvatarImg" src="" alt="avatar" style="display:none;">
                        <i class="fa-solid fa-user" id="sidebarAvatarIcon"></i>
                    <?php endif; ?>
                </div>
            </a>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?= $fullName ?></span>
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
                <li><a href="favorites.php">FAVORITES</a></li>
                <li><a href="../order/status.php">ORDERS</a></li>
            </ul>
        </div>
        <div class="logo">
            <img src="/picture/BoyCold Logo 2.png" alt="BoyCold">
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
        <h1>CHECK OUT</h1>
    </div>

    <div class="co-grid">

        <!-- LEFT SIDE -->
        <div class="co-form-col">

            <div class="co-form-panel">

                <!-- CONTACT -->
                <div class="co-section">
                    <h3 class="co-section-title">CONTACT INFORMATION</h3>

                    <div class="co-field">
                        <label class="co-label">Full Name</label>
                        <div class="co-input-wrap">
                            <i class="fa-regular fa-user co-icon"></i>
                            <input type="text"
                                   class="co-input"
                                   placeholder="Full Name"
                                   value="<?= $fullName ?>">
                        </div>
                    </div>

                    <div class="co-field">
                        <label class="co-label">Contact Number</label>
                        <div class="co-input-wrap">
                            <i class="fa-solid fa-phone co-icon"></i>
                            <input type="text"
                                   class="co-input"
                                   placeholder="Phone Number"
                                   value="<?= $phone ?>">
                        </div>
                    </div>
                </div>

                <!-- STORE -->
                <div class="co-section">
                    <h3 class="co-section-title">STORE LOCATION</h3>
                    <label class="co-label">Choose a store branch</label>
                    <select class="co-input co-select">
                        <option value="">Select Branch</option>
                        <option>BoyCold Cafe - Baliuag Bulacan</option>
                        <option>BoyCold Cafe - Bustos Bulacan</option>
                    </select>
                </div>

                <!-- DELIVERY -->
                <div class="co-section">
                    <h3 class="co-section-title">DELIVERY DETAILS</h3>

                    <div class="co-toggle-row">
                        <button type="button" class="co-toggle-btn co-active" onclick="setDeliveryMode(this)">
                            <i class="fa-solid fa-motorcycle"></i>
                            Delivery
                        </button>
                        <button type="button" class="co-toggle-btn" onclick="setDeliveryMode(this)">
                            <i class="fa-solid fa-store"></i>
                            Pick-Up
                        </button>
                    </div>

                    <div style="margin-top:15px;">
                        <label class="co-label">DELIVER TO</label>
                        <select class="co-input co-select" id="deliveryAddress">
                            <option value="">Select Address</option>
                            <option><?= $address ?></option>
                        </select>
                    </div>
                </div>


                <!-- PAYMENT -->
                <div class="co-section">
                    <h3 class="co-section-title">PAYMENT METHOD</h3>

                    <div class="co-payment-list">

                        <label class="co-pay-card co-pay-selected" id="payGcash">
                            <input type="radio" name="payment" checked class="co-radio">
                            <div class="co-pay-logo co-pay-gcash">G</div>
                            <div class="co-pay-info">
                                <div class="co-pay-name">GCash</div>
                                <div class="co-pay-desc">Pay securely using your GCash app</div>
                            </div>
                            <div class="co-pay-circle"></div>
                        </label>

                        <label class="co-pay-card" id="payCod">
                            <input type="radio" name="payment" class="co-radio">
                            <div class="co-pay-logo co-pay-cod">₱</div>
                            <div class="co-pay-info">
                                <div class="co-pay-name">Cash On Delivery</div>
                                <div class="co-pay-desc">Pay upon delivery</div>
                            </div>
                            <div class="co-pay-circle"></div>
                        </label>

                    </div>
                </div>

            </div>

        </div>

        <!-- RIGHT SIDE -->
        <aside class="co-summary-col">

            <div class="co-summary-panel">

                <div class="co-summary-header">
                    <h2>ORDER SUMMARY</h2>
                    <a href="cart.php" class="co-edit-link">Edit Cart</a>
                </div>

                <!-- Items populated by JS -->
                <div class="co-item-list" id="coItemList">
                    <div class="co-loading" style="text-align:center;padding:20px;opacity:.6;">
                        Loading cart…
                    </div>
                </div>

                <div class="co-totals">
                    <div class="co-total-row">
                        <span>Subtotal</span>
                        <span id="coSubtotal">₱0.00</span>
                    </div>
                    <div class="co-total-row">
                        <span>Delivery Fee</span>
                        <span id="coDelivery">₱30.00</span>
                    </div>
                    <div class="co-total-row">
                        <span>Taxes</span>
                        <span id="coTax">₱5.00</span>
                    </div>
                </div>

                <div class="co-grand-total">
                    <span>TOTAL</span>
                    <span id="coTotal">₱0.00</span>
                </div>

                <button class="co-place-btn" id="coPlaceBtn" disabled>Place Order — ₱0.00</button>

                <div class="co-terms">
                    By placing your order, you agree to our
                    <a href="#">BoyCold Cafe Terms</a>
                </div>

            </div>

        </aside>

    </div>

</main>

    <footer>
        <div>© 2026 BoyCold Cafe. All rights reserved.</div>
        <div class="footer-links">
            <a href="#">About Us</a>
            <a href="#">Terms & Conditions</a>
            <a href="#">Privacy</a>
        </div>
        <div class="footer-logo">
            BOYCOLD CAFE
        </div>
    </footer>



    <script>
        /* ── Nav ── */
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
            const dropdown = document.getElementById('avatarDropdown');
            if (wrap && dropdown && !wrap.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });
        function setDeliveryMode(btn) {
            document.querySelectorAll('.co-toggle-btn').forEach(b => b.classList.remove('co-active'));
            btn.classList.add('co-active');
        }
        // Payment card selection
        document.querySelectorAll('.co-pay-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.co-pay-card').forEach(c => c.classList.remove('co-pay-selected'));
                this.classList.add('co-pay-selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        /* ── Cart & Order Summary ── */
        const CART_API  = '../api/cart_api.php';
        const ORDER_API = '../api/orders_api.php';
        const DELIVERY_FEE = 30;
        const TAX = 5;
        let cartItems = [];

        async function loadCart() {
            try {
                const res  = await fetch(CART_API + '?action=get');
                const data = await res.json();
                if (data.success) {
                    cartItems = data.items;
                    renderSummary();
                } else {
                    document.getElementById('coItemList').innerHTML =
                        '<p style="text-align:center;opacity:.6;">Could not load cart.</p>';
                }
            } catch (err) {
                document.getElementById('coItemList').innerHTML =
                    '<p style="text-align:center;opacity:.6;">Network error loading cart.</p>';
            }
        }

        function renderSummary() {
            const list = document.getElementById('coItemList');
            if (!cartItems.length) {
                list.innerHTML = '<p style="text-align:center;padding:20px;opacity:.6;">Your cart is empty. <a href="menu.php">Browse Menu</a></p>';
                updateTotals(0);
                return;
            }
            list.innerHTML = cartItems.map(item => {
                const details = [
                    item.milk   ? 'Milk: '    + item.milk   : '',
                    item.addons ? 'Add-ons: ' + item.addons : '',
                ].filter(Boolean).join('<br>');
                return `
                <div class="co-item">
                    <div class="co-item-img">
                        <img src="${item.image || ''}" alt="${item.name}"
                             onerror="this.style.display='none'">
                    </div>
                    <div class="co-item-info">
                        <p class="co-item-name">${item.name}</p>
                        ${details ? `<p class="co-item-detail">${details}</p>` : ''}
                        <p class="co-item-qty">Qty: ${item.qty}</p>
                    </div>
                    <div class="co-item-price">₱${item.total.toFixed(2)}</div>
                </div>`;
            }).join('');

            const subtotal = cartItems.reduce((s, i) => s + i.total, 0);
            updateTotals(subtotal);
        }

        function updateTotals(subtotal) {
            const total = subtotal + DELIVERY_FEE + TAX;
            document.getElementById('coSubtotal').textContent = '₱' + subtotal.toFixed(2);
            document.getElementById('coDelivery').textContent = '₱' + DELIVERY_FEE.toFixed(2);
            document.getElementById('coTax').textContent      = '₱' + TAX.toFixed(2);
            document.getElementById('coTotal').textContent    = '₱' + total.toFixed(2);

            const btn = document.getElementById('coPlaceBtn');
            btn.textContent = 'Place Order — ₱' + total.toFixed(2);
            btn.disabled = (subtotal === 0);
            btn.style.opacity = subtotal === 0 ? '0.45' : '1';
            btn.style.cursor  = subtotal === 0 ? 'not-allowed' : 'pointer';
        }

        /* ── Place Order ── */
        document.getElementById('coPlaceBtn').addEventListener('click', async function() {
            if (!cartItems.length) return;

            // Gather form values
            const activeDelivery = document.querySelector('.co-toggle-btn.co-active');
            const orderType = activeDelivery ? activeDelivery.textContent.trim().toLowerCase() : 'delivery';
            const isPickup  = orderType === 'pick-up';

            // For pick-up use the branch select; for delivery use the address select
            const branchEl  = document.querySelector('.co-section:nth-child(2) .co-select');
            const addressEl = document.getElementById('deliveryAddress');
            const branch    = branchEl  ? branchEl.value  : '';
            const address   = addressEl ? addressEl.value : '';

            if (!branch) {
                alert('Please select a store branch.');
                return;
            }
            if (!isPickup && !address) {
                alert('Please select a delivery address.');
                return;
            }
            const finalAddress = isPickup ? branch : address;
            const payment = document.querySelector('.co-pay-card.co-pay-selected .co-pay-name')?.textContent || '';

            this.disabled = true;
            this.textContent = 'Placing order…';

            const subtotal = cartItems.reduce((s, i) => s + i.total, 0);
            const orderData = {
                action: 'place',
                items: cartItems.map(i => ({
                    name:      i.name,
                    unitPrice: i.unitPrice,
                    qty:       i.qty,
                    image:     i.image     || '',
                    milk:      i.milk      || '',
                    addons:    i.addons    || '',
                    orderType: i.orderType || orderType,
                    notes:     i.notes     || ''
                })),
                order_type:   orderType,
                address:      finalAddress,
                delivery_fee: DELIVERY_FEE,
                tax:          TAX,
                notes:        ''
            };

            try {
                const res    = await fetch(ORDER_API, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(orderData)
                });
                const result = await res.json();
                if (result.success) {
                    window.location.href = '../order/status.php?order_id=' + result.order_id;
                } else {
                    alert('Error placing order: ' + (result.error || 'Unknown error'));
                    this.disabled = false;
                    updateTotals(subtotal);
                }
            } catch (err) {
                alert('Network error. Please try again.');
                this.disabled = false;
                updateTotals(subtotal);
            }
        });

        // Load cart on page ready
        loadCart();
    </script>

</body>
</html>