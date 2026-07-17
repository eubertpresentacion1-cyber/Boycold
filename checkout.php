<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!isset($_SESSION['checkout_data']) || !is_array($_SESSION['checkout_data'])) {
    $_SESSION['checkout_data'] = [];
}

$boycold_cart_items = array_values($_SESSION['cart']);
$boycold_checkout_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $required = ['name', 'email', 'address', 'city', 'state', 'zip', 'country'];
    $missing = [];

    foreach ($required as $field) {
        $value = trim((string)($_POST[$field] ?? ''));
        if ($value === '') {
            $missing[] = $field;
        } else {
            $_SESSION['checkout_data'][$field] = $value;
        }
    }

    if (!empty($missing)) {
        $boycold_checkout_error = 'Please complete the required fields: ' . implode(', ', $missing) . '.';
    } elseif (empty($boycold_cart_items)) {
        $boycold_checkout_error = 'Your cart is empty.';
    } else {
        $subtotal = 0;
        foreach ($boycold_cart_items as $item) {
            $qty = max(1, (int)($item['qty'] ?? 1));
            $price = (float)($item['price'] ?? 0);
            $subtotal += $price * $qty;
        }

        $_SESSION['order_total'] = number_format($subtotal, 2, '.', '');
        $_SESSION['order_status'] = 'received';
        header('Location: thankyou.php');
        exit;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        form { max-width: 600px; display: grid; gap: 0.75rem; }
        input, select { padding: 0.6rem; }
        .error { color: #b00020; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>Checkout</h1>
    <p>Complete your details below to place an order.</p>
    <?php if ($boycold_checkout_error !== ''): ?>
        <div class="error"><?php echo htmlspecialchars($boycold_checkout_error); ?></div>
    <?php endif; ?>
    <form method="post">
        <input name="name" placeholder="Full name" value="<?php echo htmlspecialchars((string)($_SESSION['checkout_data']['name'] ?? '')); ?>">
        <input name="email" type="email" placeholder="Email" value="<?php echo htmlspecialchars((string)($_SESSION['checkout_data']['email'] ?? '')); ?>">
        <input name="address" placeholder="Address" value="<?php echo htmlspecialchars((string)($_SESSION['checkout_data']['address'] ?? '')); ?>">
        <input name="city" placeholder="City" value="<?php echo htmlspecialchars((string)($_SESSION['checkout_data']['city'] ?? '')); ?>">
        <input name="state" placeholder="State" value="<?php echo htmlspecialchars((string)($_SESSION['checkout_data']['state'] ?? '')); ?>">
        <input name="zip" placeholder="ZIP" value="<?php echo htmlspecialchars((string)($_SESSION['checkout_data']['zip'] ?? '')); ?>">
        <input name="country" placeholder="Country" value="<?php echo htmlspecialchars((string)($_SESSION['checkout_data']['country'] ?? '')); ?>">
        <button name="place_order" type="submit">Place order</button>
    </form>
</body>
</html>
