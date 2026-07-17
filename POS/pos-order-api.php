<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Simple CORS support for local development where frontend may run on a different port.
if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

session_name('POS_SESSION');
session_start();
require_once __DIR__ . '/../config/db_config.php';

header('Content-Type: application/json');

function pos_json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function pos_normalize_order_type(string $orderType): string
{
    $normalized = strtolower(trim(str_replace('_', '-', $orderType)));
    $normalized = preg_replace('/\s+/', '-', $normalized);

    if ($normalized === 'dinein') {
        return 'dine-in';
    }
    if ($normalized === 'take-out') {
        return 'takeout';
    }
    if ($normalized === 'pick-up') {
        return 'pickup';
    }

    return in_array($normalized, ['dine-in', 'takeout', 'delivery', 'pickup'], true)
        ? $normalized
        : 'dine-in';
}

function pos_addons_to_text($addons): string
{
    if (empty($addons)) {
        return '';
    }

    if (!is_array($addons)) {
        return substr(trim((string) $addons), 0, 255);
    }

    $names = [];
    foreach ($addons as $addon) {
        if (is_array($addon)) {
            $name = trim((string) ($addon['value'] ?? $addon['name'] ?? ''));
        } else {
            $name = trim((string) $addon);
        }
        if ($name !== '') {
            $names[] = $name;
        }
    }

    return substr(implode(', ', $names), 0, 255);
}

function pos_table_exists(mysqli $connect, string $table): bool
{
    $safeTable = $connect->real_escape_string($table);
    $result = $connect->query("SHOW TABLES LIKE '{$safeTable}'");
    return $result && $result->num_rows > 0;
}

function pos_ensure_walk_in_customer(mysqli $connect): string
{
    $firstName = 'Walk-in';
    $lastName = 'Customer';
    $userName = $firstName . ' ' . $lastName;

    $stmt = $connect->prepare('SELECT user_name FROM users WHERE user_name = ? LIMIT 1');
    $stmt->bind_param('s', $userName);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        return $userName;
    }

    $email = 'walkin-' . bin2hex(random_bytes(6)) . '@boycold.local';
    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    try {
        $insert = $connect->prepare(
            "INSERT INTO users
               (firstname, lastname, email, password, is_verified, phone, address)
             VALUES (?, ?, ?, ?, 1, '', '')"
        );
        $insert->bind_param('ssss', $firstName, $lastName, $email, $password);
        $insert->execute();
    } catch (mysqli_sql_exception $e) {
        // A concurrent request may have created it after the first lookup.
        $stmt = $connect->prepare('SELECT user_name FROM users WHERE user_name = ? LIMIT 1');
        $stmt->bind_param('s', $userName);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            return $userName;
        }
        throw $e;
    }

    return $userName;
}

function pos_format_order_no(int $orderId, string $orderType, string $createdAt): string
{
    $typeCodes = [
        'delivery' => 'DEL',
        'pickup' => 'PU',
        'dine-in' => 'DI',
        'takeout' => 'TO',
    ];
    $typeCode = $typeCodes[$orderType] ?? 'GEN';
    $date = new DateTime($createdAt);

    return sprintf('POS-%s-%s-%05d', $typeCode, $date->format('Y'), $orderId);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        pos_json_response(['success' => false, 'error' => 'Method not allowed'], 405);
    }

    // Allow established employee sessions, and keep the existing user-session fallback for local/dev POS use.
    $hasEmployeeSession = !empty($_SESSION['employee_id']) || !empty($_SESSION['employee_name']) || !empty($_SESSION['employee_email']);
    $hasUserSession = !empty($_SESSION['user_id']) && (!empty($_SESSION['user_email']) || !empty($_SESSION['user_name']));
    if (!$hasEmployeeSession && !$hasUserSession) {
        pos_json_response(['success' => false, 'error' => 'Unauthorized. Please log in to the POS first.'], 401);
    }

    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!is_array($body)) {
        pos_json_response(['success' => false, 'error' => 'Invalid JSON payload'], 400);
    }
    if (empty($body['items']) || !is_array($body['items'])) {
        pos_json_response(['success' => false, 'error' => 'No order items provided'], 400);
    }

    $items = $body['items'];
    $orderType = pos_normalize_order_type((string) ($body['order_type'] ?? 'dine-in'));
    $notes = trim((string) ($body['notes'] ?? ''));
    $deliveryFee = max(0, (float) ($body['delivery_fee'] ?? 0));
    $tax = max(0, (float) ($body['tax'] ?? 0));
    $paymentMethod = strtolower(trim((string) ($body['payment_method'] ?? 'cod')));
    if (!in_array($paymentMethod, ['cod', 'gcash'], true)) {
        $paymentMethod = 'cod';
    }

    $paymentStatus = 'paid';
    $status = 'completed';
    $address = '';

    $subtotal = 0;
    foreach ($items as $item) {
        $unitPrice = max(0, (float) ($item['unitPrice'] ?? 0));
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $subtotal += $unitPrice * $qty;
    }
    $total = $subtotal + $deliveryFee + $tax;

    $connect->begin_transaction();

    $userName = pos_ensure_walk_in_customer($connect);

    // Get branch_id and cashier_id from session
    $branchId = isset($_SESSION['branch_id']) ? (int) $_SESSION['branch_id'] : 0;
    $cashierId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;

    $stmt = $connect->prepare(
        "INSERT INTO orders
           (user_name, status, order_type, payment_method, payment_status,
            subtotal, delivery_fee, tax, total, address, notes, branch_id, cashier_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        'sssssddddssii',
        $userName,
        $status,
        $orderType,
        $paymentMethod,
        $paymentStatus,
        $subtotal,
        $deliveryFee,
        $tax,
        $total,
        $address,
        $notes,
        $branchId,
        $cashierId
    );
    $stmt->execute();
    $orderId = (int) $connect->insert_id;

    $itemStmt = $connect->prepare(
        "INSERT INTO order_items
           (order_id, product_name, product_image, unit_price, quantity,
            line_total, milk, addons, order_type, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $trackIngredients = pos_table_exists($connect, 'product_ingredients') && pos_table_exists($connect, 'order_ingredients');
    $piStmt = null;
    $oiStmt = null;
    if ($trackIngredients) {
        $piStmt = $connect->prepare('SELECT ingredient_id, amount FROM product_ingredients WHERE product_name = ?');
        $oiStmt = $connect->prepare('INSERT INTO order_ingredients (order_id, ingredient_id, amount) VALUES (?, ?, ?)');
    }

    foreach ($items as $item) {
        $name = substr(trim((string) ($item['name'] ?? 'Unknown Item')), 0, 150);
        if ($name === '') {
            $name = 'Unknown Item';
        }
        $image = substr(trim((string) ($item['image'] ?? '')), 0, 255);
        $unitPrice = max(0, (float) ($item['unitPrice'] ?? 0));
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $lineTotal = $unitPrice * $qty;
        $milk = substr(trim((string) ($item['milk'] ?? '')), 0, 80);
        $addons = pos_addons_to_text($item['addons'] ?? '');
        $orderItemType = pos_normalize_order_type((string) ($item['orderType'] ?? $orderType));
        $itemNotes = trim((string) ($item['notes'] ?? ''));

        $itemStmt->bind_param(
            'issdidssss',
            $orderId,
            $name,
            $image,
            $unitPrice,
            $qty,
            $lineTotal,
            $milk,
            $addons,
            $orderItemType,
            $itemNotes
        );
        $itemStmt->execute();

        if ($piStmt && $oiStmt) {
            $piStmt->bind_param('s', $name);
            $piStmt->execute();
            $res = $piStmt->get_result();
            while ($prow = $res->fetch_assoc()) {
                $ingredientId = (int) $prow['ingredient_id'];
                $totalAmount = (float) $prow['amount'] * $qty;
                $oiStmt->bind_param('iid', $orderId, $ingredientId, $totalAmount);
                $oiStmt->execute();
            }
        }
    }

    $createdAt = date('Y-m-d H:i:s');
    $createdStmt = $connect->prepare('SELECT created_at FROM orders WHERE id = ?');
    $createdStmt->bind_param('i', $orderId);
    $createdStmt->execute();
    $createdRow = $createdStmt->get_result()->fetch_assoc();
    if (!empty($createdRow['created_at'])) {
        $createdAt = $createdRow['created_at'];
    }

    $connect->commit();

    pos_json_response([
        'success' => true,
        'order_id' => $orderId,
        'order_no' => pos_format_order_no($orderId, $orderType, $createdAt),
        'order_type' => $orderType,
        'created_at' => $createdAt,
        'total' => number_format($total, 2, '.', ''),
        'message' => 'POS order saved to history.',
    ]);
} catch (Throwable $e) {
    if (isset($connect) && $connect instanceof mysqli) {
        try {
            $connect->rollback();
        } catch (Throwable $rollbackError) {
            // Keep the original exception for the response.
        }
    }

    error_log('POS order save failed: ' . $e->getMessage());
    pos_json_response([
        'success' => false,
        'error' => 'Could not save POS order: ' . $e->getMessage(),
    ], 500);
}
