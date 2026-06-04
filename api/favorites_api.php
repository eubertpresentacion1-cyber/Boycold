<?php
// ── favorites_api.php ─────────────────────────────────────────
// Manages the favorites/wishlist for the logged-in user only.
// product_id is the FK to the products table (integer).
// The string-based "id" used in JS (e.g. "americano") maps to
// products.id — use get_product_id_by_name() or store numeric IDs
// in your menu HTML (data-product-id="1").
//
// Actions (POST JSON  { "action": "...", "product_id": N }):
//   get    → return all favorited product IDs for this user
//   toggle → add if not present, remove if present
//   add    → add (ignore if already exists)
//   remove → remove
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

$userId = (int) $_SESSION['user_id'];

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

switch ($action) {

    // ── GET: return list of favorited product IDs ─────────────
    case 'get':
        $stmt = $connect->prepare(
            "SELECT f.product_id, p.product_name, p.price, p.image, p.category
             FROM   favorites f
             JOIN   products  p ON p.id = f.product_id
             WHERE  f.user_id = ?
             ORDER  BY f.created_at DESC"
        );
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'favorites' => $rows]);
        break;

    // ── TOGGLE: add if not favorited, remove if already is ────
    case 'toggle':
        $productId = isset($body['product_id']) ? (int) $body['product_id'] : 0;
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid product_id.']);
            break;
        }

        // Check current state — WHERE user_id prevents reading another user's data
        $check = $connect->prepare(
            "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?"
        );
        $check->bind_param("ii", $userId, $productId);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;

        if ($exists) {
            $del = $connect->prepare(
                "DELETE FROM favorites WHERE user_id = ? AND product_id = ?"
            );
            $del->bind_param("ii", $userId, $productId);
            $del->execute();
            echo json_encode(['success' => true, 'favorited' => false]);
        } else {
            $ins = $connect->prepare(
                "INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)"
            );
            $ins->bind_param("ii", $userId, $productId);
            $ins->execute();
            echo json_encode(['success' => true, 'favorited' => true]);
        }
        break;

    // ── ADD: add without check (INSERT IGNORE handles dupe) ───
    case 'add':
        $productId = isset($body['product_id']) ? (int) $body['product_id'] : 0;
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid product_id.']);
            break;
        }

        $stmt = $connect->prepare(
            "INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)"
        );
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();

        echo json_encode(['success' => true, 'favorited' => true]);
        break;

    // ── REMOVE ────────────────────────────────────────────────
    case 'remove':
        $productId = isset($body['product_id']) ? (int) $body['product_id'] : 0;
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid product_id.']);
            break;
        }

        // WHERE user_id prevents removing another user's favorite
        $stmt = $connect->prepare(
            "DELETE FROM favorites WHERE user_id = ? AND product_id = ?"
        );
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();

        echo json_encode(['success' => true, 'favorited' => false]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}