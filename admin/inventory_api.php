<?php
session_start();
require_once '../config/db_config.php';

header('Content-Type: application/json');

// Session guard
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_inventory_status':
            echo json_encode(getInventoryStatus($connect));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getInventoryStatus($connect) {
    // Check if max_stock column exists
    $columnCheck = $connect->query("SHOW COLUMNS FROM ingredients LIKE 'max_stock'");
    $hasMaxStock = $columnCheck->num_rows > 0;
    
    // Get inventory with branch information
    if ($hasMaxStock) {
        $query = "SELECT i.id, i.name, i.unit, i.stock, i.max_stock, i.branch_id, b.branch_name 
                  FROM ingredients i 
                  LEFT JOIN branches b ON i.branch_id = b.id 
                  ORDER BY i.name";
    } else {
        $query = "SELECT i.id, i.name, i.unit, i.stock, 1000 as max_stock, i.branch_id, b.branch_name 
                  FROM ingredients i 
                  LEFT JOIN branches b ON i.branch_id = b.id 
                  ORDER BY i.name";
    }
    
    $result = $connect->query($query);
    
    if (!$result) {
        return ['success' => false, 'error' => 'Database query failed: ' . $connect->error];
    }
    
    $inventory = [];
    while ($row = $result->fetch_assoc()) {
        $inventory[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'unit' => $row['unit'],
            'stock' => (float) $row['stock'],
            'max_stock' => (float) ($row['max_stock'] ?? 1000),
            'branch_id' => $row['branch_id'],
            'branch_name' => $row['branch_name'] ?? 'Main Branch'
        ];
    }
    
    return ['success' => true, 'inventory' => $inventory];
}
