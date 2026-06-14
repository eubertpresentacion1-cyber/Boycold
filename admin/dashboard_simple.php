<?php
/**
 * Admin Dashboard - Simple Order Management
 * View all orders with status filters (All / Pending / Completed / Cancelled)
 */

session_start();
require_once '../config/db_config.php';

// Verify admin access
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

// Check admin status
$stmt = $connect->prepare("SELECT id FROM users WHERE id = ? AND (email LIKE '%admin%' OR email = 'admin@boycold.com')");
$stmt->bind_param("i", $userId);
$stmt->execute();
$isAdmin = $stmt->get_result()->num_rows > 0;

if (!$isAdmin) {
    http_response_code(403);
    die('<h1>❌ Access Denied</h1><p>This page is for admins only.</p>');
}

// Get current filter
$filter = $_GET['filter'] ?? 'all';
$validFilters = ['all', 'pending', 'completed', 'cancelled'];
if (!in_array($filter, $validFilters)) {
    $filter = 'all';
}

// Get admin info
$stmt = $connect->prepare("SELECT firstname, lastname FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$adminName = htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']);

// Build WHERE clause
$whereClause = '1=1';
if ($filter !== 'all') {
    $whereClause = "o.status = '" . $connect->real_escape_string($filter) . "'";
}

// Get orders
$ordersQuery = "
    SELECT 
        o.id, 
        o.user_id, 
        o.status, 
        o.order_type,
        o.payment_method,
        o.payment_status,
        o.total,
        o.created_at,
        u.firstname,
        u.lastname,
        u.email,
        COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE $whereClause
    GROUP BY o.id
    ORDER BY o.created_at DESC
";

$result = $connect->query($ordersQuery);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Get stats
$stats = [];
$statsQuery = "
    SELECT 
        status,
        COUNT(*) as count
    FROM orders
    GROUP BY status
";
$statsResult = $connect->query($statsQuery);
while ($row = $statsResult->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}

$totalOrders = array_sum(array_values($stats));
$pendingCount = $stats['pending'] ?? 0;
$completedCount = $stats['completed'] ?? 0;
$cancelledCount = $stats['cancelled'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #666;
        }

        .admin-info strong {
            color: #333;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.total {
            border-left-color: #667eea;
        }

        .stat-card.pending {
            border-left-color: #ff9800;
        }

        .stat-card.completed {
            border-left-color: #4caf50;
        }

        .stat-card.cancelled {
            border-left-color: #f44336;
        }

        .stat-label {
            font-size: 14px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #333;
        }

        /* Filters */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Orders Table */
        .orders-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .orders-header {
            padding: 20px 25px;
            background: #f8f9fa;
            border-bottom: 2px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .orders-header h2 {
            font-size: 18px;
            color: #333;
        }

        .orders-count {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px 25px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 2px solid #eee;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 25px;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .status-preparing {
            background: #cfe2ff;
            color: #084298;
        }

        .status-ready {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Payment Badges */
        .payment-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-cod {
            background: #e3f2fd;
            color: #1565c0;
        }

        .payment-gcash {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        .payment-unpaid {
            background: #ffebee;
            color: #c62828;
        }

        .payment-paid {
            background: #e8f5e9;
            color: #2e7d32;
        }

        /* Actions */
        .actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn.view {
            background: #e3f2fd;
            color: #1976d2;
        }

        .action-btn.view:hover {
            background: #1976d2;
            color: white;
        }

        .action-btn.complete {
            background: #e8f5e9;
            color: #388e3c;
        }

        .action-btn.complete:hover {
            background: #388e3c;
            color: white;
        }

        .action-btn.cancel {
            background: #ffebee;
            color: #d32f2f;
        }

        .action-btn.cancel:hover {
            background: #d32f2f;
            color: white;
        }

        /* Empty State */
        .empty-state {
            padding: 60px 25px;
            text-align: center;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .admin-info {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .filters {
                justify-content: center;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px 15px;
            }

            .action-btn {
                padding: 6px 10px;
                font-size: 10px;
            }
        }

        /* Logout */
        .logout-btn {
            padding: 10px 20px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .logout-btn:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <div>
            <h1><i class="fas fa-chart-line"></i> Admin Dashboard</h1>
            <p style="color: #999; margin-top: 5px;">Order Management System</p>
        </div>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="admin-info">
                <div>
                    <p style="margin: 0;">Logged in as</p>
                    <p style="margin: 0;"><strong><?php echo $adminName; ?></strong></p>
                </div>
            </div>
            <a href="../login.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-label"><i class="fas fa-receipt"></i> Total Orders</div>
            <div class="stat-number"><?php echo $totalOrders; ?></div>
        </div>
        <div class="stat-card pending">
            <div class="stat-label"><i class="fas fa-clock"></i> Pending</div>
            <div class="stat-number"><?php echo $pendingCount; ?></div>
        </div>
        <div class="stat-card completed">
            <div class="stat-label"><i class="fas fa-check-circle"></i> Completed</div>
            <div class="stat-number"><?php echo $completedCount; ?></div>
        </div>
        <div class="stat-card cancelled">
            <div class="stat-label"><i class="fas fa-times-circle"></i> Cancelled</div>
            <div class="stat-number"><?php echo $cancelledCount; ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <a href="?filter=all">
            <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> All Orders
            </button>
        </a>
        <a href="?filter=pending">
            <button class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-hourglass-start"></i> Pending
            </button>
        </a>
        <a href="?filter=completed">
            <button class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                <i class="fas fa-check"></i> Completed
            </button>
        </a>
        <a href="?filter=cancelled">
            <button class="filter-btn <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">
                <i class="fas fa-ban"></i> Cancelled
            </button>
        </a>
    </div>

    <!-- Orders Table -->
    <div class="orders-container">
        <div class="orders-header">
            <h2><?php echo ucfirst($filter); ?> Orders</h2>
            <span class="orders-count"><?php echo count($orders); ?> orders</span>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No <?php echo $filter !== 'all' ? $filter : ''; ?> orders found</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo htmlspecialchars($order['id']); ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 600;">
                                    <?php echo htmlspecialchars($order['firstname'] . ' ' . $order['lastname']); ?>
                                </div>
                                <div style="font-size: 12px; color: #999;">
                                    <?php echo htmlspecialchars($order['email']); ?>
                                </div>
                            </td>
                            <td>
                                <span style="background: #f0f0f0; padding: 4px 8px; border-radius: 4px;">
                                    <?php echo htmlspecialchars($order['item_count']); ?> items
                                </span>
                            </td>
                            <td>
                                <strong>₱<?php echo number_format($order['total'], 2); ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <span class="payment-badge payment-<?php echo htmlspecialchars($order['payment_method']); ?>">
                                        <?php echo htmlspecialchars(strtoupper($order['payment_method'])); ?>
                                    </span>
                                    <span class="payment-badge payment-<?php echo htmlspecialchars($order['payment_status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 12px; color: #666;">
                                    <?php echo date('M d, H:i', strtotime($order['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="action-btn view" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function viewOrder(orderId) {
    // You can redirect to an order detail page or open a modal
    window.location.href = '../User/orderstatus.php?order_id=' + orderId;
}
</script>
</body>
</html>
