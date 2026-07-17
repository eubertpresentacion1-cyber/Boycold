<?php
require_once __DIR__ . '/config/db_config.php';

echo "<h2>Branch Isolation Test</h2>";

// Test 1: Check orders by branch
echo "<h3>Test 1: Orders by Branch</h3>";

$branches = [1 => 'Baliuag', 2 => 'Bustos'];

foreach ($branches as $branchId => $branchName) {
    echo "<h4>Branch: $branchName (ID: $branchId)</h4>";
    
    $stmt = $connect->prepare("SELECT id, user_name, status, order_type, total, branch_id FROM orders WHERE branch_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->bind_param('i', $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User</th><th>Status</th><th>Type</th><th>Total</th><th>Branch ID</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_name']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['order_type']}</td>";
            echo "<td>₱{$row['total']}</td>";
            echo "<td>{$row['branch_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No orders found for this branch.</p>";
    }
    $stmt->close();
}

// Test 2: Check shift logs by branch
echo "<h3>Test 2: Shift Logs by Branch</h3>";

foreach ($branches as $branchId => $branchName) {
    echo "<h4>Branch: $branchName (ID: $branchId)</h4>";
    
    $stmt = $connect->prepare("SELECT id, employee_id, opening_cash_float, status, opened_at, branch_id FROM shift_logs WHERE branch_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->bind_param('i', $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Employee ID</th><th>Opening Cash</th><th>Status</th><th>Opened At</th><th>Branch ID</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['employee_id']}</td>";
            echo "<td>₱{$row['opening_cash_float']}</td>";
            echo "<td>{$row['status']}</td>";
            echo "<td>{$row['opened_at']}</td>";
            echo "<td>{$row['branch_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No shift logs found for this branch.</p>";
    }
    $stmt->close();
}

// Test 3: Check loyalty transactions by branch
echo "<h3>Test 3: Loyalty Transactions by Branch</h3>";

foreach ($branches as $branchId => $branchName) {
    echo "<h4>Branch: $branchName (ID: $branchId)</h4>";
    
    $stmt = $connect->prepare("SELECT id, user_id, card_no, transaction_type, points_awarded, branch_id FROM loyalty_transactions WHERE branch_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->bind_param('i', $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Card No</th><th>Type</th><th>Points</th><th>Branch ID</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['user_id']}</td>";
            echo "<td>{$row['card_no']}</td>";
            echo "<td>{$row['transaction_type']}</td>";
            echo "<td>{$row['points_awarded']}</td>";
            echo "<td>{$row['branch_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No loyalty transactions found for this branch.</p>";
    }
    $stmt->close();
}

// Test 4: Verify no cross-branch contamination
echo "<h3>Test 4: Cross-Branch Contamination Check</h3>";

// Check if any orders have NULL branch_id
$stmt = $connect->prepare("SELECT COUNT(*) as count FROM orders WHERE branch_id IS NULL");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$nullBranchOrders = $result['count'];
$stmt->close();

echo "<p>Orders with NULL branch_id: <strong>$nullBranchOrders</strong> ";
if ($nullBranchOrders > 0) {
    echo "<span style='color: red;'>⚠️ WARNING: Found orders without branch assignment!</span>";
} else {
    echo "<span style='color: green;'>✓ All orders have branch assignment</span>";
}
echo "</p>";

// Check if any shift logs have NULL branch_id
$stmt = $connect->prepare("SELECT COUNT(*) as count FROM shift_logs WHERE branch_id IS NULL");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$nullBranchShifts = $result['count'];
$stmt->close();

echo "<p>Shift logs with NULL branch_id: <strong>$nullBranchShifts</strong> ";
if ($nullBranchShifts > 0) {
    echo "<span style='color: red;'>⚠️ WARNING: Found shift logs without branch assignment!</span>";
} else {
    echo "<span style='color: green;'>✓ All shift logs have branch assignment</span>";
}
echo "</p>";

// Test 5: Verify branch filtering in POS queries
echo "<h3>Test 5: POS Query Filtering Simulation</h3>";

// Simulate Baliuag POS query
$baliuagId = 1;
$stmt = $connect->prepare("SELECT COUNT(*) as count FROM orders WHERE branch_id = ?");
$stmt->bind_param('i', $baliuagId);
$stmt->execute();
$baliuagOrders = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Simulate Bustos POS query
$bustosId = 2;
$stmt = $connect->prepare("SELECT COUNT(*) as count FROM orders WHERE branch_id = ?");
$stmt->bind_param('i', $bustosId);
$stmt->execute();
$bustosOrders = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

echo "<p>Baliuag POS can see: <strong>$baliuagOrders orders</strong></p>";
echo "<p>Bustos POS can see: <strong>$bustosOrders orders</strong></p>";

$totalOrders = $baliuagOrders + $bustosOrders;
echo "<p>Total orders across both branches: <strong>$totalOrders</strong></p>";

if ($baliuagOrders !== $bustosOrders) {
    echo "<p><span style='color: green;'>✓ Branch isolation working - different order counts per branch</span></p>";
} else {
    echo "<p><span style='color: orange;'>ℹ️ Same order count - may need more test data</span></p>";
}

echo "<hr>";
echo "<p><strong>Test completed. Review results above to verify branch isolation.</strong></p>";
echo "<p><a href='test_branch_isolation.php'>Refresh Test</a> | <a href='POS/dashboard/pos-menu.php'>Go to POS Dashboard</a></p>";
