<?php
// ── cart_api.php ─────────────────────────────────────────────
// Handles all cart operations for the logged-in user only.
// Every read/write is scoped to $_SESSION['user_id'] — the user
// ID is NEVER taken from POST/GET input.
//
// Actions (POST JSON body  { "action": "...", ... }):
//   get       → return all cart items for this user
//   add       → insert or increment a cart row
//   update    → change quantity for a specific cart row
//   remove    → delete a specific cart row
//   clear     → delete all rows for this user
//
// Response: always JSON  { success: bool, ... }
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

// Always use session — never trust user input for the user ID
$userId = (int) $_SESSION['user_id'];

// ── Parse request ─────────────────────────────────────────────
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

// ── Route ────────────────────────────────────────────────────
switch ($action) {

    // ── GET: return all cart items for this user ──────────────
    case 'get':
        // cart stores product_id (FK to products table).
        // For items added via JS (name-based), we store them in a
        // separate "name" column. To keep backward-compatibility
        // with the localStorage approach, we return the raw row data.
        $stmt = $connect->prepare(
            "SELECT c.id, c.product_id, c.quantity, c.milk, c.addons,
                    c.order_type, c.notes,
                    p.product_name, p.price, p.image
             FROM   cart c
             JOIN   products p ON p.id = c.product_id
             WHERE  c.user_id = ?
             ORDER  BY c.created_at ASC"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Shape into the same format addtocart.php / cart.php expects
        $items = array_map(function($r) {
            $unitPrice = (float) $r['price'];
            $qty       = (int)   $r['quantity'];
            return [
                'cartId'    => (int) $r['id'],
                'productId' => (int) $r['product_id'],
                'name'      => $r['product_name'],
                'unitPrice' => $unitPrice,
                'qty'       => $qty,
                'total'     => $unitPrice * $qty,
                'image'     => $r['image'] ?? '',
                'milk'      => $r['milk']       ?? '',
                'addons'    => $r['addons']     ?? '',
                'orderType' => $r['order_type'] ?? '',
                'notes'     => $r['notes']      ?? '',
            ];
        }, $rows);

        echo json_encode(['success' => true, 'items' => $items]);
        break;

    // ── ADD: insert or increment ──────────────────────────────
    case 'add':
        $productId = isset($body['product_id']) ? (int) $body['product_id'] : 0;
        $qty       = isset($body['quantity'])   ? max(1, (int) $body['quantity']) : 1;
        $milk      = substr(trim($body['milk']       ?? ''), 0, 80);
        $addons    = substr(trim($body['addons']     ?? ''), 0, 255);
        $orderType = substr(trim($body['order_type'] ?? ''), 0, 40);
        $notes     = trim($body['notes'] ?? '');

        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid product_id.']);
            break;
        }

        // INSERT … ON DUPLICATE KEY UPDATE increments quantity.
        // The UNIQUE KEY is (user_id, product_id) in the schema.
        $stmt = $connect->prepare(
            "INSERT INTO cart (user_id, product_id, quantity, milk, addons, order_type, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               quantity   = quantity + VALUES(quantity),
               milk       = VALUES(milk),
               addons     = VALUES(addons),
               order_type = VALUES(order_type),
               notes      = VALUES(notes)"
        );
        $stmt->bind_param("iiissss", $userId, $productId, $qty, $milk, $addons, $orderType, $notes);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
        break;

    // ── UPDATE: set exact quantity for a cart row ─────────────
    case 'update':
        $cartId = isset($body['cart_id'])  ? (int) $body['cart_id']  : 0;
        $qty    = isset($body['quantity']) ? max(1, (int) $body['quantity']) : 1;

        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid cart_id.']);
            break;
        }

        // WHERE user_id = $userId prevents editing another user's row
        $stmt = $connect->prepare(
            "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("iii", $qty, $cartId, $userId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Cart updated.']);
        break;

    // ── REMOVE: delete one row ────────────────────────────────
    case 'remove':
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : 0;

        if ($cartId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid cart_id.']);
            break;
        }

        // WHERE user_id = $userId prevents deleting another user's row
        $stmt = $connect->prepare(
            "DELETE FROM cart WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $cartId, $userId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Item removed.']);
        break;

    // ── CLEAR: empty the entire cart for this user ────────────
    case 'clear':
        $stmt = $connect->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Cart cleared.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}