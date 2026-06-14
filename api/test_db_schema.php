<?php
/**
 * Database Schema & Connection Diagnostic
 * 
 * This script verifies:
 * 1. Database connection works
 * 2. orders table exists with required columns
 * 3. order_items table exists with required columns
 * 4. Column types are correct
 */

require_once '../config/db_config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; margin-bottom: 15px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .info { background: #e7f3ff; padding: 10px; border-left: 4px solid #007bff; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th { background: #f9f9f9; border: 1px solid #ddd; padding: 10px; text-align: left; font-weight: bold; }
        table td { border: 1px solid #ddd; padding: 10px; }
        table tr:nth-child(even) { background: #f9f9f9; }
        .status-icon { font-size: 20px; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Database Diagnostic Report</h1>
    <div class='info'>Generated: " . date('Y-m-d H:i:s') . "</div>";

// ═══════════════════════════════════════════════════════════════════
// 1. CONNECTION TEST
// ═══════════════════════════════════════════════════════════════════
echo "<h2>1️⃣ Database Connection</h2>";

if ($connect->connect_error) {
    echo "<p class='error'>❌ FAILED: " . htmlspecialchars($connect->connect_error) . "</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='success'>✅ Connected to: <code>" . htmlspecialchars(DB_NAME) . "</code> on <code>" . htmlspecialchars(DB_HOST) . "</code></p>";
echo "<p><strong>Server Version:</strong> " . htmlspecialchars($connect->server_info) . "</p>";

// ═══════════════════════════════════════════════════════════════════
// 2. ORDERS TABLE CHECK
// ═══════════════════════════════════════════════════════════════════
echo "<h2>2️⃣ Orders Table</h2>";

$ordersCheck = $connect->query("SHOW COLUMNS FROM `orders`");
if (!$ordersCheck) {
    echo "<p class='error'>❌ Orders table does NOT exist!</p>";
    echo "<p>Error: " . htmlspecialchars($connect->error) . "</p>";
    echo "<p class='warning'>⚠️ You need to run: <code>boycold_db.sql</code></p>";
} else {
    echo "<p class='success'>✅ Orders table exists</p>";
    
    $requiredColumns = [
        'id' => 'INT',
        'user_id' => 'INT',
        'status' => 'ENUM',
        'order_type' => 'ENUM',
        'payment_method' => 'ENUM',
        'payment_status' => 'ENUM',
        'subtotal' => 'DECIMAL',
        'delivery_fee' => 'DECIMAL',
        'tax' => 'DECIMAL',
        'total' => 'DECIMAL',
        'address' => 'VARCHAR',
        'notes' => 'TEXT',
        'created_at' => 'DATETIME',
        'updated_at' => 'DATETIME'
    ];
    
    $columns = [];
    while ($row = $ordersCheck->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<table>";
    echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Default</th><th>Status</th></tr>";
    
    $allPresent = true;
    foreach ($requiredColumns as $colName => $expectedType) {
        if (isset($columns[$colName])) {
            $type = $columns[$colName]['Type'];
            $null = $columns[$colName]['Null'];
            $default = $columns[$colName]['Default'] ?? '(none)';
            echo "<tr>";
            echo "<td><code>$colName</code></td>";
            echo "<td><code>" . htmlspecialchars($type) . "</code></td>";
            echo "<td>$null</td>";
            echo "<td>" . htmlspecialchars($default) . "</td>";
            echo "<td class='success'>✅</td>";
            echo "</tr>";
        } else {
            $allPresent = false;
            echo "<tr>";
            echo "<td><code>$colName</code></td>";
            echo "<td colspan='3' class='error'>❌ MISSING!</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    if (!$allPresent) {
        echo "<p class='error'>❌ Some columns are missing! The orders table schema is incomplete.</p>";
        echo "<p class='warning'>⚠️ Run <code>boycold_db.sql</code> to update the schema.</p>";
    } else {
        echo "<p class='success'>✅ All required columns present</p>";
    }
}

// ═══════════════════════════════════════════════════════════════════
// 3. ORDER_ITEMS TABLE CHECK
// ═══════════════════════════════════════════════════════════════════
echo "<h2>3️⃣ Order Items Table</h2>";

$itemsCheck = $connect->query("SHOW COLUMNS FROM `order_items`");
if (!$itemsCheck) {
    echo "<p class='error'>❌ Order items table does NOT exist!</p>";
    echo "<p>Error: " . htmlspecialchars($connect->error) . "</p>";
    echo "<p class='warning'>⚠️ You need to run: <code>boycold_db.sql</code></p>";
} else {
    echo "<p class='success'>✅ Order items table exists</p>";
    
    $requiredItemColumns = [
        'id' => 'INT',
        'order_id' => 'INT',
        'product_name' => 'VARCHAR',
        'product_image' => 'VARCHAR',
        'unit_price' => 'DECIMAL',
        'quantity' => 'INT',
        'line_total' => 'DECIMAL',
        'milk' => 'VARCHAR',
        'addons' => 'VARCHAR',
        'order_type' => 'VARCHAR',
        'notes' => 'TEXT'
    ];
    
    $itemColumns = [];
    while ($row = $itemsCheck->fetch_assoc()) {
        $itemColumns[$row['Field']] = $row;
    }
    
    echo "<table>";
    echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Default</th><th>Status</th></tr>";
    
    $itemsAllPresent = true;
    foreach ($requiredItemColumns as $colName => $expectedType) {
        if (isset($itemColumns[$colName])) {
            $type = $itemColumns[$colName]['Type'];
            $null = $itemColumns[$colName]['Null'];
            $default = $itemColumns[$colName]['Default'] ?? '(none)';
            echo "<tr>";
            echo "<td><code>$colName</code></td>";
            echo "<td><code>" . htmlspecialchars($type) . "</code></td>";
            echo "<td>$null</td>";
            echo "<td>" . htmlspecialchars($default) . "</td>";
            echo "<td class='success'>✅</td>";
            echo "</tr>";
        } else {
            $itemsAllPresent = false;
            echo "<tr>";
            echo "<td><code>$colName</code></td>";
            echo "<td colspan='3' class='error'>❌ MISSING!</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    if (!$itemsAllPresent) {
        echo "<p class='error'>❌ Some columns are missing!</p>";
        echo "<p class='warning'>⚠️ Run <code>boycold_db.sql</code> to update the schema.</p>";
    } else {
        echo "<p class='success'>✅ All required columns present</p>";
    }
}

// ═══════════════════════════════════════════════════════════════════
// 4. TEST DATA CHECK
// ═══════════════════════════════════════════════════════════════════
echo "<h2>4️⃣ Sample Data Check</h2>";

$orderCount = $connect->query("SELECT COUNT(*) as cnt FROM `orders`");
if ($orderCount) {
    $row = $orderCount->fetch_assoc();
    $count = $row['cnt'];
    echo "<p><strong>Total Orders:</strong> <code>$count</code></p>";
    
    if ($count > 0) {
        echo "<p class='success'>✅ Found existing orders - database is populated</p>";
        
        // Show recent orders
        $recent = $connect->query("
            SELECT 
                o.id, o.user_id, o.status, o.payment_method, 
                o.payment_status, o.total, o.created_at
            FROM orders o
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        
        if ($recent) {
            echo "<table>";
            echo "<tr><th>Order ID</th><th>User ID</th><th>Status</th><th>Payment</th><th>Amount</th><th>Created</th></tr>";
            while ($row = $recent->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "<td>" . htmlspecialchars($row['payment_method']) . " (" . htmlspecialchars($row['payment_status']) . ")</td>";
                echo "<td>₱" . htmlspecialchars($row['total']) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

// ═══════════════════════════════════════════════════════════════════
// 5. SUMMARY
// ═══════════════════════════════════════════════════════════════════
echo "<h2>📋 Summary</h2>";
echo "<div class='info'>";

$allGood = isset($ordersCheck) && isset($itemsCheck) && $allPresent && $itemsAllPresent;

if ($allGood) {
    echo "<p class='success'>✅ ALL CHECKS PASSED - Database is ready!</p>";
    echo "<p>You should now be able to place orders without database errors.</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Try placing an order: <a href='../User/checkout.php' target='_blank'>Go to Checkout</a></li>";
    echo "<li>Check order status: <a href='../User/ordercustom.php' target='_blank'>My Orders</a></li>";
    echo "<li>View admin dashboard: <a href='../admin/dashboard.php' target='_blank'>Admin Dashboard</a></li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ DATABASE ISSUES DETECTED</p>";
    echo "<p><strong>Issues Found:</strong></p>";
    echo "<ul>";
    if (!isset($ordersCheck)) echo "<li>❌ Orders table missing</li>";
    if (!isset($itemsCheck)) echo "<li>❌ Order items table missing</li>";
    if (isset($ordersCheck) && !$allPresent) echo "<li>❌ Orders table has missing columns</li>";
    if (isset($itemsCheck) && !$itemsAllPresent) echo "<li>❌ Order items table has missing columns</li>";
    echo "</ul>";
    echo "<p class='warning'><strong>To Fix:</strong></p>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin/' target='_blank'>http://localhost/phpmyadmin/</a></li>";
    echo "<li>Select database: <code>boycold_db</code></li>";
    echo "<li>Go to <strong>SQL</strong> tab</li>";
    echo "<li>Upload/paste contents of <code>config/boycold_db.sql</code></li>";
    echo "<li>Execute the SQL</li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
}

echo "</div>";

echo "</div>
</body>
</html>";

$connect->close();
?>
