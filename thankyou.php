<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        .card { max-width: 600px; padding: 1.5rem; border: 1px solid #ddd; border-radius: 8px; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Thank you for your order</h1>
        <p>Your order has been received and is now being processed.</p>
        <p class="muted">Order total: $<?php echo htmlspecialchars((string)($_SESSION['order_total'] ?? '0.00')); ?></p>
        <p><a href="index.php">Return to store</a></p>
        <p><a href="checkout.php">Back to checkout</a></p>
    </div>
</body>
</html>
