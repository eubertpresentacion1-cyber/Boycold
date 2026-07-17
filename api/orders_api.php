<?php
// ── orders_api.php ────────────────────────────────────────────
// Handles order creation and retrieval.
// Regular users only see their own orders (WHERE user_name = ?).
// Admins (is_admin = 1 in users table, or use a session flag) can
// see all orders by omitting the user_name filter.
//
// Actions:
//   place   → create a new order from the current cart (POST JSON)
//   list    → get orders for this user (or all, if admin)
//   detail  → get one order with its items
//   cancel  → cancel a pending order (user's own only)
//   update_status → admin-only: change order status
//
// COD FLOW:
//   Order placed (status=pending, payment_status=unpaid for COD,
//                  payment_status=paid for GCash)
//     → Admin prepares order (status=preparing)
//     → Admin delivers (status=delivered)
//     → Admin marks completed (status=completed)
//         → if payment_method = 'cod', payment_status auto-set to 'paid'
// ─────────────────────────────────────────────────────────────

// Enable error catching
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Simple CORS support for local development where frontend may run on a
// different port (e.g. a dev server on :3000 while PHP runs elsewhere).
// Mirrors pos-order-api.php's handling.
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require_once '../config/db_config.php';

header('Content-Type: application/json');

// Set up error handler (catches classic PHP warnings/notices)
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
});

// ── Catch-all for uncaught exceptions ──────────────────────────
// PHP 8.1+ makes mysqli throw mysqli_sql_exception on query errors by
// default. set_error_handler() above does NOT catch these (they're
// Throwables, not classic PHP errors), so without this handler any
// SQL problem here would kill the script with a completely blank
// response body — the browser gets nothing to parse as JSON, and the
// orders list silently fails to load with no visible clue why.
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error: ' . $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine()
    ]);
    exit;
});

// ── Auth guard ────────────────────────────────────────────────
$userId     = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

if ($userId <= 0 && $employeeId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

// ── Always derive user_name fresh from the users table ─────────
// `orders.user_name` is filled from the SAME generated column as
// `users.user_name` (firstname + ' ' + lastname). Trusting
// $_SESSION['user_name'] instead is what was causing "No orders
// yet" even when rows exist: if that session value was ever set
// to something else at login (or the name changed since), it no
// longer matches orders.user_name and every WHERE user_name = ?
// query silently returns zero rows. Looking it up by user_id here
// guarantees an exact match every single time.
$userName = '';
if ($userId > 0) {
    $uStmt  = $connect->prepare("SELECT user_name FROM users WHERE id = ?");
    $uStmt->bind_param("i", $userId);
    $uStmt->execute();
    $uRow = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();

    if ($uRow) {
        $userName = $uRow['user_name'];
        $_SESSION['user_name'] = $userName; // keep session in sync for any other code reading it
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not found in database.']);
        exit;
    }
}

if ($userName === '' && $employeeId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not found.']);
    exit;
}

$isEmployee = $employeeId > 0;
$isAdmin = !empty($_SESSION['is_admin']) || $isEmployee; // POS employees can manage order statuses.

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

// Log the request for debugging
$actorLabel = $userName !== '' ? $userName : ('employee#' . $employeeId);
error_log("Orders API - User: $actorLabel, Action: $action, Body Keys: " . implode(',', array_keys($body)));

switch ($action) {

    // ── TEST ACTION (for debugging) ──────────────────────────
    case 'test':
        echo json_encode([
            'success' => true,
            'test' => 'API is working',
            'user_name' => $userName,
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;

    // ── PLACE ORDER ───────────────────────────────────────────
    // Expects JSON:
    // {
    //   "action": "place",
    //   "items": [
    //     { "name": "Americano", "unitPrice": 69, "qty": 2,
    //       "image": "/picture/...", "milk": "", "addons": "",
    //       "orderType": "dine-in", "notes": "" }
    //   ],
    //   "order_type": "dine-in",
    //   "payment_method": "cod" | "gcash",
    //   "address": "...",
    //   "delivery_fee": 30,
    //   "tax": 5,
    //   "notes": ""
    // }
    case 'place':
        $items          = $body['items']        ?? [];
        $orderType      = substr(trim($body['order_type'] ?? 'dine-in'), 0, 20);
        $address        = trim($body['address']  ?? '');
        $contactNumber  = trim($body['contact_number'] ?? '');
        $deliveryFee    = (float) ($body['delivery_fee'] ?? 0);
        $tax            = (float) ($body['tax']          ?? 0);
        $orderNotes     = trim($body['notes']   ?? '');
        $branchId       = isset($body['branch_id']) && $body['branch_id'] !== '' ? (int) $body['branch_id'] : null;

        // ── Payment method / status ────────────────────────────
        $paymentMethod = strtolower(trim($body['payment_method'] ?? 'cod'));
        if (!in_array($paymentMethod, ['cod', 'gcash'], true)) {
            $paymentMethod = 'cod';
        }
        // GCash is paid through the app at checkout time; COD is
        // settled later when the order is delivered (see update_status).
        $paymentStatus = ($paymentMethod === 'gcash') ? 'paid' : 'unpaid';

        if (empty($items)) {
            echo json_encode(['success' => false, 'error' => 'No items in order.']);
            break;
        }

        // Calculate subtotal from items (never trust the client total)
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float)($item['unitPrice'] ?? 0) * max(1, (int)($item['qty'] ?? 1));
        }
        $total = $subtotal + $deliveryFee + $tax;

        // ── Insert order row ──────────────────────────────────
        // user_name comes from SESSION — never from the request body
        $stmt = $connect->prepare(
            "INSERT INTO orders
               (user_name, status, order_type, payment_method, payment_status,
                subtotal, delivery_fee, tax, total, branch_id, address, notes)
             VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssssdddisss",
            $userName,
            $orderType,
            $paymentMethod,
            $paymentStatus,
            $subtotal,
            $deliveryFee,
            $tax,
            $total,
            $branchId,
            $address,
            $orderNotes
        );
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Failed to create order.']);
            break;
        }
        $orderId = $connect->insert_id;

        // ── Insert order_items ────────────────────────────────
        $itemStmt = $connect->prepare(
            "INSERT INTO order_items
               (order_id, product_name, product_image, unit_price, quantity,
                line_total, milk, addons, order_type, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $name      = substr(trim($item['name']      ?? ''), 0, 150);
            $image     = substr(trim($item['image']     ?? ''), 0, 255);
            $unitPrice = (float)  ($item['unitPrice']  ?? 0);
            $qty       = max(1, (int) ($item['qty']    ?? 1));
            $lineTotal = $unitPrice * $qty;
            $milk      = substr(trim($item['milk']      ?? ''), 0, 80);
            $addons    = substr(trim($item['addons']    ?? ''), 0, 255);
            $oType     = substr(trim($item['orderType'] ?? ''), 0, 40);
            $notes     = trim($item['notes'] ?? '');

            $itemStmt->bind_param(
                "issdidssss",
                $orderId,
                $name,
                $image,
                $unitPrice,
                $qty,
                $lineTotal,
                $milk,
                $addons,
                $oType,
                $notes
            );
            $itemStmt->execute();
        }

        // ── Clear the user's DB cart after order — but ONLY when this
        // order actually came from the persistent cart. Direct "buy now"
        // orders (from ordercustom.php) send from_cart = false so a
        // single-item purchase never wipes out unrelated cart contents.
        // Default to true so any older caller that omits the flag keeps
        // the original behavior.
        $fromCart = array_key_exists('from_cart', $body) ? (bool) $body['from_cart'] : true;
        if ($fromCart) {
            $clr = $connect->prepare("DELETE FROM cart WHERE user_name = ?");
            $clr->bind_param("s", $userName);
            $clr->execute();
        }

        echo json_encode([
            'success'        => true,
            'order_id'       => $orderId,
            'total'          => number_format($total, 2),
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'message'        => 'Order placed successfully.',
        ]);
        break;

    // ── LIST ORDERS ───────────────────────────────────────────
    // Regular users: only their own orders.
    // Admins: all orders (pass ?action=list&all=1 or set is_admin).
    case 'list':

    $status = strtolower(trim(
        $_POST['status']
        ?? $body['status']
        ?? $_GET['status']
        ?? ''
    ));

    $allowedStatus = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'completed', 'cancelled'];

    if (!in_array($status, $allowedStatus, true)) {
        $status = '';
    }

    if ($isAdmin && (!empty($_GET['all']) || !empty($body['all']))) {

        $sql = "
            SELECT
                o.id,
                o.user_name,
                o.status,
                o.order_type,
                o.payment_method,
                o.payment_status,
                o.subtotal,
                o.delivery_fee,
                o.tax,
                o.total,
                o.created_at,
                u.firstname,
                u.lastname,
                u.email
            FROM orders o
            JOIN users u
                ON u.user_name = o.user_name
        ";

        if ($status != '') {
            $sql .= " WHERE o.status = ?";
        }

        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $connect->prepare($sql);

        if ($status != '') {
            $stmt->bind_param("s", $status);
        }

    } else {

        $sql = "
            SELECT
                id,
                user_name,
                status,
                order_type,
                payment_method,
                payment_status,
                subtotal,
                delivery_fee,
                tax,
                total,
                created_at
            FROM orders
            WHERE user_name = ?
        ";

        if ($status != '') {
            $sql .= " AND status = ?";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $connect->prepare($sql);

        if ($status != '') {
            $stmt->bind_param("ss", $userName, $status);
        } else {
            $stmt->bind_param("s", $userName);
        }
    }

    $stmt->execute();

    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "success" => true,
        "orders" => $orders
    ]);

    break;

    // ── DETAIL: one order + its items ─────────────────────────
    case 'detail':
        $orderId = isset($body['order_id']) ? (int) $body['order_id']
            : (isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0);

        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid order_id.']);
            break;
        }

        // Non-admins can only see their own order
        if ($isAdmin) {
            $stmt = $connect->prepare(
                "SELECT * FROM orders WHERE id = ?"
            );
            $stmt->bind_param("i", $orderId);
        } else {
            $stmt = $connect->prepare(
                "SELECT * FROM orders WHERE id = ? AND user_name = ?"
            );
            $stmt->bind_param("is", $orderId, $userName);
        }
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found.']);
            break;
        }

        // Fetch line items
        $items = $connect->prepare(
            "SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC"
        );
        $items->bind_param("i", $orderId);
        $items->execute();
        $order['items'] = $items->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'order' => $order]);
        break;

    // ── CANCEL: user cancels their own pending order ──────────
    case 'cancel':
        $orderId = isset($body['order_id']) ? (int) $body['order_id'] : 0;
        if ($orderId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid order_id.']);
            break;
        }

        if ($isAdmin) {
            $stmt = $connect->prepare(
                "UPDATE orders
                 SET status = 'cancelled'
                 WHERE id = ?
                   AND status NOT IN ('ready', 'delivered', 'completed', 'cancelled')"
            );
            $stmt->bind_param("i", $orderId);
        } else {
            // Only allow cancelling orders that are still active; the user is
            // matched via the users table so stale or mismatched session names
            // do not prevent the update.
            $stmt = $connect->prepare(
                "UPDATE orders o
                 INNER JOIN users u ON u.user_name = o.user_name
                 SET o.status = 'cancelled'
                 WHERE o.id = ? AND u.id = ?
                   AND o.status NOT IN ('ready', 'delivered', 'completed', 'cancelled')"
            );
            $stmt->bind_param("ii", $orderId, $userId);
        }
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Cannot cancel: order not found, not yours, or already in a final state.'
            ]);
            break;
        }

        echo json_encode(['success' => true, 'message' => 'Order cancelled.']);
        break;

    // COD FLOW:
    //   pending → preparing → delivered → completed
    //   When status is set to 'completed' AND payment_method = 'cod',
    //   payment_status is automatically flipped to 'paid'.
    //   GCash orders are already payment_status = 'paid' from checkout.
    case 'update_status':
        $orderId   = isset($body['order_id']) ? (int) $body['order_id'] : 0;
        $newStatus = trim($body['status'] ?? '');
        $allowed   = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'completed', 'cancelled'];

        if ($orderId <= 0 || !in_array($newStatus, $allowed, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid order_id or status.']);
            break;
        }

        $existingOrderStmt = $connect->prepare("SELECT user_name, status FROM orders WHERE id = ?");
        $existingOrderStmt->bind_param("i", $orderId);
        $existingOrderStmt->execute();
        $existingOrder = $existingOrderStmt->get_result()->fetch_assoc();

        if (!$existingOrder) {
            echo json_encode(['success' => false, 'error' => 'Order not found.']);
            break;
        }

        // Staff/admins can move an order through any status. The one
        // exception: order-popup.php's "Confirm Order" button is also shown
        // to the customer who placed the order, so a non-admin is allowed
        // through here ONLY to flip their own still-pending order to
        // 'confirmed' — checked against the order row itself (not the
        // request body), so it can't be used to touch anyone else's order
        // or any other transition.
        $isOwnPendingConfirm = (
            $newStatus === 'confirmed' &&
            ($existingOrder['status'] ?? '') === 'pending' &&
            $userName !== '' &&
            $existingOrder['user_name'] === $userName
        );

        if (!$isAdmin && !$isOwnPendingConfirm) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden.']);
            break;
        }

        $shouldAwardLoyalty = ($newStatus === 'completed' && ($existingOrder['status'] ?? '') !== 'completed');

        if ($newStatus === 'completed') {
            // Auto-settle COD orders on completion; leave GCash (already paid) untouched
            $stmt = $connect->prepare(
                "UPDATE orders
                 SET status = ?,
                     payment_status = IF(payment_method = 'cod', 'paid', payment_status)
                 WHERE id = ?"
            );
        } else {
            $stmt = $connect->prepare("UPDATE orders SET status = ? WHERE id = ?");
        }
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();

        if ($shouldAwardLoyalty && !empty($existingOrder['user_name'])) {
            $loyaltyStmt = $connect->prepare(
                "UPDATE users
                 SET loyalty_beans = CASE WHEN loyalty_beans + 1 >= 10 THEN 0 ELSE loyalty_beans + 1 END,
                     loyalty_stamps = loyalty_stamps + CASE WHEN loyalty_beans + 1 >= 10 THEN 1 ELSE 0 END
                 WHERE user_name = ?"
            );
            $loyaltyStmt->bind_param("s", $existingOrder['user_name']);
            $loyaltyStmt->execute();
        }

        echo json_encode(['success' => true, 'message' => "Order status set to '$newStatus'."]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}