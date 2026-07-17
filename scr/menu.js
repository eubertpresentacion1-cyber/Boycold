// Replaces localStorage-only favorites with DB-backed API calls.
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
    if (isOpen) {
        setTimeout(() => search.querySelector('input').focus(), 420);
    } else {
        const inp = search.querySelector('input');
        inp.value = '';
        applyFilters('', activeCategory);
    }
}
function toggleAvatarDropdown() {
    document.getElementById('avatarDropdown').classList.toggle('open');
}

// ── Close dropdowns on outside click ─────────────────────────
document.addEventListener('click', function(e) {
    const search    = document.getElementById('navSearch');
    const searchBtn = document.getElementById('searchIconBtn');
    if (search && searchBtn && !search.contains(e.target) && !searchBtn.contains(e.target)) {
        search.classList.remove('open');
        searchBtn.classList.remove('active');
        const inp = search.querySelector('input');
        if (inp) { inp.value = ''; applyFilters('', activeCategory); }
    }
    const wrap = document.querySelector('.avatar-dropdown-wrap');
    if (wrap && !wrap.contains(e.target)) {
        const dd = document.getElementById('avatarDropdown');
        if (dd) dd.classList.remove('open');
    }
});

// ── CATEGORY FILTER + SEARCH ──────────────────────────────────
let activeCategory = 'popular'; // tracks the currently active tab

function applyFilters(query, category) {
    const q = query.trim().toLowerCase();
    let anyVisible = false;

    document.querySelectorAll('.product-card').forEach(card => {
        const cats     = (card.getAttribute('data-category') || '').trim().split(/\s+/).filter(Boolean);
        const name     = (card.getAttribute('data-product-name') || '').toLowerCase();
        const matchCat = !category || cats.includes(category);
        const matchQ   = !q || name.includes(q);
        const show     = matchCat && matchQ;
        card.style.display = show ? '' : 'none';
        if (show) anyVisible = true;
    });

    // Show/hide "no results" panel
    const nsr = document.getElementById('noSearchResults');
    if (nsr) {
        if (!anyVisible && q) {
            nsr.querySelector('.nsr-sub').textContent = `No results for "${query}". Try a different search.`;
            nsr.style.display = 'flex';
        } else {
            nsr.style.display = 'none';
        }
    }
}

document.querySelectorAll('.box ul li a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.box ul li a').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        activeCategory = this.getAttribute('data-filter');
        // Keep any current search query active
        const inp = document.querySelector('#navSearch input');
        applyFilters(inp ? inp.value : '', activeCategory);
    });
});

// ── LIVE SEARCH INPUT ────────────────────────────────────────
(function () {
    const inp = document.querySelector('#navSearch input');
    if (!inp) return;
    inp.addEventListener('input', function () {
        const q = this.value.trim();
        if (q) {
            // While typing: deactivate category tabs and show all matches
            document.querySelectorAll('.box ul li a').forEach(l => l.classList.remove('active'));
            applyFilters(q, '');
        } else {
            // Cleared: restore active category
            const activeLink = document.querySelector('.box ul li a.active');
            if (activeLink) activeLink.classList.add('active');
            applyFilters('', activeCategory);
        }
    });
})();

// ── FAVORITES — DB-BACKED ────────────────────────────────────
const favSet     = new Set();
const pendingFav = new Set(); // prevents double-fire on fast clicks

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
        applyFavUIAll();
    } catch (e) { /* silently ignore if offline */ }
}

function applyFavUIAll() {
    document.querySelectorAll('.product-card').forEach(card => {
        const pname = card.getAttribute('data-product-name');
        const icon  = card.querySelector('.card-heart i');
        if (!pname || !icon) return;
        if (favSet.has(String(pname))) {
            icon.style.color            = '#e53935';
            icon.style.webkitTextStroke = '0';
        } else {
            icon.style.color            = 'transparent';
            icon.style.webkitTextStroke = '1.5px #e53935';
        }
    });
}

// ── SINGLE delegated handler for all card interactions ────────
document.addEventListener('click', async function(e) {

    // ── HEART ──────────────────────────────────────────────────
    const heartBtn = e.target.closest('.card-heart');
    if (heartBtn) {
        e.preventDefault();
        e.stopPropagation();

        const card  = heartBtn.closest('.product-card');
        const pname = card?.getAttribute('data-product-name');
        if (!pname) return;

        // Block if a request is already in flight for this product
        if (pendingFav.has(pname)) return;
        pendingFav.add(pname);

        const icon    = heartBtn.querySelector('i');
        const isFaved = favSet.has(String(pname));

        // Optimistic UI
        if (isFaved) {
            favSet.delete(String(pname));
            icon.style.color            = 'transparent';
            icon.style.webkitTextStroke = '1.5px #e53935';
        } else {
            favSet.add(String(pname));
            icon.style.color            = '#e53935';
            icon.style.webkitTextStroke = '0';
        }

        // Use explicit add/remove — never 'toggle', which can flip
        // twice if the event somehow fires more than once.
        const action = isFaved ? 'remove' : 'add';
        try {
            await fetch('../api/favorites_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, product_name: pname })
            });
        } catch (err) { /* optimistic state stays on network error */ }

        pendingFav.delete(pname);
        return;
    }

    // ── CART ───────────────────────────────────────────────────
    const cartBtn = e.target.closest('.btn-cart');
    if (cartBtn) {
        e.preventDefault();
        e.stopPropagation();

        const card  = cartBtn.closest('.product-card');
        const name  = card.dataset.productName || card.querySelector('.card-name')?.textContent.trim() || '';
        if (!name) return;

        cartBtn.disabled = true;
        try {
            const res  = await fetch('../api/cart_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', product_name: name, quantity: 1 })
            });
            const data = await res.json();
            if (data.success) {
                showCartToast(name);
            } else {
                alert('Could not add to cart. Please try again.');
            }
        } catch (err) {
            alert('Network error. Please try again.');
        }
        cartBtn.disabled = false;
        return;
    }

    // ── ORDER ──────────────────────────────────────────────────
    const orderBtn = e.target.closest('.btn-order');
    if (orderBtn) {
        e.stopPropagation();
        const card   = orderBtn.closest('.product-card');
        const name   = card.querySelector('.card-name')?.textContent.trim()  || '';
        const price  = card.querySelector('.card-price')?.textContent.replace('₱','').trim() || '';
        const image  = card.querySelector('.card-image img')?.getAttribute('src') || '';
        const params = new URLSearchParams({ name, price, image });
        window.location.href = 'ordercustom.php?' + params.toString();
        return;
    }
});

// ── CART TOAST ───────────────────────────────────────────────
function showCartToast(name) {
    const toast = document.getElementById('cartToast');
    document.getElementById('cartToastMsg').textContent = `"${name}" added to cart!`;
    toast.style.display = 'flex';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 2500);
}

// ── INIT ─────────────────────────────────────────────────────
loadFavorites();
const _initActiveLink = document.querySelector('.box ul li a.active');
if (_initActiveLink) {
    activeCategory = _initActiveLink.getAttribute('data-filter') || 'popular';
    _initActiveLink.click();
}