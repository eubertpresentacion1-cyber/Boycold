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