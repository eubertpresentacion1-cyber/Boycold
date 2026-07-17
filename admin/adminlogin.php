<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin-css/adminlogin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>BoyCold Cafe</title>
</head>
<body>
    <nav>
        <div class="nav-logo">
            <img src="../img/LOGO.png" alt="BoyCold Cafe Logo" class="logo">
        </div>
    </nav>

    <section class="login-container">

        <!-- ───────────────── STEP 1: Login ───────────────── -->
        <div class="form-step active" id="step1">
            <h1>Login</h1>
            <p>Secure login. Smarter Management</p>

            <form id="loginForm" novalidate>

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
                    <div class="btns">
                        <button type="submit" class="submit-btn">
                            Log In
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <script>
        // Holds the values submitted on step 1, used to validate step 2 against.
        const loginForm = document.getElementById("loginForm");

        const email = document.getElementById("email");
        const password = document.getElementById("password");

        const emailError = document.getElementById("emailError");
        const passwordError = document.getElementById("passwordError");

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        function setError(input, error, message) {
            error.textContent = message;
            input.classList.add("error-input");
        }

        function clearError(input, error) {
            error.textContent = "";
            input.classList.remove("error-input");
        }

        // Show / hide password
        document.querySelector(".pass-toggle").addEventListener("click", function () {

            const icon = this.querySelector("i");

            if (password.type === "password") {
                password.type = "text";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                password.type = "password";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            }

        });

        // Clear errors while typing
        email.addEventListener("input", () => clearError(email, emailError));
        password.addEventListener("input", () => clearError(password, passwordError));

        // Form validation
        loginForm.addEventListener("submit", function (e) {

            e.preventDefault();

            let valid = true;

            if (email.value.trim() === "") {
                setError(email, emailError, "Email is required.");
                valid = false;
            } else if (!emailRegex.test(email.value.trim())) {
                setError(email, emailError, "Enter a valid email.");
                valid = false;
            }

            if (password.value === "") {
                setError(password, passwordError, "Password is required.");
                valid = false;
            } else if (password.value.length < 8) {
                setError(password, passwordError, "Password must be at least 8 characters.");
                valid = false;
            }

            if (!valid) return;
            window.location.href = "dashboard.php";
        });
    </script>
</body>
</html>