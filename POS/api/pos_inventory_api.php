<?php
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

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_inventory':
            echo json_encode(getInventory($connect, $branchId));
            break;
            
        case 'update_inventory':
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode(updateInventory($connect, $branchId, $input));
            break;
            
        case 'reset_inventory':
            echo json_encode(resetInventory($connect, $branchId));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getInventory($connect, $branchId) {
    $stmt = $connect->prepare("SELECT name, unit, stock, max_stock FROM ingredients WHERE branch_id = ?");
    $stmt->bind_param('i', $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $inventory = [];
    while ($row = $result->fetch_assoc()) {
        $key = mapIngredientToKey($row['name']);
        if ($key) {
            $inventory[$key] = [
                'current' => (float) $row['stock'],
                'max' => (float) $row['max_stock'],
                'unit' => $row['unit']
            ];
        }
    }
    $stmt->close();
    
    // Ensure all required keys exist with defaults
    $defaults = [
        'coffeeBeans' => ['current' => 1000, 'max' => 1000, 'unit' => 'g'],
        'milk' => ['current' => 1000, 'max' => 1000, 'unit' => 'ml'],
        'matcha' => ['current' => 1000, 'max' => 1000, 'unit' => 'g'],
        'chocolate' => ['current' => 1000, 'max' => 1000, 'unit' => 'g'],
        'cups' => ['current' => 100, 'max' => 100, 'unit' => 'pcs']
    ];
    
    foreach ($defaults as $key => $default) {
        if (!isset($inventory[$key])) {
            $inventory[$key] = $default;
        }
    }
    
    return ['success' => true, 'inventory' => $inventory];
}

function updateInventory($connect, $branchId, $input) {
    foreach ($input as $key => $data) {
        $ingredientName = mapKeyToIngredient($key);
        if ($ingredientName) {
            $current = $data['current'] ?? 0;
            $stmt = $connect->prepare("UPDATE ingredients SET stock = ? WHERE branch_id = ? AND name = ?");
            $stmt->bind_param('dis', $current, $branchId, $ingredientName);
            $stmt->execute();
            $stmt->close();
        }
    }
    return ['success' => true];
}

function resetInventory($connect, $branchId) {
    $defaults = [
        'Coffee Beans' => 1000,
        'Whole Milk' => 1000,
        'Oat Milk' => 1000,
        'Matcha' => 1000,
        'Chocolate' => 1000,
        'Espresso Shot' => 1000,
        'Whipped Cream' => 1000,
        'Cups' => 100
    ];
    
    foreach ($defaults as $name => $stock) {
        $stmt = $connect->prepare("UPDATE ingredients SET stock = ? WHERE branch_id = ? AND name = ?");
        $stmt->bind_param('dis', $stock, $branchId, $name);
        $stmt->execute();
        $stmt->close();
    }
    
    return ['success' => true];
}

function mapIngredientToKey($name) {
    $map = [
        'Coffee Beans' => 'coffeeBeans',
        'Whole Milk' => 'milk',
        'Oat Milk' => 'milk',
        'Matcha' => 'matcha',
        'Chocolate' => 'chocolate',
        'Cups' => 'cups'
    ];
    return $map[$name] ?? null;
}

function mapKeyToIngredient($key) {
    $map = [
        'coffeeBeans' => 'Coffee Beans',
        'milk' => 'Whole Milk',
        'matcha' => 'Matcha',
        'chocolate' => 'Chocolate',
        'cups' => 'Cups'
    ];
    return $map[$key] ?? null;
}
