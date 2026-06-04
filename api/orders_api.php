<?php
// ── orders_api.php ────────────────────────────────────────────
// Handles order creation and retrieval.
// Regular users only see their own orders (WHERE user_id = ?).
// Admins (is_admin = 1 in users table, or use a session flag) can
// see all orders by omitting the user_id filter.
//
// Actions:
//   place   → create a new order from the current cart (POST JSON)
//   list    → get orders for this user (or all, if admin)
//   detail  → get one order with its items
//   cancel  → cancel a pending order (user's own only)
//   update_status → admin-only: change order status
//
// ─────────────────────────────────────────────────────────────
session_start();
require_once '../config/db_config.php';

header('Content-Type: application/json');

// ── Auth guard ────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

$userId  = (int) $_SESSION['user_id'];
$isAdmin = !empty($_SESSION['is_admin']); // set this in your login handler

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

switch ($action) {

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
    //   "address": "...",
    //   "delivery_fee": 30,
    //   "tax": 5,
    //   "notes": ""
    // }
    case 'place':
        $items      = $body['items']       ?? [];
        $orderType  = substr(trim($body['order_type'] ?? 'dine-in'), 0, 20);
        $address    = trim($body['address']  ?? '');
        $deliveryFee = (float) ($body['delivery_fee'] ?? 0);
        $tax         = (float) ($body['tax']          ?? 0);
        $orderNotes  = trim($body['notes']   ?? '');

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
        // user_id comes from SESSION — never from the request body
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

            $itemStmt->bind_param("sssdidssss",
                $orderId, $name, $image, $unitPrice, $qty,
                $lineTotal, $milk, $addons, $oType, $notes
            );
            $itemStmt->execute();
        }

        // ── Optionally clear the user's DB cart after order ───
        $clr = $connect->prepare("DELETE FROM cart WHERE user_id = ?");
        $clr->bind_param("i", $userId);
        $clr->execute();

        echo json_encode([
            'success'  => true,
            'order_id' => $orderId,
            'total'    => number_format($total, 2),
            'message'  => 'Order placed successfully.',
        ]);
        break;

    // ── LIST ORDERS ───────────────────────────────────────────
    // Regular users: only their own orders.
    // Admins: all orders (pass ?action=list&all=1 or set is_admin).
    case 'list':
        $status = trim($_GET['status'] ?? $body['status'] ?? '');

        if ($isAdmin && !empty($body['all'])) {
            // Admin: all orders
            $sql = "SELECT o.id, o.user_id, o.status, o.order_type,
                           o.subtotal, o.delivery_fee, o.tax, o.total,
                           o.created_at,
                           u.firstname, u.lastname, u.email
                    FROM   orders o
                    JOIN   users  u ON u.id = o.user_id";
            $params = "";
            $args   = [];
            if ($status) {
                $sql   .= " WHERE o.status = ?";
                $params = "s";
                $args   = [&$status];
            }
            $sql .= " ORDER BY o.created_at DESC";
            $stmt = $connect->prepare($sql);
            if ($params) {
                $stmt->bind_param($params, ...$args);
            }
        } else {
            // Regular user: only their own
            $sql = "SELECT o.id, o.user_id, o.status, o.order_type,
                           o.subtotal, o.delivery_fee, o.tax, o.total,
                           o.created_at
                    FROM   orders o
                    WHERE  o.user_id = ?";
            $args = [$userId];
            if ($status) {
                $sql  .= " AND o.status = ?";
                $args[] = $status;
            }
            $sql .= " ORDER BY o.created_at DESC";
            $stmt = $connect->prepare($sql);
            if ($status) {
                $stmt->bind_param("is", $userId, $status);
            } else {
                $stmt->bind_param("i", $userId);
            }
        }

        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'orders' => $orders]);
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
                "SELECT * FROM orders WHERE id = ? AND user_id = ?"
            );
            $stmt->bind_param("ii", $orderId, $userId);
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

        // Only allow cancelling pending orders; user_id guard prevents
        // cancelling someone else's order
        $stmt = $connect->prepare(
            "UPDATE orders SET status = 'cancelled'
             WHERE  id = ? AND user_id = ? AND status = 'pending'"
        );
        $stmt->bind_param("ii", $orderId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false,
                'error' => 'Cannot cancel: order not found, not yours, or not pending.']);
            break;
        }

        echo json_encode(['success' => true, 'message' => 'Order cancelled.']);
        break;

    // ── UPDATE STATUS (admin only) ────────────────────────────
    case 'update_status':
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden.']);
            break;
        }

        $orderId   = isset($body['order_id']) ? (int) $body['order_id'] : 0;
        $newStatus = trim($body['status'] ?? '');
        $allowed   = ['pending','confirmed','preparing','ready','delivered','cancelled'];

        if ($orderId <= 0 || !in_array($newStatus, $allowed, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid order_id or status.']);
            break;
        }

        $stmt = $connect->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => "Order status set to '$newStatus'."]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}