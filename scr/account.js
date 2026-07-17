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
                const phoneVal = data.value || '';
                document.querySelectorAll('.profile-phone, [data-field="phone"]').forEach(el => el.textContent = phoneVal || '—');
                // Also update profile card phone display
                updatePhoneInProfile(phoneVal);
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

// ── Set phone as default ───────────────────────────────────
async function setDefaultPhone() {
    const phoneInput = document.getElementById('inp-phone');
    const phone = phoneInput.value.trim();
    const msgEl = document.getElementById('msg-phone');
    
    if (!phone) {
        msgEl.textContent = 'Please enter a phone number first.';
        msgEl.style.color = '#c0392b';
        return;
    }
    
    if (!/^09\d{9}$/.test(phone)) {
        msgEl.textContent = 'Phone must be 11 digits starting with 09.';
        msgEl.style.color = '#c0392b';
        return;
    }

    const btn = document.getElementById('phone-default-btn');
    btn.disabled = true;
    btn.textContent = 'Setting...';

    try {
        const fd = new FormData();
        fd.append('field', 'phone');
        fd.append('value', phone);
        fd.append('set_default', '1');

        const res = await fetch('account.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            msgEl.textContent = 'Phone set as default! ✓';
            msgEl.style.color = '#27ae60';
            // Update profile card immediately
            updatePhoneInProfile(phone);
            setTimeout(() => { msgEl.textContent = ''; }, 3000);
        } else {
            msgEl.textContent = data.error || 'Could not set as default.';
            msgEl.style.color = '#c0392b';
        }
    } catch (err) {
        msgEl.textContent = 'Network error. Please try again.';
        msgEl.style.color = '#c0392b';
    }
    
    btn.disabled = false;
    btn.textContent = 'Set as Default';
}

// Update phone display in profile card
function updatePhoneInProfile(phone) {
    const phoneVal = document.getElementById('phone-val');
    if (phoneVal) {
        if (phone) {
            phoneVal.textContent = phone;
            phoneVal.className = '';
        } else {
            phoneVal.textContent = '* Add your phone number';
            phoneVal.className = 'placeholder-text';
        }
    }
}

// ── Avatar choice modal ────────────────────────────────────
function openAvatarModal() {
    document.getElementById('avatarModal').style.display = 'flex';
}
function closeAvatarModal() {
    document.getElementById('avatarModal').style.display = 'none';
}
function triggerFilePicker() {
    closeAvatarModal();
    document.getElementById('avatarFileInput').click();
}
const avatarModal = document.getElementById('avatarModal');
if (avatarModal) {
    avatarModal.addEventListener('click', function (e) {
        if (e.target === this) closeAvatarModal();
    });
}

// ── Camera (getUserMedia) ──────────────────────────────────
let _cameraStream  = null;   // active MediaStream
let _facingMode    = 'user'; // 'user' = front, 'environment' = back
let _capturedBlob  = null;   // captured image as Blob

async function triggerCamera() {
    closeAvatarModal();

    // On mobile, if getUserMedia isn't supported, fall back to capture input
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        document.getElementById('avatarCameraInput').click();
        return;
    }

    _facingMode   = 'user';
    _capturedBlob = null;
    _resetCameraUI();
    document.getElementById('cameraModal').style.display = 'flex';
    await _startCamera();
}

async function _startCamera() {
    const errEl = document.getElementById('cameraError');
    errEl.style.display = 'none';

    // Stop any existing stream first
    _stopCamera();

    try {
        _cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: _facingMode, width: { ideal: 1280 }, height: { ideal: 960 } },
            audio: false
        });
        const video = document.getElementById('cameraStream');
        video.srcObject = _cameraStream;
        video.style.display = 'block';
        document.getElementById('cameraCanvas').style.display = 'none';
        document.getElementById('cameraPlaceholder').style.display = 'none';
    } catch (err) {
        let msg = 'Could not access the camera.';
        if (err.name === 'NotAllowedError')  msg = 'Camera permission denied. Please allow camera access and try again.';
        if (err.name === 'NotFoundError')    msg = 'No camera found on this device.';
        if (err.name === 'NotReadableError') msg = 'Camera is already in use by another application.';
        errEl.textContent    = msg;
        errEl.style.display  = 'block';
        document.getElementById('cameraPlaceholder').style.display = 'flex';
    }
}

function _stopCamera() {
    if (_cameraStream) {
        _cameraStream.getTracks().forEach(t => t.stop());
        _cameraStream = null;
    }
    const video = document.getElementById('cameraStream');
    if (video) video.srcObject = null;
}

function _resetCameraUI() {
    document.getElementById('btnCapture').style.display  = '';
    document.getElementById('btnFlipCamera').style.display = '';
    document.getElementById('btnRetake').style.display   = 'none';
    document.getElementById('btnUsePhoto').style.display = 'none';
    document.getElementById('cameraCanvas').style.display = 'none';
    document.getElementById('cameraStream').style.display = 'block';
    document.getElementById('cameraPlaceholder').style.display = 'flex';
    document.getElementById('cameraError').style.display = 'none';
}

function capturePhoto() {
    const video  = document.getElementById('cameraStream');
    const canvas = document.getElementById('cameraCanvas');
    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

    // Freeze: hide video, show canvas snapshot
    video.style.display  = 'none';
    canvas.style.display = 'block';

    // Convert canvas to Blob for upload
    canvas.toBlob(blob => {
        _capturedBlob = blob;
    }, 'image/jpeg', 0.92);

    // Swap buttons: hide Capture+Flip, show Retake+Use
    document.getElementById('btnCapture').style.display    = 'none';
    document.getElementById('btnFlipCamera').style.display = 'none';
    document.getElementById('btnRetake').style.display     = '';
    document.getElementById('btnUsePhoto').style.display   = '';
}

function retakePhoto() {
    _capturedBlob = null;
    _resetCameraUI();
    _startCamera();
}

function usePhoto() {
    if (!_capturedBlob) return;
    const file = new File([_capturedBlob], 'camera_photo_' + Date.now() + '.jpg', { type: 'image/jpeg' });
    closeCameraModal();
    handleAvatarFile(file);
}

function closeCameraModal() {
    _stopCamera();
    _capturedBlob = null;
    document.getElementById('cameraModal').style.display = 'none';
}

async function flipCamera() {
    _facingMode = _facingMode === 'user' ? 'environment' : 'user';
    _capturedBlob = null;
    _resetCameraUI();
    await _startCamera();
}

// Close camera modal on backdrop click
const cameraModal = document.getElementById('cameraModal');
if (cameraModal) {
    cameraModal.addEventListener('click', function (e) {
        if (e.target === this) closeCameraModal();
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
        expandedView.classList.remove('panel-open');
        document.querySelectorAll('.s-panel').forEach(p => p.classList.remove('s-active'));
        document.getElementById('s-panel-welcome').classList.add('s-active');
        document.querySelectorAll('.s-item').forEach(i => i.classList.remove('s-active'));
        // Hide mobile back button
        const backBtn = document.getElementById('s-mobile-back');
        if (backBtn) backBtn.style.display = 'none';
    }, 420);
}
function showSPanel(name, el) {
    document.querySelectorAll('.s-panel').forEach(p => p.classList.remove('s-active'));
    document.querySelectorAll('.s-item').forEach(i => i.classList.remove('s-active'));
    document.getElementById('s-panel-' + name).classList.add('s-active');
    el.classList.add('s-active');

    // Mobile: switch to panel view (back-nav pattern)
    const expandedView = document.querySelector('.card-expanded-view');
    if (expandedView && window.innerWidth <= 768) {
        expandedView.classList.add('panel-open');
        const backBtn = document.getElementById('s-mobile-back');
        if (backBtn) backBtn.style.display = 'flex';
    }
}

function closeMobilePanel() {
    const expandedView = document.querySelector('.card-expanded-view');
    if (expandedView) expandedView.classList.remove('panel-open');
    document.querySelectorAll('.s-panel').forEach(p => p.classList.remove('s-active'));
    document.querySelectorAll('.s-item').forEach(i => i.classList.remove('s-active'));
    const backBtn = document.getElementById('s-mobile-back');
    if (backBtn) backBtn.style.display = 'none';
    const welcome = document.getElementById('s-panel-welcome');
    if (welcome) welcome.classList.add('s-active');
}
function showMsg(panelKey, text, isError) {
    const el = document.getElementById('msg-' + panelKey);
    if (!el) return;
    el.textContent = text;
    el.style.color = isError ? '#c0392b' : '#27ae60';
    el.style.marginTop = '8px';
    el.style.fontSize = '0.85rem';
}

// ── Address book (Saved Addresses panel) ───────────────────
// Same `addresses` table + addresses_api.php used by checkout.php,
// so anything added/edited/deleted here is reflected at checkout too.
let addressBook = Array.isArray(typeof ADDRESS_BOOK_INITIAL !== 'undefined' ? ADDRESS_BOOK_INITIAL : null)
    ? ADDRESS_BOOK_INITIAL : [];
let editingAddressId = null; // null = add mode, otherwise the id being edited
const ADDRESS_API = '../api/addresses_api.php';
const ADDRESS_MODAL_FIELDS = ['addrLabel', 'addrRecipient', 'addrStreet', 'addrBarangay', 'addrCity', 'addrProvince', 'addrZip'];

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

function formatAddressLine(a) {
    return [a.street_address, a.barangay, a.city, a.province, a.zip_code]
        .filter(Boolean).map(escapeHtml).join(', ');
}

// ── Initialize and update profile with default address ────
function initializeDefaultAddressDisplay() {
    const defaultAddr = addressBook.find(a => Number(a.is_default) === 1);
    const addressVal = document.getElementById('address-val');
    if (addressVal) {
        if (defaultAddr) {
            const formatted = formatAddressLine(defaultAddr);
            addressVal.textContent = formatted;
            addressVal.className = '';
        } else {
            addressVal.textContent = '* Set default address in settings';
            addressVal.className = 'placeholder-text';
        }
    }
}

// Call on page load to set initial default address display
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDefaultAddressDisplay);
} else {
    initializeDefaultAddressDisplay();
}

// Update the profile card when default address changes
function updateProfileDefaultAddress() {
    initializeDefaultAddressDisplay();
}

function renderAddressBook() {
    const list = document.getElementById('addressBookList');
    if (!list) return;

    if (!addressBook.length) {
        list.innerHTML = '<p class="addr-book-empty">No saved addresses yet. Add one so it\u2019s ready at checkout.</p>';
        return;
    }

    list.innerHTML = addressBook.map(a => `
        <div class="addr-book-card">
            <div class="addr-book-card-top">
                <span class="addr-book-label">${escapeHtml(a.label) || 'Address'}</span>
                ${Number(a.is_default) === 1 ? '<span class="addr-book-default-badge">Default</span>' : ''}
            </div>
            <div class="addr-book-recipient">${escapeHtml(a.recipient_name)}</div>
            <div class="addr-book-line">${formatAddressLine(a)}</div>
            <div class="addr-book-actions">
                ${Number(a.is_default) !== 1 ? `<button type="button" class="addr-book-btn" onclick="setDefaultAddress(${a.id})">Set Default</button>` : ''}
                <button type="button" class="addr-book-btn" onclick="openEditAddressModal(${a.id})">Edit</button>
                <button type="button" class="addr-book-btn addr-book-btn-danger" onclick="deleteAddressBookEntry(${a.id})">Delete</button>
            </div>
        </div>
    `).join('');
}

function openAddressBookModal() {
    editingAddressId = null;
    document.getElementById('addrModalTitle').textContent = 'Add New Address';
    document.getElementById('addrModalSub').textContent = 'Fill in your details below to add a new delivery address.';
    document.getElementById('addrSaveBtn').textContent = 'Save Address';
    ADDRESS_MODAL_FIELDS.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    document.getElementById('addrIsDefault').checked = false;
    document.getElementById('addrModalMsg').textContent = '';
    document.getElementById('addressModalOverlay').style.display = 'flex';
}

function openEditAddressModal(id) {
    const a = addressBook.find(x => Number(x.id) === Number(id));
    if (!a) return;
    editingAddressId = id;
    document.getElementById('addrModalTitle').textContent = 'Edit Address';
    document.getElementById('addrModalSub').textContent = 'Update your delivery details below.';
    document.getElementById('addrSaveBtn').textContent = 'Save Changes';
    document.getElementById('addrLabel').value       = a.label || '';
    document.getElementById('addrRecipient').value   = a.recipient_name || '';
    document.getElementById('addrStreet').value      = a.street_address || '';
    document.getElementById('addrBarangay').value    = a.barangay || '';
    document.getElementById('addrCity').value        = a.city || '';
    document.getElementById('addrProvince').value    = a.province || '';
    document.getElementById('addrZip').value         = a.zip_code || '';
    document.getElementById('addrIsDefault').checked = Number(a.is_default) === 1;
    document.getElementById('addrModalMsg').textContent = '';
    document.getElementById('addressModalOverlay').style.display = 'flex';
}

function closeAddressBookModal() {
    document.getElementById('addressModalOverlay').style.display = 'none';
    editingAddressId = null;
}

async function saveAddressBookEntry() {
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

    const saveBtn  = document.getElementById('addrSaveBtn');
    const isEditing = !!editingAddressId;
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving\u2026';

    const payload = {
        action: isEditing ? 'edit' : 'add',
        label,
        recipient_name: recipient,
        street_address: street,
        barangay,
        city,
        province,
        zip_code: zip,
        is_default: isDefault
    };
    if (isEditing) {
        payload.id = editingAddressId;
        const existing = addressBook.find(a => Number(a.id) === Number(editingAddressId));
        payload.phone = existing ? existing.phone || '' : '';
    } else {
        payload.phone = '';
    }

    try {
        const res = await fetch(ADDRESS_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            if (data.address.is_default) {
                addressBook.forEach(a => a.is_default = 0);
            }
            if (isEditing) {
                const idx = addressBook.findIndex(a => Number(a.id) === Number(editingAddressId));
                if (idx !== -1) addressBook[idx] = data.address;
            } else {
                addressBook.unshift(data.address);
            }
            renderAddressBook();
            // Update profile card if this address is now default
            if (data.address.is_default) {
                updateProfileDefaultAddress();
            }
            closeAddressBookModal();
        } else {
            if (msg) msg.textContent = data.error || 'Could not save address.';
        }
    } catch (err) {
        if (msg) msg.textContent = 'Network error. Please try again.';
    }

    saveBtn.disabled = false;
    saveBtn.textContent = isEditing ? 'Save Changes' : 'Save Address';
}

async function setDefaultAddress(id) {
    const a = addressBook.find(x => Number(x.id) === Number(id));
    if (!a) return;

    try {
        const res = await fetch(ADDRESS_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'edit', id: a.id, label: a.label, recipient_name: a.recipient_name,
                phone: a.phone, street_address: a.street_address, barangay: a.barangay,
                city: a.city, province: a.province, zip_code: a.zip_code, is_default: true
            })
        });
        const data = await res.json();
        if (data.success) {
            addressBook.forEach(x => x.is_default = 0);
            const idx = addressBook.findIndex(x => Number(x.id) === Number(id));
            if (idx !== -1) addressBook[idx] = data.address;
            renderAddressBook();
            // Update profile card with new default address
            updateProfileDefaultAddress();
        } else {
            showMsg('address', data.error || 'Could not set default.', true);
        }
    } catch (err) {
        showMsg('address', 'Network error. Please try again.', true);
    }
}

async function deleteAddressBookEntry(id) {
    if (!confirm('Delete this saved address?')) return;

    try {
        const res = await fetch(ADDRESS_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.success) {
            addressBook = addressBook.filter(a => Number(a.id) !== Number(id));
            if (data.new_default_id) {
                addressBook.forEach(a => { a.is_default = Number(a.id) === Number(data.new_default_id) ? 1 : 0; });
            }
            renderAddressBook();
            // Update profile card in case deleted address was the default
            updateProfileDefaultAddress();
        } else {
            showMsg('address', data.error || 'Could not delete address.', true);
        }
    } catch (err) {
        showMsg('address', 'Network error. Please try again.', true);
    }
}

// Close the modal when clicking the overlay itself (not the modal card)
document.addEventListener('click', function (e) {
    if (e.target && e.target.id === 'addressModalOverlay') closeAddressBookModal();
});

renderAddressBook();

// ── Save password ──────────────────────────────────────────
async function savePassword() {
    const current  = document.getElementById('inp-current-password').value;
    const newPw    = document.getElementById('inp-new-password').value;
    const confirm  = document.getElementById('inp-confirm-password').value;
    const msgEl    = document.getElementById('msg-password');

    msgEl.textContent = '';

    if (!current || !newPw || !confirm) {
        msgEl.textContent = 'All password fields are required.';
        msgEl.style.color = '#c0392b';
        return;
    }
    if (newPw !== confirm) {
        msgEl.textContent = 'Passwords do not match.';
        msgEl.style.color = '#c0392b';
        return;
    }
    if (
        newPw.length < 8 || newPw.length > 25 ||
        !/[A-Z]/.test(newPw) ||
        !/[a-z]/.test(newPw) ||
        !/[0-9]/.test(newPw)
    ) {
        msgEl.textContent = 'Password does not meet the requirements.';
        msgEl.style.color = '#c0392b';
        return;
    }

    const fd = new FormData();
    fd.append('field',            'password');
    fd.append('current_password', current);
    fd.append('new_password',     newPw);
    fd.append('confirm_password', confirm);

    try {
        const res  = await fetch('account.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            msgEl.textContent = data.message || 'Password changed successfully!';
            msgEl.style.color = '#27ae60';
            document.getElementById('inp-current-password').value = '';
            document.getElementById('inp-new-password').value     = '';
            document.getElementById('inp-confirm-password').value = '';
            // Hide rules panel after success
            const rulesPanel = document.getElementById('password-rules-panel');
            if (rulesPanel) rulesPanel.style.display = 'none';
        } else {
            msgEl.textContent = data.error || 'Failed to update password.';
            msgEl.style.color = '#c0392b';
        }
    } catch (err) {
        msgEl.textContent = 'Network error. Please try again.';
        msgEl.style.color = '#c0392b';
    }
}

// ── Save email (two-step OTP flow) ─────────────────────────
async function saveEmail() {
    const newEmail = document.getElementById('inp-new-email').value.trim();
    const msgEl    = document.getElementById('msg-email');

    msgEl.textContent = '';

    if (!newEmail) {
        msgEl.textContent = 'Email cannot be empty.';
        msgEl.style.color = '#c0392b';
        return;
    }

    const btn = document.querySelector('#s-panel-email .s-save-btn');
    if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }

    const fd = new FormData();
    fd.append('field',     'email_send_otp');
    fd.append('new_email', newEmail);

    try {
        const res  = await fetch('account.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            msgEl.textContent = data.message;
            msgEl.style.color = '#27ae60';
            // Show OTP verification step
            showEmailOtpStep(newEmail);
        } else {
            msgEl.textContent = data.error || 'Failed to send OTP.';
            msgEl.style.color = '#c0392b';
        }
    } catch (err) {
        msgEl.textContent = 'Network error. Please try again.';
        msgEl.style.color = '#c0392b';
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = 'Send OTP'; }
    }
}

function showEmailOtpStep(newEmail) {
    const panel = document.getElementById('s-panel-email');
    if (!panel) return;

    // Prevent duplicate injection
    if (panel.querySelector('#email-otp-step')) return;

    const step = document.createElement('div');
    step.id = 'email-otp-step';
    step.style.cssText = 'margin-top:16px;';
    step.innerHTML = `
        <p style="font-size:.85rem;color:#555;margin-bottom:10px;">
            Enter the 6-digit code sent to <strong>${newEmail}</strong>
        </p>
        <div style="display:flex;gap:6px;margin-bottom:10px;" id="email-otp-digits">
            ${[1,2,3,4,5,6].map(i =>
                `<input type="text" maxlength="1" inputmode="numeric"
                    id="email-otp-d${i}"
                    style="width:38px;height:42px;text-align:center;font-size:1.1rem;
                           border:1.5px solid #ccc;border-radius:8px;outline:none;">`
            ).join('')}
        </div>
        <div id="msg-email-otp" style="font-size:.85rem;min-height:18px;margin-bottom:8px;"></div>
        <div style="display:flex;gap:10px;align-items:center;">
            <button id="btn-verify-email-otp" class="s-save-btn"
                onclick="verifyEmailOtp()" style="margin:0;">Verify & Save</button>
            <span id="email-otp-resend-wrap" style="font-size:.85rem;">
                <span id="email-otp-resend-link" class="resend-link"
                    style="color:#6F4E37;cursor:pointer;text-decoration:underline;"
                    onclick="resendEmailOtp()">Resend OTP</span>
                <span id="email-otp-countdown" style="display:none;color:#6F4E37;font-size:.85rem;"></span>
            </span>
        </div>
    `;

    // Insert before the existing msg-email div
    const msgEl = document.getElementById('msg-email');
    panel.insertBefore(step, msgEl);

    // Wire up auto-focus/auto-advance on the 6 digit inputs
    const digits = step.querySelectorAll('input[type="text"]');
    digits.forEach((inp, i) => {
        inp.addEventListener('input', () => {
            inp.value = inp.value.replace(/\D/g, '');
            if (inp.value && i < digits.length - 1) digits[i + 1].focus();
        });
        inp.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !inp.value && i > 0) digits[i - 1].focus();
        });
        inp.addEventListener('paste', e => {
            e.preventDefault();
            const p = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
            [...p].forEach((ch, j) => { if (digits[j]) digits[j].value = ch; });
            // Focus last filled digit
            const last = Math.min(p.length, digits.length) - 1;
            if (last >= 0) digits[last].focus();
        });
    });

    // Start 60s countdown so user knows when they can resend
    startEmailOtpCountdown();

    digits[0].focus();
}

function startEmailOtpCountdown() {
    const link      = document.getElementById('email-otp-resend-link');
    const countdown = document.getElementById('email-otp-countdown');
    if (!link || !countdown) return;

    let remaining = 60;
    link.style.display = 'none';
    countdown.style.display = 'inline';
    countdown.textContent   = `Resend in ${remaining}s`;

    const timer = setInterval(() => {
        remaining--;
        if (remaining <= 0) {
            clearInterval(timer);
            countdown.style.display = 'none';
            link.style.display      = 'inline';
        } else {
            countdown.textContent = `Resend in ${remaining}s`;
        }
    }, 1000);
}

async function resendEmailOtp() {
    const newEmailInput = document.getElementById('inp-new-email');
    const newEmail      = newEmailInput ? newEmailInput.value.trim() : '';
    const msgEl         = document.getElementById('msg-email-otp');

    if (!newEmail) return;

    const fd = new FormData();
    fd.append('field',     'email_send_otp');
    fd.append('new_email', newEmail);

    try {
        const res  = await fetch('account.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            if (msgEl) { msgEl.textContent = 'New OTP sent!'; msgEl.style.color = '#27ae60'; }
            startEmailOtpCountdown();
        } else {
            if (msgEl) { msgEl.textContent = data.error || 'Could not resend.'; msgEl.style.color = '#c0392b'; }
        }
    } catch (err) {
        if (msgEl) { msgEl.textContent = 'Network error.'; msgEl.style.color = '#c0392b'; }
    }
}

async function verifyEmailOtp() {
    const digits = document.querySelectorAll('#email-otp-digits input');
    let otp = '';
    digits.forEach(d => otp += d.value);

    const msgEl = document.getElementById('msg-email-otp');
    msgEl.textContent = '';

    if (otp.length !== 6) {
        msgEl.textContent = 'Please enter all 6 digits.';
        msgEl.style.color = '#c0392b';
        return;
    }

    const btn = document.getElementById('btn-verify-email-otp');
    if (btn) { btn.disabled = true; btn.textContent = 'Verifying…'; }

    const fd = new FormData();
    fd.append('field', 'email_verify_otp');
    fd.append('otp',   otp);

    try {
        const res  = await fetch('account.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            // Update every place the email is displayed on the page
            const newEmail = data.new_email;
            document.querySelectorAll('.sidebar-user-email, [data-field="email"]').forEach(el => el.textContent = newEmail);

            // Show success in the main panel message area
            const panelMsg = document.getElementById('msg-email');
            if (panelMsg) { panelMsg.textContent = data.message || 'Email updated successfully!'; panelMsg.style.color = '#27ae60'; }

            // Remove the OTP step
            const step = document.getElementById('email-otp-step');
            if (step) step.remove();

            // Clear the email input
            const inp = document.getElementById('inp-new-email');
            if (inp) inp.value = '';

            // Reset the Send OTP button label
            const sendBtn = document.querySelector('#s-panel-email .s-save-btn');
            if (sendBtn) sendBtn.textContent = 'Re-send OTP';
        } else {
            msgEl.textContent = data.error || 'Verification failed.';
            msgEl.style.color = '#c0392b';
            if (btn) { btn.disabled = false; btn.textContent = 'Verify & Save'; }
        }
    } catch (err) {
        msgEl.textContent = 'Network error. Please try again.';
        msgEl.style.color = '#c0392b';
        if (btn) { btn.disabled = false; btn.textContent = 'Verify & Save'; }
    }
}

// ── Password eye toggle ────────────────────────────────────
document.addEventListener('click', function (e) {
    const eye = e.target.closest('.password-eye');
    if (!eye) return;
    const inputId = eye.getAttribute('data-input');
    const input   = document.getElementById(inputId);
    if (!input) return;
    const isVisible = input.type === 'text';
    input.type      = isVisible ? 'password' : 'text';
    eye.src         = isVisible ? '../picture/eye-close.png' : '../picture/eye-open.png';
});

// ── Live password rules validation ────────────────────────
(function () {
    const newPwInput = document.getElementById('inp-new-password');
    if (!newPwInput) return;

    const rulesPanel  = document.getElementById('password-rules-panel');
    const rLength     = document.getElementById('s-length');
    const rUppercase  = document.getElementById('s-uppercase');
    const rLowercase  = document.getElementById('s-lowercase');
    const rNumber     = document.getElementById('s-number');

    function setRule(el, valid) {
        if (!el) return;
        el.className = valid ? 'valid' : 'invalid';
        el.textContent = (valid ? '✔' : '✘') + el.textContent.slice(1);
    }

    newPwInput.addEventListener('focus', function () {
        if (rulesPanel) rulesPanel.style.display = 'block';
    });

    newPwInput.addEventListener('input', function () {
        const v = this.value;
        setRule(rLength,    v.length >= 8 && v.length <= 25);
        setRule(rUppercase, /[A-Z]/.test(v));
        setRule(rLowercase, /[a-z]/.test(v));
        setRule(rNumber,    /[0-9]/.test(v));
    });

    newPwInput.addEventListener('blur', function () {
        if (this.value === '' && rulesPanel) rulesPanel.style.display = 'none';
    });
})();

// ── QR Modal ──────────────────────────────────────────────
function openQRModal() {
    const overlay = document.getElementById('qrModalOverlay');
    if (overlay) overlay.classList.add('open');
}

function closeQRModal(e) {
    if (e.target === document.getElementById('qrModalOverlay')) {
        closeQRModalDirect();
    }
}

function closeQRModalDirect() {
    document.getElementById('qrModalOverlay').classList.remove('open');
}

// Close QR modal with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeQRModalDirect();
});