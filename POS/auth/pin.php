<?php
session_name('POS_SESSION');
session_start();
require_once __DIR__ . '/../config/db_config.php';

$isNewSignup = isset($_SESSION['employee_signup']);
$isExistingEmployee = isset($_SESSION['employee_id']);

// Nothing to attach a PIN to — send them back to sign up
if (!$isNewSignup && !$isExistingEmployee) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_pin') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'errors' => []];

    $pin = trim($_POST['pin'] ?? '');
    $confirmPin = trim($_POST['confirm_pin'] ?? '');

    if (!preg_match('/^\d{4}$/', $pin)) {
        $response['errors']['pin'] = 'PIN must contain exactly 4 digits.';
    } elseif (!preg_match('/^\d{4}$/', $confirmPin)) {
        $response['errors']['confirm_pin'] = 'Please enter all 4 digits.';
    } elseif ($pin !== $confirmPin) {
        $response['errors']['confirm_pin'] = 'PIN does not match.';
    }

    if (empty($response['errors'])) {
        $hashedPin = password_hash($pin, PASSWORD_DEFAULT);

        if ($isNewSignup) {
            $data = $_SESSION['employee_signup'];

            $stmt = $connect->prepare(
                'INSERT INTO employees (email, branch_id, password, pin) VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param('siss', $data['email'], $data['branch_id'], $data['password'], $hashedPin);

            if ($stmt->execute()) {
                $_SESSION['employee_id'] = $stmt->insert_id;
                $_SESSION['employee_email'] = $data['email'];
                $_SESSION['branch_id'] = $data['branch_id'];
                unset($_SESSION['employee_signup']);
                $response['success'] = true;
            } else {
                $response['errors']['pin'] = 'Something went wrong. Please try again.';
            }
            $stmt->close();

        } else {
            $stmt = $connect->prepare('UPDATE employees SET pin = ? WHERE id = ?');
            $stmt->bind_param('si', $hashedPin, $_SESSION['employee_id']);

            if ($stmt->execute()) {
                $response['success'] = true;
            } else {
                $response['errors']['pin'] = 'Something went wrong. Please try again.';
            }
            $stmt->close();
        }
    }

    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="auth-css/pin.css">
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

    <section class="pin-container">

        <!-- PIN CREATION -->
        <div class="form-step active" id="step1">

            <h1>Create Your PIN</h1>

            <p>Set a 4-digit PIN for quick access</p>

            <form id="pinForm" novalidate>

                <div class="pin-group">

                    <div class="pin-inputs">

                        <input type="password" maxlength="1" inputmode="numeric" class="pin-box">
                        <input type="password" maxlength="1" inputmode="numeric" class="pin-box">
                        <input type="password" maxlength="1" inputmode="numeric" class="pin-box">
                        <input type="password" maxlength="1" inputmode="numeric" class="pin-box">

                    </div>

                    <span class="error" id="pinError"></span>

                </div>
                <div class="btns">
                    <button type="button" class="cancel-btn" id="cancelBtn1">
                        Cancel
                    </button>
                    <button class="submit-btn">
                        Create
                    </button>
                </div>
                

            </form>

        </div>



        <!-- STEP 2 -->

        <div class="form-step" id="step2">

            <h1>Confirm the 4 digit passcode</h1>

            <p>Point of sale</p>

            <form id="confirmForm" novalidate>

                <div class="pin-group">

                    <div class="pin-inputs">

                        <input type="password" maxlength="1" inputmode="numeric" class="confirm-box">
                        <input type="password" maxlength="1" inputmode="numeric" class="confirm-box">
                        <input type="password" maxlength="1" inputmode="numeric" class="confirm-box">
                        <input type="password" maxlength="1" inputmode="numeric" class="confirm-box">

                    </div>

                    <span class="error" id="confirmPinError"></span>

                </div>

                <div class="btns">

                    <button type="button" class="cancel-btn" id="cancelBtn2">
                        Cancel
                    </button>

                    <button class="submit-btn" id="confirmPinBtn">
                        Confirm
                    </button>
                </div>
            </form>
        </div>

        <div class="form-step" id="step3">

            <div class="success-container">

                <div class="success-image">
                    <!-- Replace with your approved check image -->
                    <img src="/img/ChatGPT Image Jun 26, 2026, 11_12_31 PM 1.png"
                        alt="Success">
                </div>

                <h1><?php echo $isNewSignup ? 'Account Created Successfully!' : 'Passcode Set Successfully!'; ?></h1>

                <p>
                    <?php echo $isNewSignup
                        ? 'The employee account has been created.'
                        : 'Your passcode has been saved.'; ?>
                </p>

                <button class="loginbtn" id="loginBtn">
                    <?php echo $isNewSignup ? 'Log In' : 'Continue'; ?>
                </button>
            </div>
        </div>
    </section>

    
    <script>
        // PIN CREATION FLOW
        const pinForm = document.getElementById("pinForm");
        const confirmForm = document.getElementById("confirmForm");
        const pinBoxes = document.querySelectorAll(".pin-box");
        const confirmBoxes = document.querySelectorAll(".confirm-box");
        const pinError = document.getElementById("pinError");
        const confirmPinError = document.getElementById("confirmPinError");
        const cancelBtn1 = document.getElementById("cancelBtn1");
        const cancelBtn2 = document.getElementById("cancelBtn2");
        const confirmPinBtn = document.getElementById("confirmPinBtn");

        let savedPin = "";

        function setupPinBoxes(boxes, errorElement) {
            boxes.forEach((box, index) => {
                box.addEventListener("keydown", (e) => {
                    const allowedKeys = ["Backspace", "Delete", "Tab", "ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown", "Enter"];
                    if (allowedKeys.includes(e.key)) {
                        errorElement.textContent = "";
                        if (e.key === "Backspace") {
                            if (box.value === "" && index > 0) {
                                boxes[index - 1].focus();
                            }
                        }
                        return;
                    }
                    if (!/^[0-9]$/.test(e.key)) {
                        e.preventDefault();
                        errorElement.textContent = "*Use numerical digits only.*";
                        return;
                    }
                    errorElement.textContent = "";
                });

                box.addEventListener("input", () => {
                    box.value = box.value.replace(/\D/g, "");
                    if (box.value && index < boxes.length - 1) {
                        boxes[index + 1].focus();
                    }
                });
            });
        }

        function getPin(boxes) {
            return [...boxes].map(box => box.value).join("");
        }

        setupPinBoxes(pinBoxes, pinError);
        setupPinBoxes(confirmBoxes, confirmPinError);

        pinForm.addEventListener("submit", (e) => {
            e.preventDefault();
            pinError.textContent = "";
            const pin = getPin(pinBoxes);

            if (pin.length !== 4) {
                pinError.textContent = "PIN must contain exactly 4 digits.";
                return;
            }

            savedPin = pin;
            step1.classList.remove("active");
            step2.classList.add("active");
            confirmBoxes[0].focus();
        });

        function resetPin() {
            savedPin = "";
            pinBoxes.forEach(box => box.value = "");
            confirmBoxes.forEach(box => box.value = "");
            pinError.textContent = "";
            confirmPinError.textContent = "";
            step2.classList.remove("active");
            step3.classList.remove("active");
            step1.classList.add("active");
            pinBoxes[0].focus();
        }

        document.getElementById("cancelBtn1").addEventListener("click", resetPin);
        document.getElementById("cancelBtn2").addEventListener("click", resetPin);

        confirmForm.addEventListener("submit", (e) => {
            e.preventDefault();
            confirmPinError.textContent = "";
            const confirmPin = getPin(confirmBoxes);

            if (confirmPin.length !== 4) {
                confirmPinError.textContent = "Please enter all 4 digits.";
                return;
            }

            confirmPinBtn.disabled = true;
            confirmPinBtn.textContent = "Saving...";

            const formData = new FormData();
            formData.append("action", "create_pin");
            formData.append("pin", savedPin);
            formData.append("confirm_pin", confirmPin);

            fetch("pin.php", { method: "POST", body: formData })
                .then(res => res.json())
                .then(data => {
                    confirmPinBtn.disabled = false;
                    confirmPinBtn.textContent = "Confirm";

                    if (!data.success) {
                        if (data.errors.pin) confirmPinError.textContent = data.errors.pin;
                        if (data.errors.confirm_pin) confirmPinError.textContent = data.errors.confirm_pin;
                        return;
                    }

                    step2.classList.remove("active");
                    step3.classList.add("active");
                })
                .catch(() => {
                    confirmPinBtn.disabled = false;
                    confirmPinBtn.textContent = "Confirm";
                    confirmPinError.textContent = "Something went wrong. Please try again.";
                });
        });

        document.getElementById("loginBtn").addEventListener("click", () => {
            window.location.href = "login.php";
        });

        pinBoxes[0].focus();
    </script>

</body>
</html>