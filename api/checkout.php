<?php
// ── checkout.php ──────────────────────────────────────────────
// Server-side order placement endpoint.
// Called by addtocart.php's "Proceed to Checkout" button.
//
// POST JSON body from addtocart.php JS:
// {
//   "items":        [ { name, unitPrice, qty, total, image,
//                       milk, addons, orderType, notes } ],
//   "order_type":   "dine-in" | "takeout" | "delivery",
//   "address":      "...",
//   "delivery_fee": 30,
//   "tax":          5,
//   "notes":        ""
// }
//
// Returns JSON { success, order_id, total, message }
// ─────────────────────────────────────────────────────────────
session_start();
require_once '../config/db_config.php';

header('Content-Type: application/json');

// ── Auth guard ─────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

// user_id is ALWAYS from the session — never from request input
$userId = (int) $_SESSION['user_id'];

$raw   = file_get_contents('php://input');
$body  = json_decode($raw, true);

if (!$body || empty($body['items'])) {
    echo json_encode(['success' => false, 'error' => 'No items provided.']);
    exit;
}

$items       = $body['items'];
$orderType   = substr(trim($body['order_type']   ?? 'dine-in'), 0, 20);
$address     = trim($body['address']     ?? '');
$deliveryFee = max(0, (float) ($body['delivery_fee'] ?? 0));
$tax         = max(0, (float) ($body['tax']          ?? 0));
$orderNotes  = trim($body['notes'] ?? '');

// ── Re-calculate totals server-side (never trust client totals) ─
$subtotal = 0;
foreach ($items as &$item) {
    $item['unitPrice'] = max(0, (float) ($item['unitPrice'] ?? 0));
    $item['qty']       = max(1, (int)   ($item['qty']       ?? 1));
    $item['total']     = $item['unitPrice'] * $item['qty'];
    $subtotal         += $item['total'];
}
unset($item);
$total = $subtotal + $deliveryFee + $tax;

// ── Insert order ───────────────────────────────────────────────
$stmt = $connect->prepare(
    "INSERT INTO orders
       (user_id, status, order_type, subtotal, delivery_fee, tax, total, address, notes)
     VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("issdddss",
    $userId, $orderType, $subtotal, $deliveryFee, $tax, $total, $address, $orderNotes
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Failed to create order.']);
    exit;
}
$orderId = $connect->insert_id;

// ── Insert line items ──────────────────────────────────────────
$itemStmt = $connect->prepare(
    "INSERT INTO order_items
       (order_id, product_name, product_image, unit_price, quantity,
        line_total, milk, addons, order_type, notes)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

foreach ($items as $item) {
    $name      = substr(trim($item['name']      ?? ''), 0, 150);
    $image     = substr(trim($item['image']     ?? ''), 0, 255);
    $unitPrice = (float)  $item['unitPrice'];
    $qty       = (int)    $item['qty'];
    $lineTotal = $unitPrice * $qty;
    $milk      = substr(trim($item['milk']      ?? ''), 0, 80);
    $addons    = substr(trim($item['addons']    ?? ''), 0, 255);
    $oType     = substr(trim($item['orderType'] ?? ''), 0, 40);
    $notes     = trim($item['notes'] ?? '');

    $itemStmt->bind_param("sssdidssss",
        $orderId, $name, $image, $unitPrice, $qty,
        $lineTotal, $milk, $addons, $oType, $notes
    );
    $itemStmt->execute();
}

echo json_encode([
    'success'  => true,
    'order_id' => $orderId,
    'total'    => number_format($total, 2),
    'message'  => 'Order placed!',
]);