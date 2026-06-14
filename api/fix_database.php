<?php
/**
 * Fix Missing Database Columns
 * 
 * Adds missing payment_method and payment_status columns to orders table
 */

require_once '../config/db_config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .error { color: #dc3545; font-weight: bold; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .warning { color: #ff9800; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; margin: 15px 0; }
        code { background: #f4f4f4; padding: 5px 10px; border-radius: 3px; font-family: monospace; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f9f9f9; }
        .step h3 { margin-top: 0; color: #0056b3; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Database Fix Tool</h1>";

// Check if we're applying the fix
$apply = $_GET['apply'] ?? false;

if ($apply === 'true') {
    echo "<h2>Applying Fix...</h2>";
    
    $errors = [];
    $success = [];
    
    // ════════════════════════════════════════════════════════════════
    // Step 1: Add payment_method column
    // ════════════════════════════════════════════════════════════════
    echo "<div class='step'>";
    echo "<h3>Step 1/2: Adding payment_method column...</h3>";
    
    // First check if column already exists
    $checkCol = $connect->query("SHOW COLUMNS FROM orders WHERE Field = 'payment_method'");
    if ($checkCol && $checkCol->num_rows > 0) {
        echo "<p class='warning'>⚠️ Column payment_method already exists - skipping</p>";
        $success[] = "payment_method column already exists";
    } else {
        $sql = "ALTER TABLE `orders` 
                ADD COLUMN `payment_method` ENUM('cod','gcash') DEFAULT 'cod' 
                AFTER `order_type`";
        
        if ($connect->query($sql)) {
            echo "<p class='success'>✅ Added payment_method column</p>";
            $success[] = "Added payment_method column";
        } else {
            echo "<p class='error'>❌ Failed to add payment_method: " . htmlspecialchars($connect->error) . "</p>";
            $errors[] = $connect->error;
        }
    }
    echo "</div>";
    
    // ════════════════════════════════════════════════════════════════
    // Step 2: Add payment_status column
    // ════════════════════════════════════════════════════════════════
    echo "<div class='step'>";
    echo "<h3>Step 2/2: Adding payment_status column...</h3>";
    
    // First check if column already exists
    $checkCol = $connect->query("SHOW COLUMNS FROM orders WHERE Field = 'payment_status'");
    if ($checkCol && $checkCol->num_rows > 0) {
        echo "<p class='warning'>⚠️ Column payment_status already exists - skipping</p>";
        $success[] = "payment_status column already exists";
    } else {
        $sql = "ALTER TABLE `orders` 
                ADD COLUMN `payment_status` ENUM('unpaid','paid','cancelled') DEFAULT 'unpaid' 
                AFTER `payment_method`";
        
        if ($connect->query($sql)) {
            echo "<p class='success'>✅ Added payment_status column</p>";
            $success[] = "Added payment_status column";
        } else {
            echo "<p class='error'>❌ Failed to add payment_status: " . htmlspecialchars($connect->error) . "</p>";
            $errors[] = $connect->error;
        }
    }
    echo "</div>";
    
    // ════════════════════════════════════════════════════════════════
    // Summary
    // ════════════════════════════════════════════════════════════════
    echo "<h2>Summary</h2>";
    
    if (empty($errors)) {
        echo "<div class='success'>";
        echo "<p><strong>✅ SUCCESS! Database has been fixed.</strong></p>";
        echo "<p><strong>Changes Applied:</strong></p>";
        echo "<ul>";
        foreach ($success as $msg) {
            echo "<li>✅ $msg</li>";
        }
        echo "</ul>";
        echo "<p><strong>What's Next:</strong></p>";
        echo "<ol>";
        echo "<li><a href='test_db_schema.php'><strong>Verify the fix</strong></a> - Run diagnostic again</li>";
        echo "<li><a href='../User/checkout.php'><strong>Place an order</strong></a> - Try checkout</li>";
        echo "<li><a href='../User/ordercustom.php'><strong>Check your orders</strong></a> - View status</li>";
        echo "<li><a href='../admin/dashboard.php'><strong>View as admin</strong></a> - Manage orders</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<p><strong>❌ FAILED - Some errors occurred:</strong></p>";
        echo "<ul>";
        foreach ($errors as $err) {
            echo "<li>❌ " . htmlspecialchars($err) . "</li>";
        }
        echo "</ul>";
        echo "<p><strong>Please try again or contact support.</strong></p>";
        echo "</div>";
    }
    
} else {
    // Show the option to apply the fix
    echo "<div class='warning'>";
    echo "<h2>⚠️ Missing Columns Detected</h2>";
    echo "<p>Your database is missing these critical columns:</p>";
    echo "<ul>";
    echo "<li>❌ <code>payment_method</code> - Tracks COD vs GCash</li>";
    echo "<li>❌ <code>payment_status</code> - Tracks unpaid vs paid</li>";
    echo "</ul>";
    echo "<p><strong>Impact:</strong> Order placement will fail with 500 error.</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>How to Fix</h3>";
    echo "<p>This tool will safely add the missing columns to your existing orders table.</p>";
    echo "<p><strong>⏱️ Time required:</strong> Less than 1 second</p>";
    echo "<p><strong>⚠️ Important:</strong> Backup your database first (if it has important data)</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>Ready?</h3>";
    echo "<p>Click the button below to add the missing columns:</p>";
    echo "<form method='GET'>";
    echo "<input type='hidden' name='apply' value='true'>";
    echo "<button type='submit' onclick=\"return confirm('Are you sure? This will add columns to the orders table.');\" style='background: #28a745; font-size: 18px; padding: 15px 30px;'>✅ Fix Database Now</button>";
    echo "</form>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>What This Does</h3>";
    echo "<p><strong>Column 1: payment_method</strong></p>";
    echo "<code>ALTER TABLE orders ADD COLUMN payment_method ENUM('cod','gcash') DEFAULT 'cod' AFTER order_type;</code>";
    echo "<p><strong>Column 2: payment_status</strong></p>";
    echo "<code>ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','paid','cancelled') DEFAULT 'unpaid' AFTER payment_method;</code>";
    echo "<p>Both columns will be added safely without affecting existing data.</p>";
    echo "</div>";
}

echo "</div>
</body>
</html>";

$connect->close();
?>
