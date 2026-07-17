<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_name('POS_SESSION');
session_start();
require_once '../config/db_config.php';

header('Content-Type: application/json');

// Session guard
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$employeeId = (int) $_SESSION['employee_id'];
$branchId = isset($_SESSION['branch_id']) ? (int) $_SESSION['branch_id'] : 0;

// Generate or get session ID
$sessionId = isset($_SESSION['pos_session_id']) ? $_SESSION['pos_session_id'] : null;
if (!$sessionId) {
    $sessionId = bin2hex(random_bytes(16));
    $_SESSION['pos_session_id'] = $sessionId;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_cart':
            echo json_encode(getCart($connect, $employeeId, $branchId, $sessionId));
            break;

        case 'add_to_cart':
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode(addToCart($connect, $employeeId, $branchId, $sessionId, $input));
            break;

        case 'remove_from_cart':
            $cartItemId = (int) ($_GET['cart_item_id'] ?? 0);
            echo json_encode(removeFromCart($connect, $employeeId, $branchId, $sessionId, $cartItemId));
            break;

        case 'clear_cart':
            echo json_encode(clearCart($connect, $employeeId, $branchId, $sessionId));
            break;

        case 'get_receipt_number':
            echo json_encode(getReceiptNumber($connect, $branchId));
            break;

        case 'increment_receipt_counter':
            echo json_encode(incrementReceiptCounter($connect, $branchId));
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Throwable $e) {
    error_log('pos_cart_api failed [' . $action . ']: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getCart(mysqli $connect, int $employeeId, int $branchId, string $sessionId) {
    // Ensure session exists
    ensureSession($connect, $employeeId, $branchId, $sessionId);

    $stmt = $connect->prepare("SELECT * FROM pos_cart WHERE employee_id = ? AND branch_id = ? AND session_id = ? ORDER BY created_at");
    $stmt->bind_param('iis', $employeeId, $branchId, $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $cart[] = [
            'id' => $row['id'],
            'product_id' => $row['product_id'],
            'name' => $row['product_name'],
            'img' => $row['product_image'],
            'category' => $row['category'],
            'basePrice' => (float) $row['base_price'],
            'milk' => $row['milk'],
            'milkPrice' => (float) $row['milk_price'],
            'addons' => json_decode($row['addons'] ?? '[]', true) ?: [],
            'orderType' => $row['order_type'],
            'qty' => (int) $row['quantity'],
            'itemTotal' => (float) $row['item_total']
        ];
    }
    $stmt->close();

    return ['success' => true, 'cart' => $cart];
}

function addToCart(mysqli $connect, int $employeeId, int $branchId, string $sessionId, array $input) {
    if (!is_array($input)) {
        return ['success' => false, 'error' => 'Invalid JSON payload'];
    }

    // Ensure session exists
    ensureSession($connect, $employeeId, $branchId, $sessionId);

    $productId    = (int) ($input['product_id'] ?? 0);
    $productName  = substr(trim((string) ($input['name'] ?? '')), 0, 150);
    $productImage = substr(trim((string) ($input['img'] ?? '')), 0, 255);
    $category     = substr(trim((string) ($input['category'] ?? '')), 0, 100);
    $basePrice    = max(0, (float) ($input['basePrice'] ?? 0));
    $milk         = substr(trim((string) ($input['milk'] ?? '')), 0, 80);
    $milkPrice    = max(0, (float) ($input['milkPrice'] ?? 0));
    $addons       = json_encode($input['addons'] ?? []);
    $orderType    = substr(trim((string) ($input['orderType'] ?? 'Dine In')), 0, 40);
    $quantity     = max(1, (int) ($input['qty'] ?? 1));
    $itemTotal    = max(0, (float) ($input['itemTotal'] ?? 0));

    $stmt = $connect->prepare(
        "INSERT INTO pos_cart
           (employee_id, branch_id, session_id, product_id, product_name, product_image,
            category, base_price, milk, milk_price, addons, order_type, quantity, item_total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // 14 columns -> 14 type chars: i i s i s s s d s d s s i d
    $stmt->bind_param(
        'iisisssdsdssid',
        $employeeId,
        $branchId,
        $sessionId,
        $productId,
        $productName,
        $productImage,
        $category,
        $basePrice,
        $milk,
        $milkPrice,
        $addons,
        $orderType,
        $quantity,
        $itemTotal
    );

    if ($stmt->execute()) {
        $insertId = $connect->insert_id;
        $stmt->close();
        return ['success' => true, 'cart_item_id' => $insertId];
    }

    $stmt->close();
    return ['success' => false, 'error' => 'Failed to add to cart'];
}

function removeFromCart(mysqli $connect, int $employeeId, int $branchId, string $sessionId, int $cartItemId) {
    $stmt = $connect->prepare("DELETE FROM pos_cart WHERE id = ? AND employee_id = ? AND branch_id = ? AND session_id = ?");
    $stmt->bind_param('iiis', $cartItemId, $employeeId, $branchId, $sessionId);

    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true];
    }

    $stmt->close();
    return ['success' => false, 'error' => 'Failed to remove from cart'];
}

function clearCart(mysqli $connect, int $employeeId, int $branchId, string $sessionId) {
    $stmt = $connect->prepare("DELETE FROM pos_cart WHERE employee_id = ? AND branch_id = ? AND session_id = ?");
    $stmt->bind_param('iis', $employeeId, $branchId, $sessionId);

    if ($stmt->execute()) {
        $stmt->close();

        // Mark session as completed
        $updateStmt = $connect->prepare("UPDATE pos_sessions SET status = 'completed' WHERE employee_id = ? AND branch_id = ? AND session_id = ?");
        $updateStmt->bind_param('iis', $employeeId, $branchId, $sessionId);
        $updateStmt->execute();
        $updateStmt->close();

        return ['success' => true];
    }

    $stmt->close();
    return ['success' => false, 'error' => 'Failed to clear cart'];
}

function getReceiptNumber(mysqli $connect, int $branchId) {
    $stmt = $connect->prepare("SELECT counter, prefix FROM receipt_counters WHERE branch_id = ?");
    $stmt->bind_param('i', $branchId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result) {
        $counter = (int) $result['counter'] + 1;
        $prefix = $result['prefix'];
        return ['success' => true, 'receipt_number' => $prefix . '-' . str_pad((string) $counter, 9, '0', STR_PAD_LEFT)];
    }

    // Initialize if not exists
    $insertStmt = $connect->prepare("INSERT INTO receipt_counters (branch_id, counter, prefix) VALUES (?, 0, 'BC')");
    $insertStmt->bind_param('i', $branchId);
    $insertStmt->execute();
    $insertStmt->close();

    return ['success' => true, 'receipt_number' => 'BC-000000001'];
}

function incrementReceiptCounter(mysqli $connect, int $branchId) {
    $stmt = $connect->prepare("UPDATE receipt_counters SET counter = counter + 1, last_used_date = CURDATE() WHERE branch_id = ?");
    $stmt->bind_param('i', $branchId);

    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true];
    }

    $stmt->close();
    return ['success' => false, 'error' => 'Failed to increment counter'];
}

function ensureSession(mysqli $connect, int $employeeId, int $branchId, string $sessionId) {
    $stmt = $connect->prepare("SELECT id FROM pos_sessions WHERE employee_id = ? AND branch_id = ? AND session_id = ?");
    $stmt->bind_param('iis', $employeeId, $branchId, $sessionId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        $insertStmt = $connect->prepare("INSERT INTO pos_sessions (employee_id, branch_id, session_id, status) VALUES (?, ?, ?, 'active')");
        $insertStmt->bind_param('iis', $employeeId, $branchId, $sessionId);
        $insertStmt->execute();
        $insertStmt->close();
    }
}