// ── menu.js ───────────────────────────────────────────────────
// Replaces localStorage-only favorites with DB-backed API calls.
// Cart still uses localStorage for the addtocart.php flow, but
// a DB sync helper is included at the bottom.
// ─────────────────────────────────────────────────────────────

// ── NAV / SIDEBAR ────────────────────────────────────────────
const nav = document.getElementById('mainNav');

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isOpen  = sidebar.classList.toggle('open');
    overlay.classList.toggle('open', isOpen);
    nav.classList.toggle('sidebar-open', isOpen);
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
    nav.classList.remove('sidebar-open');
}
function toggleSearch() {
    const search = document.getElementById('navSearch');
    const btn    = document.getElementById('searchIconBtn');
    const isOpen = search.classList.toggle('open');
    btn.classList.toggle('active', isOpen);
    if (isOpen) setTimeout(() => search.querySelector('input').focus(), 420);
    else search.querySelector('input').value = '';
}
document.addEventListener('click', function(e) {
    const search = document.getElementById('navSearch');
    const btn    = document.getElementById('searchIconBtn');
    if (!search || !btn) return;
    if (!search.contains(e.target) && !btn.contains(e.target)) {
        search.classList.remove('open');
        btn.classList.remove('active');
        search.querySelector('input').value = '';
    }
});
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

// ── CATEGORY FILTER ──────────────────────────────────────────
document.querySelectorAll('.box ul li a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.box ul li a').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        const filter = this.getAttribute('data-filter');
        document.querySelectorAll('.product-card').forEach(card => {
            const cats = (card.getAttribute('data-category') || '').split(' ');
            card.style.display = cats.includes(filter) ? '' : 'none';
        });
    });
});

// ── FAVORITES — DB-BACKED ────────────────────────────────────
// We store favorited product IDs in a local Set for instant UI,
// and sync every toggle to favorites_api.php.
const favSet = new Set();

async function loadFavorites() {
    try {
        const res  = await fetch('../api/favorites_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get' })
        });
        const data = await res.json();
        if (!data.success) return;

        data.favorites.forEach(f => favSet.add(String(f.product_id)));
        // Reflect on cards that already exist in the DOM
        applyFavUIAll();
    } catch (e) { /* silently ignore if offline */ }
}

function applyFavUIAll() {
    document.querySelectorAll('.product-card').forEach(card => {
        const pid  = card.getAttribute('data-product-id') || card.getAttribute('data-id');
        const icon = card.querySelector('.card-heart i');
        if (!pid || !icon) return;
        if (favSet.has(String(pid))) {
            icon.style.color = '#e53935';
            icon.style.webkitTextStroke = '0';
        } else {
            icon.style.color = 'transparent';
            icon.style.webkitTextStroke = '1.5px #e53935';
        }
    });
}

// Heart button delegation — works for dynamically added cards too
document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.card-heart');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    const card = btn.closest('.product-card');
    // data-product-id holds the integer DB id; data-id holds the slug fallback
    const pid  = card.getAttribute('data-product-id') || card.getAttribute('data-id');
    if (!pid) return;

    const icon     = btn.querySelector('i');
    const isFaved  = favSet.has(String(pid));
    const numericId = parseInt(pid, 10);

    // Optimistic UI update
    if (isFaved) {
        favSet.delete(String(pid));
        icon.style.color = 'transparent';
        icon.style.webkitTextStroke = '1.5px #e53935';
    } else {
        favSet.add(String(pid));
        icon.style.color = '#e53935';
        icon.style.webkitTextStroke = '0';
    }

    // If pid is a slug (non-numeric), we can't call the API yet
    // (you'd need the real numeric product_id from the DB).
    // Add data-product-id="<?= $product['id'] ?>" to each card in PHP.
    if (isNaN(numericId)) {
        // Store in localStorage as fallback until data-product-id is wired
        const local = JSON.parse(localStorage.getItem('boycold_favorites') || '[]');
        if (isFaved) {
            const idx = local.indexOf(pid);
            if (idx > -1) local.splice(idx, 1);
        } else {
            if (!local.includes(pid)) local.push(pid);
        }
        localStorage.setItem('boycold_favorites', JSON.stringify(local));
        return;
    }

    try {
        await fetch('../api/favorites_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle', product_id: numericId })
        });
    } catch (err) { /* network error — optimistic state stays */ }
});

// ── ORDER BUTTON ─────────────────────────────────────────────
document.querySelectorAll('.btn-order').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const card  = this.closest('.product-card');
        const name  = card.querySelector('.card-name')?.textContent.trim()   || '';
        const price = card.querySelector('.card-price')?.textContent.replace('₱','').trim() || '';
        const image = card.querySelector('.card-image img')?.getAttribute('src') || '';
        const params = new URLSearchParams({ name, price, image });
        window.location.href = 'ordercustom.php?' + params.toString();
    });
});

// ── CART via DB API ──────────────────────────────────────────
async function addToCartAPI(productId, name, price, image) {
    try {
        const res = await fetch('../api/cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                product_id: productId,
                quantity: 1,
                milk: '',
                addons: '',
                order_type: '',
                notes: ''
            })
        });
        const data = await res.json();
        if (data.success) {
            showCartToast(name);
        } else {
            console.error('Add to cart failed', data.error);
        }
    } catch (err) {
        console.error('Network error', err);
    }
}

// localStorage fallback for cart
const CART_KEY = 'boycold_cart';
function getCart() {
    try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; }
    catch(e) { return []; }
}
function saveCart(cart) { localStorage.setItem(CART_KEY, JSON.stringify(cart)); }
function addToCart(name, unitPrice, image) {
    const cart = getCart();
    const existing = cart.find(i => i.name === name && !i.milk && !i.addons && !i.notes);
    if (existing) {
        existing.qty++;
        existing.total = existing.unitPrice * existing.qty;
    } else {
        cart.push({ name, unitPrice, qty: 1, total: unitPrice,
                    image: image || '', milk: '', addons: '', orderType: '', notes: '' });
    }
    saveCart(cart);
}

function showCartToast(name) {
    const toast = document.getElementById('cartToast');
    document.getElementById('cartToastMsg').textContent = `"${name}" added to cart!`;
    toast.style.display = 'flex';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.style.display = 'none', 2500);
}

// Attach event listener for Cart buttons (delegation)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-cart');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    
    const card = btn.closest('.product-card');
    
    // Try to get data from attributes first (newer data-* attributes)
    let productId = card.dataset.productId;
    let name = card.dataset.productName;
    let price = parseFloat(card.dataset.price);
    let image = card.dataset.image;
    
    // Fallback to DOM query if data attributes not set
    if (!name) {
        name = card.querySelector('.card-name')?.textContent.trim() || '';
    }
    if (!price || isNaN(price)) {
        const priceText = card.querySelector('.card-price')?.textContent.replace('₱','').replace('.00','').trim() || '0';
        price = parseFloat(priceText);
    }
    if (!image) {
        image = card.querySelector('.card-image img')?.getAttribute('src') || '';
    }
    
    // If we have a numeric product ID, use API; otherwise use localStorage fallback
    if (productId && !isNaN(parseInt(productId, 10))) {
        addToCartAPI(parseInt(productId, 10), name, price, image);
    } else {
        // Fallback to localStorage
        addToCart(name, price, image);
        showCartToast(name);
    }
});

// ── INIT ─────────────────────────────────────────────────────
loadFavorites();
document.querySelector('.box ul li a.active')?.click();