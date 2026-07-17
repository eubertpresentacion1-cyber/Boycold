<?php
session_name('POS_SESSION');
session_start();
require_once '../config/db_config.php';

// Session guard — redirect to login if not logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$employeeId = (int) $_SESSION['employee_id'];

// Fetch fresh employee data from DB to validate session
$stmt = $connect->prepare("SELECT id, employee_name, email, is_active, branch_id, role FROM employees WHERE id=?");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

if (!$employee || (int) $employee['is_active'] === 0) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}
$stmt->close();

$branchId = (int) $employee['branch_id'];
$employeeRole = $employee['role'] ?? '';

// Only allow super admins (branch_id = 0 or admin role with branch_id = 0)
if ($branchId !== 0 && $employeeRole !== 'admin') {
    header('Location: pos-menu.php');
    exit;
}

// Fetch all branches
$branchStmt = $connect->prepare("SELECT id, branch_code, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name");
$branchStmt->execute();
$branches = $branchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$branchStmt->close();

// Get date range for analytics (default: last 30 days)
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Combined analytics across all branches
$analytics = [];

// Total orders per branch
foreach ($branches as $branch) {
    $stmt = $connect->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
            SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as total_revenue,
            SUM(total) as gross_revenue
        FROM orders 
        WHERE branch_id = ? 
        AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->bind_param('iss', $branch['id'], $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $analytics[$branch['id']] = [
        'branch_name' => $branch['branch_name'],
        'branch_code' => $branch['branch_code'],
        'total_orders' => (int)($result['total_orders'] ?? 0),
        'completed_orders' => (int)($result['completed_orders'] ?? 0),
        'cancelled_orders' => (int)($result['cancelled_orders'] ?? 0),
        'total_revenue' => (float)($result['total_revenue'] ?? 0),
        'gross_revenue' => (float)($result['gross_revenue'] ?? 0),
    ];
}

// Calculate combined totals
$combinedTotalOrders = array_sum(array_column($analytics, 'total_orders'));
$combinedCompletedOrders = array_sum(array_column($analytics, 'completed_orders'));
$combinedCancelledOrders = array_sum(array_column($analytics, 'cancelled_orders'));
$combinedRevenue = array_sum(array_column($analytics, 'total_revenue'));

// Recent orders across all branches
$recentOrdersStmt = $connect->prepare("
    SELECT o.*, b.branch_name 
    FROM orders o
    LEFT JOIN branches b ON o.branch_id = b.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at DESC
    LIMIT 20
");
$recentOrdersStmt->bind_param('ss', $startDate, $endDate);
$recentOrdersStmt->execute();
$recentOrders = $recentOrdersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$recentOrdersStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dash-css/pos-settings.css">
    <link rel="icon" href="../img/LOGO 2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Gaegu:wght@400;700&display=swap" rel="stylesheet">
    <title>BoyCold - Super Admin Dashboard</title>
    <style>
        .analytics-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .page-header {
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-size: 28px;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        .page-header p {
            color: #666;
            font-size: 14px;
        }
        .date-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            align-items: center;
        }
        .date-filter input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Afacad', sans-serif;
        }
        .date-filter button {
            padding: 10px 20px;
            background: #6F4E37;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Afacad', sans-serif;
        }
        .date-filter button:hover {
            background: #5a3d2d;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .summary-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .summary-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
        }
        .branch-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .branch-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .branch-table th,
        .branch-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .branch-table th {
            background: #f8f8f8;
            font-weight: 600;
            color: #333;
        }
        .branch-table tr:hover {
            background: #f9f9f9;
        }
        .recent-orders {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .recent-orders h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }
        .order-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        .order-info p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-completed { background: #e8f5e9; color: #2e7d32; }
        .status-pending { background: #fff3e0; color: #e65100; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        .branch-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="brand-mark" aria-hidden="true">
                    <img class="logo-light" src="../img/icon2.png" alt="LOGO">
                    <img class="logo-dark" src="../img/ChatGPT Image Jul 1, 2026, 12_58_44 PM 1.png" alt="LOGO">
                </span>
                <span class="brand-text">
                    <span class="brand-name">BoyCold Cafe</span>
                    <span class="brand-sub">Super Admin</span>
                </span>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="super-admin.php" class="active">
                            <span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                            <span class="nav-label">All Branches Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="pos-menu.php">
                            <span class="nav-icon1"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0.5 5C0.367392 5 0.240215 4.94732 0.146447 4.85355C0.0526785 4.75979 0 4.63261 0 4.5V0.5C0 0.367392 0.0526785 0.240215 0.146447 0.146447C0.240215 0.0526785 0.367392 0 0.5 0H4.5C4.63261 0 4.75979 0.0526785 4.85355 0.146447C4.94732 0.240215 5 0.367392 5 0.5V4.5C5 4.63261 4.94732 4.75979 4.85355 4.85355C4.75979 4.94732 4.63261 5 4.5 5H0.5ZM7.5 5C7.36739 5 7.24021 4.94732 7.14645 4.85355C7.05268 4.75979 7 4.63261 7 4.5V0.5C7 0.367392 7.05268 0.240215 7.14645 0.146447C7.24021 0.0526785 7.36739 0 7.5 0H11.5C11.6326 0 11.7598 0.0526785 11.8536 0.146447C11.9473 0.240215 12 0.367392 12 0.5V4.5C12 4.63261 11.9473 4.75979 11.8536 4.85355C11.7598 4.94732 11.6326 5 11.5 5H7.5ZM0.5 12C0.367392 12 0.240215 11.9473 0.146447 11.8536C0.0526785 11.7598 0 11.6326 0 11.5V7.5C0 7.36739 0.0526785 7.24021 0.146447 7.14645C0.240215 7.05268 0.367392 7 0.5 7H4.5C4.63261 7 4.75979 7.05268 4.85355 7.14645C4.94732 7.24021 5 7.36739 5 7.5V11.5C5 11.6326 4.94732 11.7598 4.85355 11.8536C4.75979 11.9473 4.63261 12 4.5 12H0.5ZM7.5 12C7.36739 12 7.24021 11.9473 7.14645 11.8536C7.05268 11.7598 7 11.6326 7 11.5V7.5C7 7.36739 7.05268 7.24021 7.14645 7.14645C7.24021 7.05268 7.36739 7 7.5 7H11.5C11.6326 7 11.7598 7.05268 11.8536 7.14645C11.9473 7.24021 12 7.36739 12 7.5V11.5C12 11.6326 11.9473 11.7598 11.8536 11.8536C11.7598 11.9473 11.6326 12 11.5 12H7.5Z"
                                        fill="currentColor" />
                                </svg></span>
                            <span class="nav-label">Menu</span>
                        </a>
                    </li>
                    <li>
                        <a href="pos-online.php">
                            <span class="nav-icon2"><i class="fa-regular fa-bell"></i></span>
                            <span class="nav-label">Online Orders</span>
                        </a>
                    </li>
                    <li>
                        <a href="pos-history.php">
                            <span class="nav-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                            <span class="nav-label">Order History</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-panel">
            <div class="analytics-container">
                <div class="page-header">
                    <h1>Super Admin Dashboard</h1>
                    <p>Combined analytics across all branches</p>
                </div>

                <div class="date-filter">
                    <form method="GET" action="">
                        <label>From: <input type="date" name="start_date" value="<?= $startDate ?>"></label>
                        <label>To: <input type="date" name="end_date" value="<?= $endDate ?>"></label>
                        <button type="submit">Apply Filter</button>
                    </form>
                </div>

                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total Orders (All Branches)</h3>
                        <div class="value"><?= number_format($combinedTotalOrders) ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Completed Orders</h3>
                        <div class="value"><?= number_format($combinedCompletedOrders) ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Cancelled Orders</h3>
                        <div class="value"><?= number_format($combinedCancelledOrders) ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Total Revenue</h3>
                        <div class="value">₱<?= number_format($combinedRevenue, 2) ?></div>
                    </div>
                </div>

                <div class="branch-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Total Orders</th>
                                <th>Completed</th>
                                <th>Cancelled</th>
                                <th>Total Revenue</th>
                                <th>Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics as $branchId => $data): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($data['branch_name']) ?></strong>
                                    <span class="branch-badge"><?= htmlspecialchars($data['branch_code']) ?></span>
                                </td>
                                <td><?= number_format($data['total_orders']) ?></td>
                                <td><?= number_format($data['completed_orders']) ?></td>
                                <td><?= number_format($data['cancelled_orders']) ?></td>
                                <td>₱<?= number_format($data['total_revenue'], 2) ?></td>
                                <td>
                                    <?php 
                                    $rate = $data['total_orders'] > 0 
                                        ? round(($data['completed_orders'] / $data['total_orders']) * 100, 1) 
                                        : 0;
                                    ?>
                                    <?= $rate ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="recent-orders">
                    <h2>Recent Orders (All Branches)</h2>
                    <?php foreach ($recentOrders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <h4>
                                Order #<?= $order['id'] ?>
                                <span class="branch-badge"><?= htmlspecialchars($order['branch_name'] ?? 'N/A') ?></span>
                            </h4>
                            <p><?= htmlspecialchars($order['user_name']) ?> • <?= date('M d, Y H:i', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div>
                            <span class="order-status status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                            <span style="margin-left: 15px; font-weight: 600;">₱<?= number_format($order['total'], 2) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
