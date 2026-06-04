<?php
session_start();
require_once '../config/db_config.php';

// Session guard
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$successMsg = '';
$errorMsg   = '';

// Handle AJAX / POST save for phone or address
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field = $_POST['field'] ?? '';
    $value = trim($_POST['value'] ?? '');

    if ($field === 'phone') {
        // Basic Philippine mobile validation (optional — 11 digits starting with 09)
        if ($value !== '' && !preg_match('/^09\d{9}$/', $value)) {
            $errorMsg = 'Phone must be an 11-digit number starting with 09 (e.g. 09123456789).';
        } else {
            $stmt = $connect->prepare("UPDATE users SET phone=? WHERE id=?");
            $stmt->bind_param("si", $value, $userId);
            $stmt->execute();
            $_SESSION['user_phone'] = $value;
            $successMsg = 'Phone number updated.';
        }
    } elseif ($field === 'address') {
        if ($value === '') {
            $errorMsg = 'Address cannot be empty.';
        } else {
            $stmt = $connect->prepare("UPDATE users SET address=? WHERE id=?");
            $stmt->bind_param("si", $value, $userId);
            $stmt->execute();
            $_SESSION['user_address'] = $value;
            $successMsg = 'Address updated.';
        }
    }
}


$stmt = $connect->prepare("SELECT Firstname, Lastname, email, phone, address, avatar FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$fullName = htmlspecialchars($user['Firstname'] . ' ' . $user['Lastname']);
$email    = htmlspecialchars($user['email']);
$phone    = $user['phone']   ? htmlspecialchars($user['phone'])   : '';
$address  = $user['address'] ? htmlspecialchars($user['address']) : '';
$avatar   = $user['avatar']  ? htmlspecialchars($user['avatar'])  : '';

if ($avatar) $_SESSION['user_avatar'] = $avatar;
$_SESSION['user_name']  = $user['Firstname'] . ' ' . $user['Lastname'];
$_SESSION['user_email'] = $user['email'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/addtocart.css">
    <link rel="icon" href="/picture/icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Your Cart</title>
</head>

<body>

    <div class="background"></div>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li><a href="home.php">HOME</a></li>
                <li><a href="menu.php">MENU</a></li>
                <li><a href="../order/status.php">ORDER</a></li>
                <li><a href="../store/store.php">STORES</a></li>
                <li class="sidebar-nav-only-not"><a href="../order/status.php">ORDERS</a></li>
                <li class="sidebar-nav-only"><a href="favorites.php">FAVORITES</a></li>
                <li><a href="../order/cart.php" class="cart-link">
                        <i class="fa-solid fa-cart-shopping fa-lg" style="color: rgb(0, 0, 0);"></i> CART
                    </a></li>
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
                <li><a href="orderstatus.php">ORDERS</a></li>
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

    <!-- CART MAIN -->
    <main class="cart-main">
        <div class="order-header">
            <h1>Your Cart</h1>
            <p>Review your items before placing your order</p>
        </div>

        <div class="cart-box">
            <!-- LEFT: Cart Items -->
            <div class="cart-left">
                <div class="cart-section-title">Cart Items</div>
                <div class="cart-items" id="cartItems">
                </div>
                <div class="cart-continue">
                    <a href="menu.php" class="btn-continue">
                        <i class="fa-solid fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            </div>

            <!-- RIGHT: Order Summary -->
            <div class="cart-right">
                <div class="cart-section-title">Order Summary</div>
                <div class="summary-rows">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="summarySubtotal">₱0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span id="summaryDelivery">₱30.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Taxes</span>
                        <span id="summaryTax">₱5.00</span>
                    </div>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-total">
                    <span>TOTAL</span>
                    <span id="summaryTotal">₱0.00</span>
                </div>
                <button class="btn-checkout">Proceed to Checkout</button>
                <p class="summary-note">
                    By placing your order, you agree to our
                    <a href="#">BoyCold Cafe Terms</a>
                </p>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
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
        // addtocart.php – inside <script>
        const CART_API = '../api/cart_api.php';
        const ORDER_API = '../api/orders_api.php';
        const DELIVERY = 30;
        const TAX = 5;

        let currentCart = [];

        async function loadCart() {
            try {
                const res = await fetch(`${CART_API}?action=get`);
                const data = await res.json();
                if (data.success) {
                    currentCart = data.items;
                    renderCart(currentCart);
                    recalcSummary();
                }
            } catch (err) {
                console.error('Failed to load cart', err);
            }
        }

        function renderCart(items) {
            const container = document.getElementById('cartItems');
            if (!items.length) {
                container.innerHTML = '<div class="empty-cart">Your cart is empty. <a href="menu.php">Browse menu</a></div>';
                return;
            }
            container.innerHTML = items.map(item => `
        <div class="cart-item" data-cart-id="${item.cartId}">
            <div class="cart-item-img"><img src="${item.image}" alt="${item.name}"></div>
            <div class="cart-item-details">
                <p class="item-name">${item.name}</p>
                ${item.milk ? `<p class="item-meta">Milk: ${item.milk}</p>` : ''}
                ${item.addons ? `<p class="item-meta">Add-ons: ${item.addons}</p>` : ''}
            </div>
            <div class="cart-item-qty">
                <button class="qty-btn" onclick="updateQty(${item.cartId}, -1)">−</button>
                <span class="qty-val">${item.qty}</span>
                <button class="qty-btn" onclick="updateQty(${item.cartId}, 1)">+</button>
            </div>
            <div class="cart-item-price">₱${item.total.toFixed(2)}</div>
            <button class="cart-item-delete" onclick="removeItem(${item.cartId})"><i class="fa-solid fa-trash"></i></button>
        </div>
    `).join('');
        }

        async function updateQty(cartId, delta) {
            const item = currentCart.find(i => i.cartId === cartId);
            if (!item) return;
            const newQty = Math.max(1, item.qty + delta);
            try {
                const res = await fetch(CART_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update',
                        cart_id: cartId,
                        quantity: newQty
                    })
                });
                const data = await res.json();
                if (data.success) {
                    await loadCart(); // reload whole cart
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function removeItem(cartId) {
            try {
                const res = await fetch(CART_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'remove',
                        cart_id: cartId
                    })
                });
                if ((await res.json()).success) await loadCart();
            } catch (err) {
                console.error(err);
            }
        }

        function recalcSummary() {
            const sub = currentCart.reduce((s, i) => s + i.total, 0);
            document.getElementById('summarySubtotal').textContent = '₱' + sub.toFixed(2);
            document.getElementById('summaryTotal').textContent = '₱' + (sub + DELIVERY + TAX).toFixed(2);
        }

        async function placeOrder() {
            if (!currentCart.length) return;
            const orderData = {
                action: 'place',
                items: currentCart.map(i => ({
                    name: i.name,
                    unitPrice: i.unitPrice,
                    qty: i.qty,
                    image: i.image,
                    milk: i.milk || '',
                    addons: i.addons || '',
                    orderType: i.orderType || '',
                    notes: i.notes || ''
                })),
                order_type: 'delivery',
                address: document.getElementById('addressInput')?.value || '',
                delivery_fee: 30,
                tax: 5,
                notes: ''
            };
            try {
                const res = await fetch(ORDER_API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });
                const result = await res.json();
                if (result.success) {
                    alert(`Order placed! Order ID: ${result.order_id}`);
                    window.location.href = 'orderstatus.php';
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (err) {
                alert('Network error');
            }
        }

        document.querySelector('.btn-checkout')?.addEventListener('click', placeOrder);
        loadCart();

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
            const dropdown = document.getElementById('avatarDropdown');
            const btn = document.getElementById('navAvatarBtn');
            if (dropdown && btn && !btn.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });

    </script>

</body>

</html>