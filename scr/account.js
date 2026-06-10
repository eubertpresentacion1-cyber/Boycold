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

function toggleSearch() {
    const search = document.getElementById('navSearch');
    const btn = document.getElementById('searchIconBtn');
    const isOpen = search.classList.toggle('open');
    btn.classList.toggle('active', isOpen);
    if (isOpen) setTimeout(() => search.querySelector('input').focus(), 420);
    else search.querySelector('input').value = '';
}

document.addEventListener('click', function (e) {
    const search = document.getElementById('navSearch');
    const btn = document.getElementById('searchIconBtn');
    if (!search || !btn) return;
    if (!search.contains(e.target) && !btn.contains(e.target)) {
        search.classList.remove('open');
        btn.classList.remove('active');
        search.querySelector('input').value = '';
    }
});

// ── Inline edit helpers ────────────────────────────────────
function toggleEdit(field) {
    const editRow = document.getElementById(field + '-edit');
    const isOpen = editRow.style.display === 'flex';
    editRow.style.display = isOpen ? 'none' : 'flex';
    document.getElementById(field + '-msg').textContent = '';
    if (!isOpen) document.getElementById(field + '-input').focus();
}

async function saveField(field) {
    // Settings panel fields (inp-*) vs profile card inline fields (*-input)
    const isSettingsPanel = !!document.getElementById('inp-' + field) || field === 'name';

    if (isSettingsPanel) {
        // ── Settings panel save ──
        const body = new FormData();
        body.append('field', field);
        if (field === 'name') {
            body.append('firstname', document.getElementById('inp-firstname').value.trim());
            body.append('lastname',  document.getElementById('inp-lastname').value.trim());
        } else if (field === 'phone') {
            body.append('value', document.getElementById('inp-phone').value.trim());
        } else if (field === 'address') {
            body.append('value', document.getElementById('inp-address').value.trim());
        }
        try {
            const res  = await fetch('account.php', { method: 'POST', body });
            const data = await res.json();
            const msgKey = field === 'name' ? 'username' : field;
            const msgEl  = document.getElementById('msg-' + msgKey);
            if (!data.success) {
                if (msgEl) { msgEl.textContent = data.error; msgEl.style.color = '#c0392b'; }
                return;
            }
            if (msgEl) { msgEl.textContent = 'Saved successfully!'; msgEl.style.color = '#27ae60'; }
            if (field === 'name') {
                const fn = data.fullname;
                ['display-fullname'].forEach(id => { const el = document.getElementById(id); if (el) el.textContent = fn; });
                document.querySelectorAll('.profile-name, .account-name, [data-field="fullname"]').forEach(el => el.textContent = fn);
            } else if (field === 'phone') {
                document.querySelectorAll('.profile-phone, [data-field="phone"]').forEach(el => el.textContent = data.value || '—');
            } else if (field === 'address') {
                document.querySelectorAll('.profile-address, [data-field="address"]').forEach(el => el.textContent = data.value || '—');
            }
        } catch (err) {
            const msgKey = field === 'name' ? 'username' : field;
            const msgEl  = document.getElementById('msg-' + msgKey);
            if (msgEl) { msgEl.textContent = 'Network error. Please try again.'; msgEl.style.color = '#c0392b'; }
        }
        return;
    }

    // ── Profile card inline save ──
    const input = document.getElementById(field + '-input');
    const msgEl = document.getElementById(field + '-msg');
    const valEl = document.getElementById(field + '-val');
    const value = input.value.trim();

    msgEl.textContent = '';
    msgEl.className = '';

    if (field === 'phone' && value !== '' && !/^09\d{9}$/.test(value)) {
        msgEl.textContent = 'Must be 11 digits starting with 09.';
        msgEl.className = 'flash-error';
        return;
    }

    const fd = new FormData();
    fd.append('field', field);
    fd.append('value', value);

    try {
        const res  = await fetch('account.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
            msgEl.textContent = data.error || 'Save failed.';
            msgEl.className = 'flash-error';
            return;
        }
    } catch (err) {
        msgEl.textContent = 'Network error. Try again.';
        msgEl.className = 'flash-error';
        return;
    }

    if (value === '') {
        valEl.textContent = field === 'address' ? '* Add your address' : '* Add your phone number';
        valEl.className = 'placeholder-text';
    } else {
        valEl.textContent = value;
        valEl.className = '';
    }

    msgEl.textContent = 'Saved ✓';
    msgEl.className = 'flash-success';
    document.getElementById(field + '-edit').style.display = 'none';
    setTimeout(() => { msgEl.textContent = ''; }, 3000);
}

// ── Avatar modal ───────────────────────────────────────────
function openAvatarModal() {
    document.getElementById('avatarModal').style.display = 'flex';
}
function closeAvatarModal() {
    document.getElementById('avatarModal').style.display = 'none';
}
function triggerCamera() {
    document.getElementById('avatarCameraInput').click();
}
function triggerFilePicker() {
    document.getElementById('avatarFileInput').click();
}
const avatarModal = document.getElementById('avatarModal');
if (avatarModal) {
    avatarModal.addEventListener('click', function (e) {
        if (e.target === this) closeAvatarModal();
    });
}

// ── Avatar upload (shared handler) ────────────────────────
async function handleAvatarFile(file) {
    if (!file) return;

    const avatarMsg     = document.getElementById('avatar-msg');
    const profileImg    = document.getElementById('profileAvatarImg');
    const profileIcon   = document.getElementById('profileAvatarIcon');
    const sidebarImg    = document.getElementById('sidebarAvatarImg');
    const sidebarIcon   = document.getElementById('sidebarAvatarIcon');
    const navImg        = document.getElementById('navAvatarImg');
    const navIcon       = document.getElementById('navAvatarIcon');

    // Instant local preview
    const localURL = URL.createObjectURL(file);
    if (profileImg)  { profileImg.src = localURL; profileImg.style.cssText = 'position:absolute;inset:0;width:110px;height:110px;object-fit:cover;border-radius:50%;display:block;'; }
    if (profileIcon) { profileIcon.style.display = 'none'; }
    if (sidebarImg)  { sidebarImg.src = localURL; sidebarImg.style.display = ''; }
    if (sidebarIcon) { sidebarIcon.style.display = 'none'; }
    if (navImg)      { navImg.src = localURL; navImg.style.display = 'block'; }
    if (navIcon)     { navIcon.style.display = 'none'; }

    avatarMsg.style.color = '#888';
    avatarMsg.textContent = 'Uploading…';

    const fd = new FormData();
    fd.append('avatar', file);

    try {
        const res  = await fetch('uploadavatar.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const newSrc = data.path + '?v=' + Date.now();
            if (profileImg)  profileImg.src = newSrc;
            if (sidebarImg)  sidebarImg.src = newSrc;
            if (navImg)      navImg.src = newSrc;
            if (profileIcon) profileIcon.style.display = 'none';
            if (navIcon)     navIcon.style.display = 'none';
            avatarMsg.style.color = '#27ae60';
            // Show the success message from the server (database update confirmation)
            avatarMsg.textContent = data.message || 'Photo updated!';
            setTimeout(() => { avatarMsg.textContent = ''; }, 3000);
        } else {
            avatarMsg.style.color = '#c0392b';
            avatarMsg.textContent = data.error || 'Upload failed.';
        }
    } catch (err) {
        avatarMsg.style.color = '#c0392b';
        avatarMsg.textContent = 'Network error. Try again.';
    }

    URL.revokeObjectURL(localURL);
    document.getElementById('avatarFileInput').value   = '';
    document.getElementById('avatarCameraInput').value = '';
}

const avatarFileInput   = document.getElementById('avatarFileInput');
const avatarCameraInput = document.getElementById('avatarCameraInput');
if (avatarFileInput)   avatarFileInput.addEventListener('change',   function () { closeAvatarModal(); handleAvatarFile(this.files[0]); });
if (avatarCameraInput) avatarCameraInput.addEventListener('change', function () { closeAvatarModal(); handleAvatarFile(this.files[0]); });

// ── Avatar hover overlay ───────────────────────────────────
const avatarWrap    = document.getElementById('profileAvatarWrap');
const avatarOverlay = document.getElementById('avatarOverlay');
if (avatarWrap && avatarOverlay) {
    avatarWrap.addEventListener('mouseenter', () => avatarOverlay.style.opacity = '1');
    avatarWrap.addEventListener('mouseleave', () => avatarOverlay.style.opacity = '0');
}

// ── Avatar image error fallback ────────────────────────────
function setupAvatarErrorHandlers() {
    const handleImageError = function(imgElement) {
        const wrapper = imgElement.closest('.sidebar-avatar');
        if (!wrapper) return;
        
        imgElement.style.display = 'none';
        const icon = wrapper.querySelector('.fa-solid.fa-user');
        if (icon) {
            icon.style.display = '';
        }
    };
    
    // Sidebar avatar
    const sidebarImg = document.getElementById('sidebarAvatarImg');
    if (sidebarImg && sidebarImg.src) {
        sidebarImg.addEventListener('error', function() {
            handleImageError(this);
        });
    }
    
    // Nav avatar
    const navImg = document.getElementById('navAvatarImg');
    if (navImg && navImg.src) {
        navImg.addEventListener('error', function() {
            handleImageError(this);
        });
    }
    
    // Profile avatar
    const profileImg = document.getElementById('profileAvatarImg');
    if (profileImg && profileImg.src) {
        profileImg.addEventListener('error', function() {
            handleImageError(this);
        });
    }
}

// Run on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupAvatarErrorHandlers);
} else {
    setupAvatarErrorHandlers();
}

// ── Nav avatar dropdown ────────────────────────────────────
function toggleAvatarDropdown() {
    document.getElementById('avatarDropdown').classList.toggle('open');
}
document.addEventListener('click', function (e) {
    const wrap = document.querySelector('.avatar-dropdown-wrap');
    if (wrap && !wrap.contains(e.target)) {
        const dd = document.getElementById('avatarDropdown');
        if (dd) dd.classList.remove('open');
    }
});

// ── Settings panel ─────────────────────────────────────────
function expandSettings() {
    document.getElementById('settingsCard').classList.add('expanded');
}
function collapseSettings() {
    const card = document.getElementById('settingsCard');
    const expandedView = card.querySelector('.card-expanded-view');
    expandedView.style.display = 'none';
    card.classList.remove('expanded');
    setTimeout(() => {
        expandedView.style.display = '';
        document.querySelectorAll('.s-panel').forEach(p => p.classList.remove('s-active'));
        document.getElementById('s-panel-welcome').classList.add('s-active');
        document.querySelectorAll('.s-item').forEach(i => i.classList.remove('s-active'));
    }, 420);
}
function showSPanel(name, el) {
    document.querySelectorAll('.s-panel').forEach(p => p.classList.remove('s-active'));
    document.querySelectorAll('.s-item').forEach(i => i.classList.remove('s-active'));
    document.getElementById('s-panel-' + name).classList.add('s-active');
    el.classList.add('s-active');
}
function showMsg(panelKey, text, isError) {
    const el = document.getElementById('msg-' + panelKey);
    if (!el) return;
    el.textContent = text;
    el.style.color = isError ? '#c0392b' : '#27ae60';
    el.style.marginTop = '8px';
    el.style.fontSize = '0.85rem';
}