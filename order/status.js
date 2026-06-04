async function handleAvatarFile(file) {
    if (!file) return;

    const avatarMsg = document.getElementById('avatar-msg');
    const profileImg = document.getElementById('profileAvatarImg');
    const sidebarImg = document.getElementById('sidebarAvatarImg');
    const navImg = document.getElementById('navAvatarImg');
    const sidebarIcon = document.getElementById('sidebarAvatarIcon');
    const navIcon = document.getElementById('navAvatarIcon');

    // Instant local preview
    const localURL = URL.createObjectURL(file);
    if (profileImg) {
        profileImg.src = localURL;
        profileImg.style.cssText = 'position:absolute;inset:0;width:110px;height:110px;object-fit:cover;border-radius:50%;display:block;';
    }
    if (sidebarImg) {
        sidebarImg.src = localURL;
        sidebarImg.style.display = '';
    }
    if (navImg) {
        navImg.src = localURL;
        navImg.style.display = 'block';
    }
    if (navIcon) {
        navIcon.style.display = 'none';
    }
    if (sidebarIcon) {
        sidebarIcon.style.display = 'none';
    }

    avatarMsg.style.color = '#888';
    avatarMsg.textContent = 'Uploading…';

    const fd = new FormData();
    fd.append('avatar', file);

    try {
        const res = await fetch('uploadavatar.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            const newSrc = data.path + '?v=' + Date.now();
            if (profileImg) profileImg.src = newSrc;
            if (sidebarImg) sidebarImg.src = newSrc;
            if (navImg) navImg.src = newSrc;
            if (navIcon) navIcon.style.display = 'none';
            avatarMsg.style.color = '#27ae60';
            // Show the success message from the server (database update confirmation)
            avatarMsg.textContent = data.message || 'Photo updated!';
            setTimeout(() => {
                avatarMsg.textContent = '';
            }, 3000);
        } else {
            avatarMsg.style.color = '#c0392b';
            avatarMsg.textContent = data.error || 'Upload failed.';
        }
    } catch (err) {
        avatarMsg.style.color = '#c0392b';
        avatarMsg.textContent = 'Network error. Try again.';
    }

    URL.revokeObjectURL(localURL);
    document.getElementById('avatarFileInput').value = '';
    document.getElementById('avatarCameraInput').value = '';
}

const avatarFileInput = document.getElementById('avatarFileInput');
const avatarCameraInput = document.getElementById('avatarCameraInput');
if (avatarFileInput) avatarFileInput.addEventListener('change', function () {
    handleAvatarFile(this.files[0]);
});
if (avatarCameraInput) avatarCameraInput.addEventListener('change', function () {
    handleAvatarFile(this.files[0]);
});

// ── Avatar hover overlay ───────────────────────────────────
const avatarWrap = document.getElementById('profileAvatarWrap');
const avatarOverlay = document.getElementById('avatarOverlay');
if (avatarWrap && avatarOverlay) {
    avatarWrap.addEventListener('mouseenter', () => avatarOverlay.style.opacity = '1');
    avatarWrap.addEventListener('mouseleave', () => avatarOverlay.style.opacity = '0');
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

function toggleSearch() {
    const search = document.getElementById('navSearch');
    const btn = document.getElementById('searchIconBtn');
    const isOpen = search.classList.toggle('open');
    btn.classList.toggle('active', isOpen);
    if (isOpen) {
        setTimeout(() => search.querySelector('input').focus(), 420);
    } else {
        search.querySelector('input').value = '';
    }
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
// Category filter active state
document.querySelectorAll('.box ul li a').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelectorAll('.box ul li a').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
    });
});

// Heart toggle
document.querySelectorAll('.card-heart').forEach(btn => {
    btn.addEventListener('click', function () {
        const icon = this.querySelector('i');
        const isLiked = icon.style.color === 'rgb(229, 57, 53)';
        if (isLiked) {
            icon.style.color = 'transparent';
            icon.style.webkitTextStroke = '1.5px #e53935';
        } else {
            icon.style.color = '#e53935';
            icon.style.webkitTextStroke = '0';
        }
    });
});

/* ── Nav Sidebar ── */
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

/* ── Rider Modal ── */
function openRiderModal() {
    document.getElementById('riderModal').classList.add('open');
}

function closeRiderModal(e) {
    if (e.target === document.getElementById('riderModal')) closeRiderModalDirect();
}

function closeRiderModalDirect() {
    document.getElementById('riderModal').classList.remove('open');
}

/* ── Report Modal ── */
function openReportModal() {
    document.getElementById('reportModal').classList.add('open');
}

function closeReportModal(e) {
    if (e.target === document.getElementById('reportModal')) closeReportModalDirect();
}

function closeReportModalDirect() {
    closeCamera(); // ← add this line at the top
    document.getElementById('reportModal').classList.remove('open');
    document.getElementById('selectDisplay').textContent = 'Select an issue';
    document.getElementById('selectDisplay').style.color = '#aaa';
    document.getElementById('selectWrapper').querySelectorAll('.dropdown-option').forEach(o => o.classList.remove('selected'));
    document.getElementById('reportTextarea').value = '';
    document.getElementById('charCount').textContent = '0/500';
    document.getElementById('previewList').innerHTML = '';
    document.getElementById('attachPopup').classList.remove('open');
    document.getElementById('selectWrapper').classList.remove('open');
}

/* ── Custom dropdown ── */
function toggleDropdown() {
    document.getElementById('selectWrapper').classList.toggle('open');
}

function selectIssue(value) {
    const display = document.getElementById('selectDisplay');
    display.textContent = value;
    display.style.color = '#1e1e1e';
    document.getElementById('selectWrapper').classList.remove('open');
    document.getElementById('customDropdown').querySelectorAll('.dropdown-option').forEach(o => {
        o.classList.toggle('selected', o.textContent === value);
    });
}

document.addEventListener('click', function (e) {
    const wrap = document.getElementById('selectWrapper');
    if (wrap && !wrap.contains(e.target)) wrap.classList.remove('open');
});

/* ── Char count ── */
function updateCharCount(el) {
    document.getElementById('charCount').textContent = el.value.length + '/500';
}

/* ── Attach popup ── */
function toggleAttachPopup(e) {
    e.stopPropagation();
    document.getElementById('attachPopup').classList.toggle('open');
}

document.addEventListener('click', function (e) {
    const p = document.getElementById('attachPopup');
    if (p && !p.parentElement.contains(e.target)) p.classList.remove('open');
});

function triggerCamera() {
    document.getElementById('attachPopup').classList.remove('open');
    document.getElementById('cameraInput').click();
}

function triggerUpload() {
    document.getElementById('attachPopup').classList.remove('open');
    document.getElementById('uploadInput').click();
}

function handleFileSelect(input) {
    const list = document.getElementById('previewList');
    Array.from(input.files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const thumb = document.createElement('div');
            thumb.className = 'preview-thumb';
            thumb.innerHTML = `
                        <img src="${e.target.result}" alt="attachment">
                        <button class="preview-remove" onclick="this.parentElement.remove()" title="Remove">&times;</button>
                    `;
            list.appendChild(thumb);
        };
        reader.readAsDataURL(file);
    });
    input.value = '';
}

/* ── Submit report ── */
function submitReport() {
    const issue = document.getElementById('selectDisplay').textContent;
    if (issue === 'Select an issue') {
        alert('Please select an issue first.');
        return;
    }
    alert('Report submitted! We will look into this shortly.');
    closeReportModalDirect();
}
/* ── Camera (getUserMedia) ── */
let cameraStream = null;

async function triggerCamera() {
    document.getElementById('attachPopup').classList.remove('open');

    const overlay = document.getElementById('cameraModal');
    const video = document.getElementById('cameraVideo');
    const errEl = document.getElementById('cameraError');
    const capBtn = document.getElementById('captureBtn');

    errEl.style.display = 'none';
    capBtn.style.display = 'flex';
    overlay.classList.add('open');

    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: {
                    ideal: 1280
                },
                height: {
                    ideal: 720
                }
            },
            audio: false
        });
        video.srcObject = cameraStream;
    } catch (err) {
        // Fallback: try any camera if rear-facing fails
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });
            video.srcObject = cameraStream;
        } catch (err2) {
            errEl.textContent = 'Camera access denied or not available. Please allow camera permission and try again.';
            errEl.style.display = 'block';
            capBtn.style.display = 'none';
        }
    }
}

function capturePhoto() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('cameraCanvas');

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const dataURL = canvas.toDataURL('image/jpeg', 0.9);
    addPreviewThumb(dataURL);

    closeCamera();
}

function closeCamera() {
    document.getElementById('cameraModal').classList.remove('open');
    if (cameraStream) {
        cameraStream.getTracks().forEach(t => t.stop());
        cameraStream = null;
    }
    document.getElementById('cameraVideo').srcObject = null;
}

/* ── Upload ── */
function triggerUpload() {
    document.getElementById('attachPopup').classList.remove('open');
    document.getElementById('uploadInput').click();
}

function handleFileSelect(input) {
    Array.from(input.files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => addPreviewThumb(e.target.result);
        reader.readAsDataURL(file);
    });
    input.value = '';
}

function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightboxOverlay').classList.add('open');
}

function closeLightbox() {
    document.getElementById('lightboxOverlay').classList.remove('open');
    document.getElementById('lightboxImg').src = '';
}

/* ── Updated shared preview helper ── */
function addPreviewThumb(src) {
    const list = document.getElementById('previewList');
    const thumb = document.createElement('div');
    thumb.className = 'preview-thumb';
    thumb.innerHTML = `
                <img src="${src}" alt="attachment" onclick="openLightbox('${src}')" style="cursor:zoom-in;">
                <button class="preview-remove" onclick="this.parentElement.remove()" title="Remove">&times;</button>
            `;
    list.appendChild(thumb);
}