<?php
session_name('POS_SESSION');
session_start();
require_once __DIR__ . '/../config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_pin') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'errors' => []];

    $pin = trim($_POST['pin'] ?? '');

    if (!preg_match('/^\d{4}$/', $pin)) {
        $response['errors']['pin'] = 'PIN must contain exactly 4 digits.';
    }

    if (empty($response['errors'])) {
        $stmt = $connect->prepare('SELECT pin FROM employees WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['employee_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result || !password_verify($pin, $result['pin'])) {
            $response['errors']['pin'] = 'Incorrect PIN. Please try again.';
        } else {
            $response['success'] = true;
            $response['redirect'] = '../dashboard/pos-shift.php';
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
        <div class="form-step active">
            <h1>Enter Your PIN</h1>
            <p>Point of sale</p>

            <form id="verifyPinForm" novalidate>
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
                    <button type="button" class="cancel-btn" id="cancelBtn">
                        Cancel
                    </button>
                    <button class="submit-btn" id="verifyBtn">
                        Verify
                    </button>
                </div>
            </form>
        </div>
    </section>

    <script>
        const verifyPinForm = document.getElementById("verifyPinForm");
        const pinBoxes = document.querySelectorAll(".pin-box");
        const pinError = document.getElementById("pinError");
        const cancelBtn = document.getElementById("cancelBtn");
        const verifyBtn = document.getElementById("verifyBtn");

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

        verifyPinForm.addEventListener("submit", (e) => {
            e.preventDefault();
            pinError.textContent = "";
            const pin = getPin(pinBoxes);

            if (pin.length !== 4) {
                pinError.textContent = "PIN must contain exactly 4 digits.";
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.textContent = "Verifying...";

            const formData = new FormData();
            formData.append("action", "verify_pin");
            formData.append("pin", pin);

            fetch("verify_pin.php", { method: "POST", body: formData })
                .then(res => res.json())
                .then(data => {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = "Verify";

                    if (!data.success) {
                        if (data.errors.pin) pinError.textContent = data.errors.pin;
                        return;
                    }

                    window.location.href = data.redirect;
                })
                .catch(() => {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = "Verify";
                    pinError.textContent = "Something went wrong. Please try again.";
                });
        });

        cancelBtn.addEventListener("click", () => {
            window.location.href = "login.php";
        });

        pinBoxes[0].focus();
    </script>
</body>
</html>
