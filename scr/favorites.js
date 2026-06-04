// ── favorites.js ──────────────────────────────────────────────
// Renders the favorites page using data from favorites_api.php.
// All cart and favorites data is stored in the DB, not localStorage.
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
function toggleAvatarDropdown() {
    document.getElementById('avatarDropdown').classList.toggle('open');
}

// ── Close dropdowns when clicking outside ────────────────────
document.addEventListener('click', function(e) {
    // Search bar
    const search = document.getElementById('navSearch');
    const searchBtn = document.getElementById('searchIconBtn');
    if (search && searchBtn && !search.contains(e.target) && !searchBtn.contains(e.target)) {
        search.classList.remove('open');
        searchBtn.classList.remove('active');
        search.querySelector('input').value = '';
    }
    // Avatar dropdown
    const wrap = document.querySelector('.avatar-dropdown-wrap');
    if (wrap && !wrap.contains(e.target)) {
        const dd = document.getElementById('avatarDropdown');
        if (dd) dd.classList.remove('open');
    }
});

// ── FAVORITES DATA FROM API ───────────────────────────────────
let favItems = [];

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
    const sort   = document.getElementById('favSort')?.value ?? 'default';
    let   items  = [...favItems];

    if (sort === 'price-asc')       items.sort((a,b) => a.price - b.price);
    else if (sort === 'price-desc') items.sort((a,b) => b.price - a.price);
    else if (sort === 'name-asc')   items.sort((a,b) => a.product_name.localeCompare(b.product_name));

    const grid    = document.getElementById('favGrid');
    const empty   = document.getElementById('favEmpty');
    const countEl = document.getElementById('favCountNum');

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
                    <p class="card-price">&#8369;${parseFloat(item.price).toFixed(2)}</p>
                </div>
                <div class="card-footer">
                    <div class="card-actions">
                        <button class="card-btn btn-cart" data-pname="${item.product_name}">
                            <i class="fa-solid fa-cart-shopping"></i> Cart
                        </button>
                        <button class="card-btn btn-order" data-pname="${item.product_name}">
                            <i class="fa-solid fa-bag-shopping"></i> Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// ── SINGLE delegated click handler for the whole grid ────────
document.addEventListener('click', function(e) {

    // HEART: remove from favorites
    const heartBtn = e.target.closest('.card-heart[data-pname]');
    if (heartBtn) {
        e.preventDefault();
        e.stopPropagation();
        const pname = heartBtn.getAttribute('data-pname');
        if (pname) removeFav(pname);
        return; // stop — don't fall through to other handlers
    }

    // CART button
    const cartBtn = e.target.closest('.btn-cart[data-pname]');
    if (cartBtn) {
        e.preventDefault();
        e.stopPropagation();
        const name = cartBtn.getAttribute('data-pname');
        if (name) addToCartDB(cartBtn, name);
        return;
    }

    // ORDER button
    const orderBtn = e.target.closest('.btn-order[data-pname]');
    if (orderBtn) {
        e.stopPropagation();
        const card  = orderBtn.closest('.product-card');
        const name  = card.querySelector('.card-name')?.textContent.trim()  || '';
        const price = card.querySelector('.card-price')?.textContent.replace('₱','').trim() || '';
        const image = card.querySelector('.card-image img')?.getAttribute('src') || '';
        const params = new URLSearchParams({ name, price, image });
        window.location.href = 'ordercustom.php?' + params.toString();
        return;
    }
});

// ── ADD TO CART via DB API ────────────────────────────────────
async function addToCartDB(btn, productName) {
    btn.disabled = true;
    try {
        const res  = await fetch('../api/cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', product_name: productName, quantity: 1 })
        });
        const data = await res.json();
        if (data.success) {
            showCartToast(productName);
        } else {
            alert('Failed to add to cart. Please try again.');
        }
    } catch(e) {
        alert('Network error. Please try again.');
    }
    btn.disabled = false;
}

// ── CART TOAST ───────────────────────────────────────────────
function showCartToast(name) {
    const toast = document.getElementById('cartToast');
    document.getElementById('cartToastMsg').textContent = `"${name}" added to cart!`;
    toast.style.display = 'flex';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 2500);
}

// ── SORT LISTENER ─────────────────────────────────────────────
document.getElementById('favSort')?.addEventListener('change', renderFavs);

// ── INIT ─────────────────────────────────────────────────────
fetchFavorites();