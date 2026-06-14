const passwordInput = document.getElementById('password');
const hideIcon = document.querySelector('.hide-icon');

hideIcon.addEventListener('click', () => {
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        hideIcon.src = 'picture/eye-open.png';
    } else {
        passwordInput.type = 'password';
        hideIcon.src = 'picture/eye-close.png';
    }
});

const password = document.getElementById("password");

const lengthRule = document.getElementById("length");
const uppercaseRule = document.getElementById("uppercase");
const lowercaseRule = document.getElementById("lowercase");
const numberRule = document.getElementById("number");

password.addEventListener("keyup", function () {

    const value = password.value;
    if (value.length >= 8 && value.length <= 25) {
        lengthRule.textContent = "✔ 8–25 characters";
        lengthRule.classList.remove("invalid");
        lengthRule.classList.add("valid");
    } else {
        lengthRule.textContent = "✘ 8–25 characters";
        lengthRule.classList.remove("valid");
        lengthRule.classList.add("invalid");
    }
    if (/[A-Z]/.test(value)) {
        uppercaseRule.textContent = "✔ At least 1 uppercase letter";
        uppercaseRule.classList.remove("invalid");
        uppercaseRule.classList.add("valid");
    } else {
        uppercaseRule.textContent = "✘ At least 1 uppercase letter";
        uppercaseRule.classList.remove("valid");
        uppercaseRule.classList.add("invalid");
    }
    if (/[a-z]/.test(value)) {
        lowercaseRule.textContent = "✔ At least 1 lowercase letter";
        lowercaseRule.classList.remove("invalid");
        lowercaseRule.classList.add("valid");
    } else {
        lowercaseRule.textContent = "✘ At least 1 lowercase letter";
        lowercaseRule.classList.remove("valid");
        lowercaseRule.classList.add("invalid");
    }

    if (/[0-9]/.test(value)) {
        numberRule.textContent = "✔ At least 1 number";
        numberRule.classList.remove("invalid");
        numberRule.classList.add("valid");
    } else {
        numberRule.textContent = "✘ At least 1 number";
        numberRule.classList.remove("valid");
        numberRule.classList.add("invalid");
    }

});

/* ── Terms & Conditions Overlay Logic ── */
const overlay = document.getElementById('tcOverlay');
const tcBody = document.getElementById('tcBody');
const tcAcceptCheck = document.getElementById('tcAcceptCheck');
const tcConfirmBtn = document.getElementById('tcConfirmBtn');
const tcScrollHint = document.getElementById('tcScrollHint');
const registerForm = document.getElementById('registerForm');
const hiddenCheckbox = document.getElementById('Remember');
const registerBtn = document.getElementById('registerBtn');

// ── Open overlay (after basic HTML5 validation passes) ──
function openOverlay() {
    // Trigger native validation first
    if (!registerForm.checkValidity()) {
        registerForm.reportValidity();
        return;
    }
    // Reset overlay state each time it opens
    tcAcceptCheck.checked = false;
    tcConfirmBtn.disabled = true;
    tcConfirmBtn.classList.remove('enabled');
    tcScrollHint.style.display = '';
    tcBody.scrollTop = 0;
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeOverlay() {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Register button → open overlay instead of submitting
registerBtn.addEventListener('click', openOverlay);

// Links inside form also open the overlay
document.getElementById('openTcLink').addEventListener('click', function (e) { e.preventDefault(); openOverlay(); });
document.getElementById('openTcLink2').addEventListener('click', function (e) { e.preventDefault(); openOverlay(); });

// Close buttons
document.getElementById('tcCloseBtn').addEventListener('click', closeOverlay);
document.getElementById('tcCancelBtn').addEventListener('click', closeOverlay);

// Click outside modal to close
overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeOverlay();
});

// Escape key to close
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay.classList.contains('active')) closeOverlay();
});

// Enable confirm button only when checkbox is ticked
tcAcceptCheck.addEventListener('change', function () {
    tcConfirmBtn.disabled = !this.checked;
    tcConfirmBtn.classList.toggle('enabled', this.checked);
});

// Hide scroll hint when user scrolls to bottom of T&C
tcBody.addEventListener('scroll', function () {
    const atBottom = tcBody.scrollTop + tcBody.clientHeight >= tcBody.scrollHeight - 10;
    if (atBottom) tcScrollHint.style.display = 'none';
});

// Confirm → check hidden checkbox, then submit form
tcConfirmBtn.addEventListener('click', function () {
    if (!tcAcceptCheck.checked) return;
    hiddenCheckbox.checked = true;   // satisfy PHP `required` on the hidden field
    closeOverlay();
    registerForm.submit();           // actual POST
});