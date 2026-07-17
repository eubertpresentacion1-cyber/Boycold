<?php
session_name('POS_SESSION');
session_start();
require_once __DIR__ . '/../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'check_email') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'errors' => []];

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $branch   = trim($_POST['branch'] ?? '');

    // Server-side validation (mirrors the client-side rules)
    if ($email === '') {
        $response['errors']['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = 'Enter a valid email address.';
    }

    if ($password === '') {
        $response['errors']['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $response['errors']['password'] = 'Password must be at least 8 characters.';
    }

    if ($branch === '' || !is_numeric($branch)) {
        $response['errors']['branch'] = 'Please choose a branch.';
    }

    if (empty($response['errors'])) {
        $stmt = $connect->prepare('SELECT id FROM employees WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $response['errors']['email'] = 'This email is already registered.';
        } else {
            // Stage the account — pin.php reads this to finish creating the employee
            $_SESSION['employee_signup'] = [
                'email'    => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'branch_id'   => (int) $branch,
            ];
            $response['success'] = true;
        }
        $stmt->close();
    }

    echo json_encode($response);
    exit;
}

// Fetch available branches for the signup form
$branchStmt = $connect->prepare("SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name");
$branchStmt->execute();
$branches = $branchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$branchStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="auth-css/signup.css">
    <link rel="icon" href="/img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>BoyCold Cafe</title>
</head>
<body>
    <nav>
        <div class="nav-logo">
            <img src="/img/BoyCold Logo 2.png" alt="BoyCold Cafe Logo" class="logo">
        </div>
    </nav>

    <section class="signup-container">

        <!-- ───────────────── STEP 1: Create Account ───────────────── -->
        <div class="form-step active" id="step1">
            <h1>Create Account</h1>
            <p>Fill the details below to get started</p>

            <form id="signupForm" novalidate>

                <!-- Email -->
                <div class="email-group">
                    <div class="input-field">
                        <div class="field-icon">
                            <svg width="22" height="18" viewBox="0 0 37 30" fill="none" stroke="#1e1e1e" xmlns="http://www.w3.org/2000/svg">
                                <path d="M36.6667 3.66667C36.6667 1.65 35.0167 0 33 0H3.66667C1.65 0 0 1.65 0 3.66667V25.6667C0 27.6833 1.65 29.3333 3.66667 29.3333H33C35.0167 29.3333 36.6667 27.6833 36.6667 25.6667V3.66667ZM33 3.66667L18.3333 12.8333L3.66667 3.66667H33ZM33 25.6667H3.66667V7.33333L18.3333 16.5L33 7.33333V25.6667Z" fill="#888"/>
                            </svg>
                        </div>
                        <input id="email" type="email" placeholder=" " required>
                        <label class="email-label" for="email">Enter your email</label>
                    </div>
                    <span class="error" id="emailError"></span>
                </div>

                <!-- Password -->
                <div class="pass-group">
                    <div class="input-field">
                        <div class="field-icon">
                            <svg width="18" height="24" viewBox="0 0 30 40" fill="none" stroke="#1e1e1e" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 30C14.0054 30 13.0516 29.6049 12.3483 28.9016C11.6451 28.1984 11.25 27.2446 11.25 26.25C11.25 24.1688 12.9187 22.5 15 22.5C15.9946 22.5 16.9484 22.8951 17.6516 23.5984C18.3549 24.3016 18.75 25.2554 18.75 26.25C18.75 27.2446 18.3549 28.1984 17.6516 28.9016C16.9484 29.6049 15.9946 30 15 30ZM26.25 35.625V16.875H3.75V35.625H26.25ZM26.25 13.125C27.2446 13.125 28.1984 13.5201 28.9016 14.2234C29.6049 14.9266 30 15.8804 30 16.875V35.625C30 36.6196 29.6049 37.5734 28.9016 38.2766C28.1984 38.9799 27.2446 39.375 26.25 39.375H3.75C2.75544 39.375 1.80161 38.9799 1.09835 38.2766C0.395088 37.5734 0 36.6196 0 35.625V16.875C0 14.7937 1.66875 13.125 3.75 13.125H5.625V9.375C5.625 6.8886 6.61272 4.50403 8.37087 2.74587C10.129 0.98772 12.5136 0 15 0C16.2311 0 17.4502 0.242492 18.5877 0.713629C19.7251 1.18477 20.7586 1.87532 21.6291 2.74587C22.4997 3.61642 23.1902 4.64992 23.6614 5.78734C24.1325 6.92477 24.375 8.14386 24.375 9.375V13.125H26.25ZM15 3.75C13.5082 3.75 12.0774 4.34263 11.0225 5.39752C9.96763 6.45242 9.375 7.88316 9.375 9.375V13.125H20.625V9.375C20.625 7.88316 20.0324 6.45242 18.9775 5.39752C17.9226 4.34263 16.4918 3.75 15 3.75Z" fill="#888"/>
                            </svg>
                        </div>
                        <input id="password" type="password" placeholder=" " minlength="8" required>
                        <label class="pass-label" for="password">Enter your password</label>
                        <span class="pass-toggle" data-target="password"><i class="fa-regular fa-eye"></i></span>
                    </div>
                    <span class="error" id="passwordError"></span>
                </div>

                <!-- Branch -->
                <div class="branch-group">
                    <div class="select-wrapper">
                        <div class="field-icon">
                            <svg width="20" height="20" viewBox="0 0 33 33" fill="none" stroke="#1e1e1e" xmlns="http://www.w3.org/2000/svg">
                                <path d="M31.9 30.4617H29.7V12.6933C29.7 12.0201 29.4682 11.3745 29.0556 10.8984C28.6431 10.4224 28.0835 10.155 27.5 10.155H18.7V2.53998C18.7003 2.08033 18.5923 1.62922 18.3877 1.2348C18.1832 0.84039 17.8896 0.517474 17.5384 0.300523C17.1871 0.0835727 16.7915 -0.0192692 16.3935 0.0029744C15.9956 0.025218 15.6104 0.171712 15.279 0.426821L4.279 8.88582C3.97724 9.11811 3.72995 9.43289 3.55914 9.80213C3.38833 10.1714 3.29931 10.5836 3.3 11.0022V30.4617H1.1C0.808262 30.4617 0.528472 30.5954 0.322183 30.8334C0.115892 31.0714 0 31.3942 0 31.7308C0 32.0674 0.115892 32.3903 0.322183 32.6283C0.528472 32.8663 0.808262 33 1.1 33H31.9C32.1917 33 32.4715 32.8663 32.6778 32.6283C32.8841 32.3903 33 32.0674 33 31.7308C33 31.3942 32.8841 31.0714 32.6778 30.8334C32.4715 30.5954 32.1917 30.4617 31.9 30.4617ZM27.5 12.6933V30.4617H18.7V12.6933H27.5ZM5.5 11.0022L16.5 2.53998V30.4617H5.5V11.0022ZM14.3 15.2317V17.77C14.3 18.1066 14.1841 18.4294 13.9778 18.6674C13.7715 18.9054 13.4917 19.0392 13.2 19.0392C12.9083 19.0392 12.6285 18.9054 12.4222 18.6674C12.2159 18.4294 12.1 18.1066 12.1 17.77V15.2317C12.1 14.8951 12.2159 14.5722 12.4222 14.3342C12.6285 14.0962 12.9083 13.9625 13.2 13.9625C13.4917 13.9625 13.7715 14.0962 13.9778 14.3342C14.1841 14.5722 14.3 14.8951 14.3 15.2317ZM9.9 15.2317V17.77C9.9 18.1066 9.78411 18.4294 9.57782 18.6674C9.37153 18.9054 9.09174 19.0392 8.8 19.0392C8.50826 19.0392 8.22847 18.9054 8.02218 18.6674C7.81589 18.4294 7.7 18.1066 7.7 17.77V15.2317C7.7 14.8951 7.81589 14.5722 8.02218 14.3342C8.22847 14.0962 8.50826 13.9625 8.8 13.9625C9.09174 13.9625 9.37153 14.0962 9.57782 14.3342C9.78411 14.5722 9.9 14.8951 9.9 15.2317ZM9.9 24.1158V26.6542C9.9 26.9908 9.78411 27.3136 9.57782 27.5516C9.37153 27.7896 9.09174 27.9233 8.8 27.9233C8.50826 27.9233 8.22847 27.7896 8.02218 27.5516C7.81589 27.3136 7.7 26.9908 7.7 26.6542V24.1158C7.7 23.7792 7.81589 23.4564 8.02218 23.2184C8.22847 22.9804 8.50826 22.8467 8.8 22.8467C9.09174 22.8467 9.37153 22.9804 9.57782 23.2184C9.78411 23.4564 9.9 23.7792 9.9 24.1158ZM14.3 24.1158V26.6542C14.3 26.9908 14.1841 27.3136 13.9778 27.5516C13.7715 27.7896 13.4917 27.9233 13.2 27.9233C12.9083 27.9233 12.6285 27.7896 12.4222 27.5516C12.2159 27.3136 12.1 26.9908 12.1 26.6542V24.1158C12.1 23.7792 12.2159 23.4564 12.4222 23.2184C12.6285 22.9804 12.9083 22.8467 13.2 22.8467C13.4917 22.8467 13.7715 22.9804 13.9778 23.2184C14.1841 23.4564 14.3 23.7792 14.3 24.1158Z" fill="#888"/>
                            </svg>
                        </div>
                        <select id="branch" required>
                            <option value="" selected></option>
                            <?php foreach ($branches as $branch): ?>
                            <option value="<?= $branch['id'] ?>"><?= htmlspecialchars($branch['branch_name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label for="branch" class="branch-label">Choose a branch</label>
                        <i class="select-arrow fa-solid fa-chevron-down"></i>
                    </div>
                    <span class="error" id="branchError"></span>
                </div>

                <div class="btns">
                    <button type="button" class="cancel-btn" id="step1CancelBtn">Cancel</button>
                    <button type="submit" class="submit-btn" id="signupBtn">Create</button>
                </div>

            </form>
        </div>

        <!-- ───────────────── STEP 2: Confirm Account ───────────────── -->
        <div class="form-step" id="step2">
            <h1>Confirm Account</h1>
            <p>Re-enter your details to confirm</p>

            <form id="confirmForm" novalidate>

                <!-- Confirm Email -->
                <div class="email-group">
                    <div class="input-field">
                        <div class="field-icon">
                            <svg width="22" height="18" viewBox="0 0 37 30" fill="none" stroke="#1e1e1e" xmlns="http://www.w3.org/2000/svg">
                                <path d="M36.6667 3.66667C36.6667 1.65 35.0167 0 33 0H3.66667C1.65 0 0 1.65 0 3.66667V25.6667C0 27.6833 1.65 29.3333 3.66667 29.3333H33C35.0167 29.3333 36.6667 27.6833 36.6667 25.6667V3.66667ZM33 3.66667L18.3333 12.8333L3.66667 3.66667H33ZM33 25.6667H3.66667V7.33333L18.3333 16.5L33 7.33333V25.6667Z" fill="#888"/>
                            </svg>
                        </div>
                        <input id="confirmEmail" type="email" placeholder=" " required>
                        <label class="email-label" for="confirmEmail">Confirm your email</label>
                    </div>
                    <span class="error" id="confirmEmailError"></span>
                </div>

                <!-- Confirm Password -->
                <div class="pass-group">
                    <div class="input-field">
                        <div class="field-icon">
                            <svg width="18" height="24" viewBox="0 0 30 40" fill="none" stroke="#1e1e1e" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 30C14.0054 30 13.0516 29.6049 12.3483 28.9016C11.6451 28.1984 11.25 27.2446 11.25 26.25C11.25 24.1688 12.9187 22.5 15 22.5C15.9946 22.5 16.9484 22.8951 17.6516 23.5984C18.3549 24.3016 18.75 25.2554 18.75 26.25C18.75 27.2446 18.3549 28.1984 17.6516 28.9016C16.9484 29.6049 15.9946 30 15 30ZM26.25 35.625V16.875H3.75V35.625H26.25ZM26.25 13.125C27.2446 13.125 28.1984 13.5201 28.9016 14.2234C29.6049 14.9266 30 15.8804 30 16.875V35.625C30 36.6196 29.6049 37.5734 28.9016 38.2766C28.1984 38.9799 27.2446 39.375 26.25 39.375H3.75C2.75544 39.375 1.80161 38.9799 1.09835 38.2766C0.395088 37.5734 0 36.6196 0 35.625V16.875C0 14.7937 1.66875 13.125 3.75 13.125H5.625V9.375C5.625 6.8886 6.61272 4.50403 8.37087 2.74587C10.129 0.98772 12.5136 0 15 0C16.2311 0 17.4502 0.242492 18.5877 0.713629C19.7251 1.18477 20.7586 1.87532 21.6291 2.74587C22.4997 3.61642 23.1902 4.64992 23.6614 5.78734C24.1325 6.92477 24.375 8.14386 24.375 9.375V13.125H26.25ZM15 3.75C13.5082 3.75 12.0774 4.34263 11.0225 5.39752C9.96763 6.45242 9.375 7.88316 9.375 9.375V13.125H20.625V9.375C20.625 7.88316 20.0324 6.45242 18.9775 5.39752C17.9226 4.34263 16.4918 3.75 15 3.75Z" fill="#888"/>
                            </svg>
                        </div>
                        <input id="confirmPassword" type="password" placeholder=" " minlength="8" required>
                        <label class="pass-label" for="confirmPassword">Confirm your password</label>
                        <span class="pass-toggle" data-target="confirmPassword"><i class="fa-regular fa-eye"></i></span>
                    </div>
                    <span class="error" id="confirmPasswordError"></span>
                </div>

                <div class="btns">
                    <button type="button" class="cancel-btn" id="step2BackBtn">Back</button>
                    <button type="submit" class="submit-btn" id="confirmBtn">Confirm</button>
                </div>

            </form>
        </div>

    </section>

    <script>
        // Holds the values submitted on step 1, used to validate step 2 against.
        var accountData = { email: '', password: '' };

        var step1 = document.getElementById('step1');
        var step2 = document.getElementById('step2');
        var signupForm = document.getElementById('signupForm');
        var confirmForm = document.getElementById('confirmForm');
        var signupBtn = document.getElementById('signupBtn');

        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        function showStep(stepToShow, stepToHide) {
            stepToHide.classList.remove('active');
            stepToShow.classList.add('active');
        }

        function setError(inputEl, errorEl, message) {
            errorEl.textContent = message;
            inputEl.classList.add('error-input');
        }

        function clearError(inputEl, errorEl) {
            errorEl.textContent = '';
            inputEl.classList.remove('error-input');
        }

        // ── Password visibility toggles (works for both step 1 & step 2) ──
        document.querySelectorAll('.pass-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                var targetInput = document.getElementById(toggle.getAttribute('data-target'));
                var icon = toggle.querySelector('i');
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    targetInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // ── Custom select: toggle blank state + label float + arrow rotation ──
        var branchSelect = document.getElementById('branch');
        var branchWrapper = branchSelect.closest('.select-wrapper');

        // Track if the select dropdown is currently open/focused
        let isSelectOpen = false;

        branchSelect.addEventListener("mousedown", (e) => {
            // If it's already open, click again means user is closing it or toggling it blank
            if (isSelectOpen) {
                branchSelect.value = ""; // Turn it blank
                branchSelect.classList.remove("filled");
                branchWrapper.classList.remove("open");
                branchSelect.blur(); // Forces label back to center
                isSelectOpen = false;
                e.preventDefault(); // Prevents native dropdown from opening again instantly
            } else {
                // First click opens it
                isSelectOpen = true;
                branchSelect.classList.add("filled");
                branchWrapper.classList.add("open");
            }
        });

        // Fallback for keyboard navigation or change events
        branchSelect.addEventListener("change", () => {
            if (branchSelect.value !== "") {
                branchSelect.classList.add("filled");
            } else {
                branchSelect.classList.remove("filled");
            }
            branchWrapper.classList.remove("open");
            isSelectOpen = false;
        });

        // When clicking outside or tabbing away
        branchSelect.addEventListener("blur", () => {
            isSelectOpen = false;
            branchWrapper.classList.remove("open");
            if (branchSelect.value === "") {
                branchSelect.classList.remove("filled");
            }
        });

        // ── Clear individual field errors as the user types/selects ──
        document.getElementById('email').addEventListener('input', function () {
            clearError(this, document.getElementById('emailError'));
        });
        document.getElementById('password').addEventListener('input', function () {
            clearError(this, document.getElementById('passwordError'));
        });
        document.getElementById('confirmEmail').addEventListener('input', function () {
            clearError(this, document.getElementById('confirmEmailError'));
        });
        document.getElementById('confirmPassword').addEventListener('input', function () {
            clearError(this, document.getElementById('confirmPasswordError'));
        });

        // ── Step 1: Cancel button clears the form ──
        document.getElementById('step1CancelBtn').addEventListener('click', function () {
            signupForm.reset();
            branchSelect.classList.remove('filled');
            ['email', 'password', 'branch'].forEach(function (id) {
                var el = document.getElementById(id);
                clearError(el, document.getElementById(id + 'Error'));
            });
        });

        // ── Step 2: Back button returns to step 1 without losing step 1 values ──
        document.getElementById('step2BackBtn').addEventListener('click', function () {
            showStep(step1, step2);
        });

        // ── Step 1 submit: validate client-side, then check email + stage on the server ──
        signupForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var emailInput = document.getElementById('email');
            var passwordInput = document.getElementById('password');
            var emailError = document.getElementById('emailError');
            var passwordError = document.getElementById('passwordError');
            var branchError = document.getElementById('branchError');

            var isValid = true;

            if (emailInput.value.trim() === '') {
                setError(emailInput, emailError, 'Email is required');
                isValid = false;
            } else if (!emailRegex.test(emailInput.value.trim())) {
                setError(emailInput, emailError, 'Enter a valid email address');
                isValid = false;
            } else {
                clearError(emailInput, emailError);
            }

            if (passwordInput.value === '') {
                setError(passwordInput, passwordError, 'Password is required');
                isValid = false;
            } else if (passwordInput.value.length < 8) {
                setError(passwordInput, passwordError, 'Password must be at least 8 characters');
                isValid = false;
            } else {
                clearError(passwordInput, passwordError);
            }

            if (branchSelect.value === '') {
                setError(branchSelect, branchError, 'Please choose a branch');
                isValid = false;
            } else {
                clearError(branchSelect, branchError);
            }

            if (!isValid) return;

            signupBtn.disabled = true;
            signupBtn.textContent = 'Checking...';

            var formData = new FormData();
            formData.append('action', 'check_email');
            formData.append('email', emailInput.value.trim());
            formData.append('password', passwordInput.value);
            formData.append('branch', branchSelect.value);

            fetch('signup.php', { method: 'POST', body: formData })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    signupBtn.disabled = false;
                    signupBtn.textContent = 'Create';

                    if (!data.success) {
                        if (data.errors.email) setError(emailInput, emailError, data.errors.email);
                        if (data.errors.password) setError(passwordInput, passwordError, data.errors.password);
                        if (data.errors.branch) setError(branchSelect, branchError, data.errors.branch);
                        return;
                    }

                    // Save step 1 values so step 2 can be checked against them
                    accountData.email = emailInput.value.trim();
                    accountData.password = passwordInput.value;

                    showStep(step2, step1);
                })
                .catch(function () {
                    signupBtn.disabled = false;
                    signupBtn.textContent = 'Create';
                    setError(emailInput, emailError, 'Something went wrong. Please try again.');
                });
        });

        // ── Step 2 submit: validate, then compare against step 1 values ──
        confirmForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var confirmEmailInput = document.getElementById('confirmEmail');
            var confirmPasswordInput = document.getElementById('confirmPassword');
            var confirmEmailError = document.getElementById('confirmEmailError');
            var confirmPasswordError = document.getElementById('confirmPasswordError');

            var isValid = true;

            if (confirmEmailInput.value.trim() === '') {
                setError(confirmEmailInput, confirmEmailError, 'Email is required');
                isValid = false;
            } else if (!emailRegex.test(confirmEmailInput.value.trim())) {
                setError(confirmEmailInput, confirmEmailError, 'Enter a valid email address');
                isValid = false;
            } else {
                clearError(confirmEmailInput, confirmEmailError);
            }

            if (confirmPasswordInput.value === '') {
                setError(confirmPasswordInput, confirmPasswordError, 'Password is required');
                isValid = false;
            } else if (confirmPasswordInput.value.length < 8) {
                setError(confirmPasswordInput, confirmPasswordError, 'Password must be at least 8 characters');
                isValid = false;
            } else {
                clearError(confirmPasswordInput, confirmPasswordError);
            }

            if (!isValid) return;

            // Compare against step 1 values
            var emailMatches = confirmEmailInput.value.trim() === accountData.email;
            var passwordMatches = confirmPasswordInput.value === accountData.password;

            if (!emailMatches) {
                setError(confirmEmailInput, confirmEmailError, "The email doesn't match");
                isValid = false;
            }

            if (!passwordMatches) {
                setError(confirmPasswordInput, confirmPasswordError, "The password doesn't match");
                isValid = false;
            }

            if (!isValid) return;

            // ✅ Account is staged server-side (from step 1) — go finish it with a PIN
            window.location.href = "pin.php";
        });
    </script>
</body>
</html>