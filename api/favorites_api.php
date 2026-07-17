<?php
// ── favorites_api.php ─────────────────────────────────────────
// Manages the favorites/wishlist for the logged-in user only.
// product_name is now the identifier (string) instead of product_id.
//
// Actions (POST JSON  { "action": "...", "product_name": "..." }):
//   get    → return all favorited product names for this user
//   toggle → add if not present, remove if present
//   add    → add (ignore if already exists)
//   remove → remove
//
// ─────────────────────────────────────────────────────────────
session_start();
require_once '../config/db_config.php';

header('Content-Type: application/json');

// ── Auth guard ────────────────────────────────────────────────
if (!isset($_SESSION['user_name'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

$userName = $_SESSION['user_name'];

$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');

switch ($action) {

    // ── GET: return list of favorited product names ─────────────
    case 'get':
        $stmt = $connect->prepare(
            "SELECT f.product_name, p.price, p.image, p.category
             FROM   favorites f
             JOIN   products  p ON p.product_name = f.product_name
             WHERE  f.user_name = ?
             ORDER  BY f.created_at DESC"
        );
        $stmt->bind_param("s", $userName);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'favorites' => $rows]);
        break;

    // ── TOGGLE: add if not favorited, remove if already is ────
    case 'toggle':
        $productName = isset($body['product_name']) ? trim($body['product_name']) : '';
        if (empty($productName)) {
            echo json_encode(['success' => false, 'error' => 'Invalid product_name.']);
            break;
        }

        // Check current state — WHERE user_name prevents reading another user's data
        $check = $connect->prepare(
            "SELECT id FROM favorites WHERE user_name = ? AND product_name = ?"
        );
        $check->bind_param("ss", $userName, $productName);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;

        if ($exists) {
            $del = $connect->prepare(
                "DELETE FROM favorites WHERE user_name = ? AND product_name = ?"
            );
            $del->bind_param("ss", $userName, $productName);
            $del->execute();
            echo json_encode(['success' => true, 'favorited' => false]);
        } else {
            $ins = $connect->prepare(
                "INSERT IGNORE INTO favorites (user_name, product_name) VALUES (?, ?)"
            );
            $ins->bind_param("ss", $userName, $productName);
            $ins->execute();
            echo json_encode(['success' => true, 'favorited' => true]);
        }
        break;

    // ── ADD: add without check (INSERT IGNORE handles dupe) ───
    case 'add':
        $productName = isset($body['product_name']) ? trim($body['product_name']) : '';
        if (empty($productName)) {
            echo json_encode(['success' => false, 'error' => 'Invalid product_name.']);
            break;
        }

        $stmt = $connect->prepare(
            "INSERT IGNORE INTO favorites (user_name, product_name) VALUES (?, ?)"
        );
        $stmt->bind_param("ss", $userName, $productName);
        $stmt->execute();

        echo json_encode(['success' => true, 'favorited' => true]);
        break;

    // ── REMOVE ────────────────────────────────────────────────
    case 'remove':
        $productName = isset($body['product_name']) ? trim($body['product_name']) : '';
        if (empty($productName)) {
            echo json_encode(['success' => false, 'error' => 'Invalid product_name.']);
            break;
        }

        // WHERE user_name prevents removing another user's favorite
        $stmt = $connect->prepare(
            "DELETE FROM favorites WHERE user_name = ? AND product_name = ?"
        );
        $stmt->bind_param("ss", $userName, $productName);
        $stmt->execute();

        echo json_encode(['success' => true, 'favorited' => false]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}
