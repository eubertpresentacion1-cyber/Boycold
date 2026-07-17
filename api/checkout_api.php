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

// ── Error/exception handling ────────────────────────────────────
// PHP 8.1+ makes mysqli throw a mysqli_sql_exception on query errors
// by default (e.g. a foreign-key violation on insert). Without a
// handler here, that exception was uncaught and killed the script
// with a blank body — the browser has nothing to parse and just
// reports "500 Internal Server Error" with no clue why. These
// handlers make sure any failure still comes back as readable JSON.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error: ' . $errstr,
        'file'    => $errfile,
        'line'    => $errline,
    ]);
    exit;
});

set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error: ' . $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
    exit;
});

// ── Auth guard ─────────────────────────────────────────────────
if (!isset($_SESSION['user_name'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

// user_name is derived fresh from the users table when possible —
// NOT trusted as-is from the session. orders.user_name has a foreign
// key to users.user_name (a generated column: firstname + ' ' + lastname).
// If the customer's name changed since login, the cached session value
// no longer matches any row in `users`, and the INSERT below would
// violate that foreign key on every single checkout attempt. Re-looking
// it up by user_id keeps it in sync, the same fix already applied in
// orders_api.php.
$userName = $_SESSION['user_name'];
if (!empty($_SESSION['user_id'])) {
    $uStmt = $connect->prepare('SELECT user_name FROM users WHERE id = ?');
    $uStmt->bind_param('i', $_SESSION['user_id']);
    $uStmt->execute();
    $uRow = $uStmt->get_result()->fetch_assoc();
    if ($uRow) {
        $userName = $uRow['user_name'];
        $_SESSION['user_name'] = $userName; // keep session in sync
    }
}

$raw   = file_get_contents('php://input');
$body  = json_decode($raw, true);

if (!$body || empty($body['items'])) {
    echo json_encode(['success' => false, 'error' => 'No items provided.']);
    exit;
}

$items       = $body['items'];
$orderType   = substr(trim($body['order_type']   ?? 'dine-in'), 0, 20);
$paymentMethod = strtolower(trim($body['payment_method'] ?? 'cod'));
if (!in_array($paymentMethod, ['cod', 'gcash'], true)) {
    $paymentMethod = 'cod';
}
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
$paymentStatus = ($paymentMethod === 'gcash') ? 'paid' : 'unpaid';
$stmt = $connect->prepare(
    "INSERT INTO orders
       (user_name, status, order_type, payment_method, payment_status, subtotal, delivery_fee, tax, total, address, notes, branch_id)
     VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$branchId = isset($_SESSION['branch_id']) ? (int) $_SESSION['branch_id'] : 1; // Default to Baliuag if not set
$stmt->bind_param("ssssddddssi",
    $userName, $orderType, $paymentMethod, $paymentStatus, $subtotal, $deliveryFee, $tax, $total, $address, $orderNotes, $branchId
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

    $itemStmt->bind_param("issdidssss",
        $orderId, $name, $image, $unitPrice, $qty,
        $lineTotal, $milk, $addons, $oType, $notes
    );
    $itemStmt->execute();
}

echo json_encode([
    'success'        => true,
    'order_id'       => $orderId,
    'total'          => number_format($total, 2),
    'payment_method' => $paymentMethod,
    'payment_status' => $paymentStatus,
    'message'        => 'Order placed!',
]);