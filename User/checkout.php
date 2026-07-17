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

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$email    = htmlspecialchars($user['email']);
$avatar   = $user['avatar'] ?? '';


// Keep session data in sync
if ($avatar) $_SESSION['user_avatar'] = $avatar;
$_SESSION['user_name']  = $user['firstname'] . ' ' . $user['lastname'];
$_SESSION['user_email'] = $user['email'];
$phone    = $user['phone']   ? htmlspecialchars($user['phone'])   : '';

// Saved delivery addresses (address book) — most recently added
// default first, so it's pre-selected in the DELIVER TO dropdown.
$userName = $_SESSION['user_name'];
$addrStmt = $connect->prepare(
    "SELECT id, label, recipient_name, phone, street_address, barangay, city, province, zip_code, is_default
     FROM addresses
     WHERE user_name = ?
     ORDER BY is_default DESC, created_at DESC"
);
$addrStmt->bind_param("s", $userName);
$addrStmt->execute();
$savedAddresses = $addrStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$addrStmt->close();

// Fetch available branches for branch selection
$branchStmt = $connect->prepare("SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name");
$branchStmt->execute();
$branches = $branchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$branchStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="css/Address-modal.css">
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
            <a href="account.php" class="sidebar-avatar-link">
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
                <li><a href="status.php">ORDERS</a></li>
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
                                   id="phoneInput"
                                   placeholder="09XXXXXXXXX"
                                   maxlength="11"
                                   value="<?= $phone ?>">
                        </div>
                    </div>
                </div>

                <!-- STORE -->
                <div class="co-section">
                    <h3 class="co-section-title">STORE LOCATION</h3>
                    <label class="co-label">Choose a store branch</label>
                    <select class="co-input co-select" id="branchSelect">
                        <option value="">Select Branch</option>
                        <?php foreach ($branches as $branch): ?>
                        <option value="<?= $branch['id'] ?>"><?= htmlspecialchars($branch['branch_name']) ?></option>
                        <?php endforeach; ?>
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
                        <div id="addressFieldWrap"><!-- select or plain input, rendered by JS --></div>
                        <button type="button" class="co-add-address-link" id="addAddressBtn" onclick="openAddAddressModal()">
                            <i class="fa-solid fa-plus"></i> Add new address
                        </button>
                    </div>
                </div>


                <!-- PAYMENT -->
                <div class="co-section">
                    <h3 class="co-section-title">PAYMENT METHOD</h3>

                    <div class="co-payment-list">

                        <label class="co-pay-card co-pay-selected" id="payGcash">
                            <input type="radio" name="payment" value="gcash" checked class="co-radio">
                            <div class="co-pay-logo co-pay-gcash">G</div>
                            <div class="co-pay-info">
                                <div class="co-pay-name">GCash</div>
                                <div class="co-pay-desc">Pay securely using your GCash app</div>
                            </div>
                            <div class="co-pay-circle"></div>
                        </label>

                        <label class="co-pay-card" id="payCod">
                            <input type="radio" name="payment" value="cod" class="co-radio">
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

    <!-- ADD NEW ADDRESS MODAL -->
    <div class="addr-modal-overlay" id="addressModalOverlay">
        <div class="addr-modal">
            <h2 class="addr-modal-title">Add New Address</h2>
            <p class="addr-modal-sub">Fill in your details below to add a new delivery address.</p>

            <div class="addr-modal-row">
                <div class="addr-field">
                    <label>Label (Optional)</label>
                    <input type="text" id="addrLabel" placeholder="e.g. Home, Work">
                </div>
                <div class="addr-field">
                    <label>Recipient Name</label>
                    <input type="text" id="addrRecipient" placeholder="Full Name">
                </div>
            </div>

            <div class="addr-field">
                <label>Street Address</label>
                <input type="text" id="addrStreet" placeholder="House/Building No., Street Name">
            </div>

            <div class="addr-modal-row">
                <div class="addr-field">
                    <label>Barangay</label>
                    <input type="text" id="addrBarangay" placeholder="Enter Barangay">
                </div>
                <div class="addr-field">
                    <label>City/Municipality</label>
                    <input type="text" id="addrCity" placeholder="Enter City/Municipality">
                </div>
            </div>

            <div class="addr-modal-row">
                <div class="addr-field">
                    <label>Province</label>
                    <input type="text" id="addrProvince" placeholder="Enter Province">
                </div>
                <div class="addr-field">
                    <label>Zip Code</label>
                    <input type="text" id="addrZip" placeholder="Enter Zip Code" maxlength="4">
                </div>
            </div>

            <label class="addr-checkbox-row">
                <input type="checkbox" id="addrIsDefault">
                Set as default address
            </label>

            <p class="addr-modal-msg" id="addrModalMsg"></p>

            <div class="addr-modal-actions">
                <button type="button" class="addr-btn-cancel" onclick="closeAddAddressModal()">Cancel</button>
                <button type="button" class="addr-btn-save" id="addrSaveBtn" onclick="saveNewAddress()">Save Address</button>
            </div>
        </div>
    </div>

</main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> BoyCold Café. All Rights Reserved.</p>
        <div class="footer-links">
            <a href="/footer-link/about.php">About Us</a>
            <a href="/footer-link/terms.php">Terms & Conditions</a>
            <a href="/footer-link/privacy.php">Privacy</a>
        </div>
        <div class="footer-logo">
            BOYCOLD CAFE
        </div>
    </footer>



    <script>
        /* ── Saved delivery addresses (address book) from the DB ── */
        const SAVED_ADDRESSES = <?= json_encode($savedAddresses) ?>;
        let addresses = Array.isArray(SAVED_ADDRESSES) ? SAVED_ADDRESSES : [];

        function formatAddress(a) {
            return [a.street_address, a.barangay, a.city, a.province, a.zip_code]
                .filter(Boolean).join(', ');
        }

        // Renders either a <select> of saved addresses, or a plain typable
        // input when the user has no saved addresses yet.
        function renderAddressField() {
            const wrap = document.getElementById('addressFieldWrap');
            if (!wrap) return;

            if (addresses.length > 0) {
                const prevSelected = document.getElementById('addressChoice')?.value;
                const options = addresses.map(a => {
                    const labelPart = a.label ? a.label + ' — ' : '';
                    return `<option value="${a.id}">${labelPart}${formatAddress(a)}</option>`;
                }).join('');
                wrap.innerHTML = `<select class="co-input co-select" id="addressChoice">${options}</select>`;
                const sel = document.getElementById('addressChoice');
                if (prevSelected && [...sel.options].some(o => o.value === prevSelected)) {
                    sel.value = prevSelected;
                }
            } else {
                wrap.innerHTML = `<input type="text" class="co-input" id="addressNewInput" placeholder="Enter delivery address">`;
            }
        }

        function getPhoneValue() {
            return document.getElementById('phoneInput')?.value.trim() || '';
        }

        function getAddressValue() {
            const select = document.getElementById('addressChoice');
            if (select) {
                const picked = addresses.find(a => String(a.id) === select.value);
                return picked ? formatAddress(picked) : '';
            }
            return document.getElementById('addressNewInput')?.value.trim() || '';
        }

        /* ── Add New Address modal ── */
        const ADDRESS_MODAL_FIELDS = ['addrLabel','addrRecipient','addrStreet','addrBarangay','addrCity','addrProvince','addrZip'];

        function openAddAddressModal() {
            document.getElementById('addressModalOverlay').style.display = 'flex';
        }

        function closeAddAddressModal() {
            document.getElementById('addressModalOverlay').style.display = 'none';
            ADDRESS_MODAL_FIELDS.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
            const chk = document.getElementById('addrIsDefault');
            if (chk) chk.checked = false;
            const msg = document.getElementById('addrModalMsg');
            if (msg) msg.textContent = '';
        }

        async function saveNewAddress() {
            const label     = document.getElementById('addrLabel').value.trim();
            const recipient = document.getElementById('addrRecipient').value.trim();
            const street    = document.getElementById('addrStreet').value.trim();
            const barangay  = document.getElementById('addrBarangay').value.trim();
            const city      = document.getElementById('addrCity').value.trim();
            const province  = document.getElementById('addrProvince').value.trim();
            const zip       = document.getElementById('addrZip').value.trim();
            const isDefault = document.getElementById('addrIsDefault').checked;

            const msg = document.getElementById('addrModalMsg');
            if (!recipient || !street || !barangay || !city || !province || !zip) {
                if (msg) msg.textContent = 'Please fill in all required fields.';
                return;
            }

            const saveBtn = document.getElementById('addrSaveBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving…';

            try {
                const res = await fetch(ADDRESS_API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add', label, recipient_name: recipient,
                        phone: '',
                        street_address: street, barangay, city, province,
                        zip_code: zip, is_default: isDefault
                    })
                });
                const data = await res.json();
                if (data.success) {
                    if (data.address.is_default) {
                        addresses.forEach(a => a.is_default = 0);
                    }
                    addresses.unshift(data.address);
                    renderAddressField();
                    const sel = document.getElementById('addressChoice');
                    if (sel) sel.value = data.address.id;
                    closeAddAddressModal();
                } else {
                    if (msg) msg.textContent = data.error || 'Could not save address.';
                }
            } catch (err) {
                if (msg) msg.textContent = 'Network error. Please try again.';
            }

            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Address';
        }

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
        const CART_API    = '../api/cart_api.php';
        const ORDER_API   = '../api/orders_api.php';
        const ADDRESS_API = '../api/addresses_api.php';
        const DELIVERY_FEE = 30;
        const TAX = 5;
        const DIRECT_KEY = 'boycold_direct_order';
        let cartItems = [];
        let isDirectOrder = false; // true = "buy now" from ordercustom.php (single item only)

        // Render the DELIVER TO field on page load
        renderAddressField();

        async function loadCart() {
            // ── Direct "buy now" order (came from ordercustom.php) ──
            // If a stashed item exists, check out ONLY that item and
            // never merge it with whatever else is sitting in the
            // user's persistent cart.
            const directRaw = sessionStorage.getItem(DIRECT_KEY);
            if (directRaw) {
                try {
                    cartItems = [JSON.parse(directRaw)];
                    isDirectOrder = true;
                    renderSummary();
                    return;
                } catch (err) {
                    sessionStorage.removeItem(DIRECT_KEY); // corrupted — fall back to cart
                }
            }

            try {
                const res  = await fetch(CART_API + '?action=get');
                const data = await res.json();
                if (data.success) {
                    let allItems = data.items;
                    
                    // ── Filter for only selected items from addtocart ──
                    const selectedRaw = sessionStorage.getItem('boycold_selected_items');
                    if (selectedRaw) {
                        try {
                            const selectedIds = JSON.parse(selectedRaw);
                            if (Array.isArray(selectedIds) && selectedIds.length > 0) {
                                allItems = allItems.filter(item => selectedIds.includes(item.cartId));
                            }
                            sessionStorage.removeItem('boycold_selected_items'); // consume once
                        } catch (err) {
                            console.error('Error parsing selected items:', err);
                        }
                    }
                    
                    cartItems = allItems;
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
            const deliveryText = activeDelivery ? activeDelivery.textContent.trim().toLowerCase() : 'delivery';
            const isPickup  = deliveryText.includes('pick');
            const orderType = isPickup ? 'takeout' : 'delivery';

            // For pick-up use the branch select; for delivery use the address field
            const branchEl = document.getElementById('branchSelect');
            const branchId = branchEl ? branchEl.value : '';
            const branchName = branchEl ? branchEl.options[branchEl.selectedIndex]?.text : '';
            const address  = getAddressValue();
            const phone    = getPhoneValue();

            if (!branchId) {
                alert('Please select a store branch.');
                return;
            }
            if (!isPickup && !address) {
                alert('Please select or enter a delivery address.');
                return;
            }
            if (!phone) {
                alert('Please provide a contact number.');
                return;
            }
            const finalAddress = isPickup ? branchName : address;
            const selectedPayment = document.querySelector('input[name="payment"]:checked')?.value || 'cod';
            const paymentMethod = ['gcash', 'cod'].includes(selectedPayment) ? selectedPayment : 'cod';

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
                order_type:      orderType,
                payment_method:  paymentMethod,
                branch_id:       branchId,
                address:         finalAddress,
                contact_number:  phone,
                delivery_fee:    DELIVERY_FEE,
                tax:             TAX,
                notes:           '',
                // Only let the server clear the persistent cart when this
                // order actually came from it. A direct "buy now" order
                // must never wipe out unrelated items sitting in the cart.
                from_cart:       !isDirectOrder
            };

            try {
                const res    = await fetch(ORDER_API, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(orderData)
                });
                
                // Check if response is ok
                if (!res.ok) {
                    const errText = await res.text();
                    console.error('API Response Error:', res.status, errText);
                    alert('Error: ' + res.status + ' ' + res.statusText);
                    this.disabled = false;
                    updateTotals(subtotal);
                    return;
                }
                
                const result = await res.json();
                if (result.success) {
                    if (isDirectOrder) sessionStorage.removeItem(DIRECT_KEY);
                    window.location.href = 'status.php?order_id=' + encodeURIComponent(result.order_id);
                } else {
                    alert('Error placing order: ' + (result.error || 'Unknown error'));
                    this.disabled = false;
                    updateTotals(subtotal);
                }
            } catch (err) {
                console.error('Network error:', err);
                alert('Network error: ' + err.message + '\n\nPlease check browser console for details.');
                this.disabled = false;
                updateTotals(subtotal);
            }
        });

        // Load cart on page ready
        loadCart();
    </script>

</body>
</html>