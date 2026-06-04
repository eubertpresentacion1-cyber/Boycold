// ── favorites.js ──────────────────────────────────────────────
// Renders the favorites page using data from favorites_api.php.
// No longer relies on localStorage — all data is per-user in DB.
// ─────────────────────────────────────────────────────────────

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

// ── FAVORITES DATA FROM API ───────────────────────────────────
let favItems = []; // [{product_id, product_name, price, image, category}]

async function fetchFavorites() {
    try {
        const res  = await fetch('../api/favorites_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get' })
        });
        const data = await res.json();
        if (data.success) favItems = data.favorites;
    } catch(e) { favItems = []; }
    renderFavs();
}

async function removeFav(productName) {
    // Optimistic removal
    favItems = favItems.filter(f => f.product_name !== productName);
    renderFavs();

    try {
        await fetch('../api/favorites_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove', product_name: productName })
        });
    } catch(e) { /* ignore */ }
}

function renderFavs() {
    const sort    = document.getElementById('favSort')?.value ?? 'default';
    let   items   = [...favItems];

    if (sort === 'price-asc')  items.sort((a,b) => a.price - b.price);
    else if (sort === 'price-desc') items.sort((a,b) => b.price - a.price);
    else if (sort === 'name-asc')   items.sort((a,b) => a.product_name.localeCompare(b.product_name));

    const grid     = document.getElementById('favGrid');
    const empty    = document.getElementById('favEmpty');
    const countEl  = document.getElementById('favCountNum');

    if (countEl) countEl.textContent = items.length;

    if (items.length === 0) {
        if (empty) empty.style.display = 'flex';
        if (grid)  grid.style.display  = 'none';
        return;
    }
    if (empty) empty.style.display = 'none';
    if (grid)  grid.style.display  = 'grid';

    grid.innerHTML = items.map(item => `
        <div class="product-card" data-category="${item.category ?? ''}" data-product-name="${item.product_name}">
            <div class="card-image">
                <div class="card-image-placeholder">
                    <div class="card-top">
                        <span class="card-badge">Popular<i class="fa-solid fa-star"></i></span>
                        <button class="card-heart fav-active" data-pname="${item.product_name}" title="Remove from favorites">
                            <i class="fa-solid fa-heart" style="color:#e53935; -webkit-text-stroke:0;"></i>
                        </button>
                    </div>
                    <img src="${item.image ?? ''}" alt="${item.product_name}">
                </div>
            </div>
            <div class="card-info">
                <div class="card-mid">
                    <p class="card-name">${item.product_name}</p>
                    <p class="card-price">₱${parseFloat(item.price).toFixed(2)}</p>
                </div>
                <div class="card-footer">
                    <div class="card-actions">
                        <button class="card-btn btn-cart"><i class="fa-solid fa-cart-shopping"></i> Cart</button>
                        <button class="card-btn btn-order"><i class="fa-solid fa-bag-shopping"></i> Order</button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// ── HEART CLICK: remove from favorites ───────────────────────
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.card-heart[data-pname]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const pname = btn.getAttribute('data-pname');
    if (pname) removeFav(pname);
});

// ── ORDER BUTTON ─────────────────────────────────────────────
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-order');
    if (!btn) return;
    e.stopPropagation();
    const card  = btn.closest('.product-card');
    const name  = card.querySelector('.card-name')?.textContent.trim()   || '';
    const price = card.querySelector('.card-price')?.textContent.replace('₱','').trim() || '';
    const image = card.querySelector('.card-image img')?.getAttribute('src') || '';
    const params = new URLSearchParams({ name, price, image });
    window.location.href = 'ordercustom.php?' + params.toString();
});

// ── CART ─────────────────────────────────────────────────────
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
    toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 2500);
}
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-cart');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const card      = btn.closest('.product-card');
    const name      = card.querySelector('.card-name')?.textContent.trim() || '';
    const priceText = card.querySelector('.card-price')?.textContent.replace('₱','').replace('.00','').trim() || '0';
    const unitPrice = parseFloat(priceText);
    const image     = card.querySelector('.card-image img')?.getAttribute('src') || '';
    addToCart(name, unitPrice, image);
    showCartToast(name);
});

// ── SORT LISTENER ─────────────────────────────────────────────
document.getElementById('favSort')?.addEventListener('change', renderFavs);

// ── INIT ─────────────────────────────────────────────────────
fetchFavorites();