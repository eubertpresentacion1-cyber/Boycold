<?php
// ── admin/dashboard.php ──────────────────────────────────────────
// Simple admin dashboard to view and manage COD orders

session_start();
require_once '../config/db_config.php';

// Check if user is admin (you'll need to set is_admin flag in users table)
// For now, we'll check if user_id matches a hardcoded admin ID or use a session flag
$userId = $_SESSION['user_id'] ?? 0;

// Get is_admin from database or session
$stmt = $connect->prepare("SELECT id FROM users WHERE id = ? AND (email LIKE '%admin%' OR email = 'admin@boycold.com')");
$stmt->bind_param("i", $userId);
$stmt->execute();
$isAdmin = $stmt->get_result()->num_rows > 0;

if (!$isAdmin) {
    http_response_code(403);
    die('Access Denied. Admin only.');
}

// Get user info
$stmt = $connect->prepare("SELECT firstname, lastname, email, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$fullName = htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
$avatar = $user['avatar'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BoyCold Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Afacad', sans-serif;
            background: #f5f5f5;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 250px;
            background: #1a1a2e;
            color: white;
            padding: 20px;
            overflow-y: auto;
        }

        .admin-sidebar h2 {
            color: #e8c547;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .admin-sidebar nav ul {
            list-style: none;
        }

        .admin-sidebar nav li {
            margin-bottom: 10px;
        }

        .admin-sidebar nav a {
            color: #ccc;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .admin-sidebar nav a:hover,
        .admin-sidebar nav a.active {
            background: #e8c547;
            color: #1a1a2e;
        }

        .admin-main {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .admin-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .admin-header h1 {
            font-size: 28px;
            color: #1a1a2e;
        }

        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .orders-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th {
            background: #1a1a2e;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .orders-table tr:hover {
            background: #f9f9f9;
        }

        .order-id {
            color: #e8c547;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending { background: #ffe6e6; color: #cc0000; }
        .status-preparing { background: #fff3cd; color: #856404; }
        .status-ready { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-completed { background: #d4edda; color: #155724; }

        .payment-method {
            font-weight: 600;
            color: #e8c547;
        }

        .payment-status-unpaid { color: #cc0000; }
        .payment-status-paid { color: #00aa00; }

        .action-btn {
            padding: 6px 12px;
            margin: 0 3px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: 0.3s;
        }

        .action-btn-view {
            background: #e8c547;
            color: #1a1a2e;
        }

        .action-btn-view:hover {
            background: #d4af00;
        }

        .action-btn-update {
            background: #4CAF50;
            color: white;
        }

        .action-btn-update:hover {
            background: #45a049;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #e8c547;
            padding-bottom: 10px;
        }

        .modal-header h2 {
            color: #1a1a2e;
        }

        .modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .modal-close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #1a1a2e;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Afacad', sans-serif;
        }

        .form-group button {
            padding: 10px 20px;
            background: #e8c547;
            color: #1a1a2e;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .form-group button:hover {
            background: #d4af00;
        }

        .filters {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .filters select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Afacad', sans-serif;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .admin-stat {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .admin-stat div:first-child {
            font-size: 28px;
            color: #e8c547;
            font-weight: 600;
        }

        .admin-stat div:last-child {
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="admin-container">
        <!-- SIDEBAR -->
        <div class="admin-sidebar">
            <h2>☕ Admin</h2>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>

        <!-- MAIN CONTENT -->
        <div class="admin-main">
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <div class="admin-user-info">
                    <?php if ($avatar): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="avatar">
                    <?php else: ?>
                        <i class="fas fa-user fa-2x"></i>
                    <?php endif; ?>
                    <div>
                        <div><?= $fullName ?></div>
                        <small>Administrator</small>
                    </div>
                </div>
            </div>

            <!-- STATS -->
            <div class="stat-grid">
                <div class="admin-stat">
                    <div id="stat-pending">-</div>
                    <div>Pending Orders</div>
                </div>
                <div class="admin-stat">
                    <div id="stat-preparing">-</div>
                    <div>Preparing</div>
                </div>
                <div class="admin-stat">
                    <div id="stat-delivered">-</div>
                    <div>Delivered Today</div>
                </div>
                <div class="admin-stat">
                    <div id="stat-cod">-</div>
                    <div>COD Unpaid</div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="filters">
                <label>Filter by Status:</label>
                <select id="filterStatus" onchange="loadOrders()">
                    <option value="">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="delivered">Delivered</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <!-- ORDERS TABLE -->
            <div class="orders-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Order Type</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <tr><td colspan="8" style="text-align: center; padding: 30px; color: #999;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- UPDATE ORDER STATUS MODAL -->
    <div class="modal" id="updateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Order Status</h2>
                <span class="modal-close" onclick="closeUpdateModal()">&times;</span>
            </div>
            <div class="form-group">
                <label>Order ID: <span id="updateOrderId"></span></label>
            </div>
            <div class="form-group">
                <label>New Status:</label>
                <select id="updateStatus">
                    <option value="pending">Pending</option>
                    <option value="preparing">Preparing</option>
                    <option value="ready">Ready</option>
                    <option value="delivered">Delivered</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="form-group">
                <button onclick="submitStatusUpdate()">Update Status</button>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '../api/orders_api.php';
        let currentEditingOrderId = null;

        async function loadOrders() {
            const status = document.getElementById('filterStatus')?.value || '';
            try {
                const response = await fetch(`${API_URL}?action=list&all=1${status ? '&status=' + status : ''}`);
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('ordersTableBody');
                    tbody.innerHTML = '';
                    
                    if (!data.orders || data.orders.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 30px; color: #999;">No orders found</td></tr>';
                        return;
                    }
                    
                    data.orders.forEach(order => {
                        const row = document.createElement('tr');
                        const createdDate = new Date(order.created_at).toLocaleDateString();
                        
                        const customerName = order.firstname ? `${order.firstname} ${order.lastname}` : 'Unknown';
                        const paymentDisplay = `${order.payment_method.toUpperCase()} (${order.payment_status.toUpperCase()})`;
                        
                        row.innerHTML = `
                            <td><span class="order-id">#${order.id}</span></td>
                            <td>${customerName}</td>
                            <td>${order.order_type}</td>
                            <td>₱${parseFloat(order.total).toFixed(2)}</td>
                            <td><span class="payment-method">${paymentDisplay}</span></td>
                            <td><span class="status-badge status-${order.status}">${order.status.toUpperCase()}</span></td>
                            <td>${createdDate}</td>
                            <td>
                                <button class="action-btn action-btn-update" onclick="openUpdateModal(${order.id}, '${order.status}')">Update</button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                    
                    updateStats(data.orders);
                } else {
                    alert('Error loading orders: ' + data.error);
                }
            } catch (err) {
                console.error('Error:', err);
                document.getElementById('ordersTableBody').innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 30px; color: #cc0000;">Error loading orders</td></tr>';
            }
        }

        function updateStats(orders) {
            const stats = {
                pending: 0,
                preparing: 0,
                delivered: 0,
                cod_unpaid: 0
            };
            
            orders.forEach(order => {
                if (order.status === 'pending') stats.pending++;
                if (order.status === 'preparing') stats.preparing++;
                if (order.status === 'delivered') stats.delivered++;
                if (order.payment_method === 'cod' && order.payment_status === 'unpaid') stats.cod_unpaid++;
            });
            
            document.getElementById('stat-pending').textContent = stats.pending;
            document.getElementById('stat-preparing').textContent = stats.preparing;
            document.getElementById('stat-delivered').textContent = stats.delivered;
            document.getElementById('stat-cod').textContent = stats.cod_unpaid;
        }

        function openUpdateModal(orderId, currentStatus) {
            currentEditingOrderId = orderId;
            document.getElementById('updateOrderId').textContent = '#' + orderId;
            document.getElementById('updateStatus').value = currentStatus;
            document.getElementById('updateModal').classList.add('active');
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').classList.remove('active');
            currentEditingOrderId = null;
        }

        async function submitStatusUpdate() {
            if (!currentEditingOrderId) return;
            
            const newStatus = document.getElementById('updateStatus').value;
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update_status',
                        order_id: currentEditingOrderId,
                        status: newStatus
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Order status updated successfully!');
                    closeUpdateModal();
                    loadOrders();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (err) {
                alert('Network error: ' + err.message);
            }
        }

        // Load orders on page load
        document.addEventListener('DOMContentLoaded', loadOrders);
    </script>

</body>
</html>
