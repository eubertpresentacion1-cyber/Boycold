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

        data.favorites.forEach(f => favSet.add(String(f.product_name)));
        // Reflect on cards that already exist in the DOM
        applyFavUIAll();
    } catch (e) { /* silently ignore if offline */ }
}

function applyFavUIAll() {
    document.querySelectorAll('.product-card').forEach(card => {
        const pname = card.getAttribute('data-product-name');
        const icon = card.querySelector('.card-heart i');
        if (!pname || !icon) return;
        if (favSet.has(String(pname))) {
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
    const pname = card.getAttribute('data-product-name');
    if (!pname) return;

    const icon     = btn.querySelector('i');
    const isFaved  = favSet.has(String(pname));

    // Optimistic UI update
    if (isFaved) {
        favSet.delete(String(pname));
        icon.style.color = 'transparent';
        icon.style.webkitTextStroke = '1.5px #e53935';
    } else {
        favSet.add(String(pname));
        icon.style.color = '#e53935';
        icon.style.webkitTextStroke = '0';
    }

    try {
        await fetch('../api/favorites_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle', product_name: pname })
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
async function addToCartAPI(name, price, image) {
    try {
        const res = await fetch('../api/cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add',
                product_name: name,
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
    
    // Get data from attributes
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
    
    // Use API with product name
    if (name) {
        addToCartAPI(name, price, image);
    } else {
        // Fallback to localStorage
        addToCart(name, price, image);
        showCartToast(name);
    }
});

// ── INIT ─────────────────────────────────────────────────────
loadFavorites();
document.querySelector('.box ul li a.active')?.click();