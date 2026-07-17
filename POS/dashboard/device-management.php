<?php
session_name('POS_SESSION');
session_start();
require_once '../config/db_config.php';

// Check if user is logged in and is a super admin (branch_id = 0 or admin role)
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$branchId = isset($_SESSION['branch_id']) ? (int) $_SESSION['branch_id'] : 0;
$employeeRole = $_SESSION['employee_role'] ?? '';

// Only allow super admins (branch_id = 0 or admin role with branch_id = 0)
if ($branchId !== 0 && $employeeRole !== 'admin') {
    header('Location: pos-menu.php');
    exit;
}

// Handle device actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_device') {
        $deviceCode = trim($_POST['device_code'] ?? '');
        $deviceName = trim($_POST['device_name'] ?? '');
        $branchId = (int) ($_POST['branch_id'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        
        if (empty($deviceCode) || empty($deviceName) || $branchId === 0) {
            $error = 'All required fields must be filled.';
        } else {
            $stmt = $connect->prepare("INSERT INTO pos_devices (device_code, device_name, branch_id, location, device_status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->bind_param('ssis', $deviceCode, $deviceName, $branchId, $location);
            if ($stmt->execute()) {
                $success = 'Device added successfully.';
            } else {
                $error = 'Failed to add device. Device code may already exist.';
            }
            $stmt->close();
        }
    } elseif ($action === 'lock_device') {
        $deviceId = (int) ($_POST['device_id'] ?? 0);
        $stmt = $connect->prepare("UPDATE pos_devices SET is_locked = 1 WHERE id = ?");
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $stmt->close();
        $success = 'Device locked successfully.';
    } elseif ($action === 'unlock_device') {
        $deviceId = (int) ($_POST['device_id'] ?? 0);
        $stmt = $connect->prepare("UPDATE pos_devices SET is_locked = 0 WHERE id = ?");
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $stmt->close();
        $success = 'Device unlocked successfully.';
    } elseif ($action === 'force_logout') {
        $deviceId = (int) ($_POST['device_id'] ?? 0);
        
        // Get current employee on device
        $stmt = $connect->prepare("SELECT current_employee_id FROM pos_devices WHERE id = ?");
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $device = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($device && $device['current_employee_id']) {
            $employeeId = $device['current_employee_id'];
            
            // Clear device assignments
            $clearDeviceStmt = $connect->prepare("UPDATE pos_devices SET current_employee_id = NULL, session_id = NULL, last_activity = NULL WHERE id = ?");
            $clearDeviceStmt->bind_param('i', $deviceId);
            $clearDeviceStmt->execute();
            $clearDeviceStmt->close();
            
            $clearEmployeeStmt = $connect->prepare("UPDATE employees SET current_device_id = NULL WHERE id = ?");
            $clearEmployeeStmt->bind_param('i', $employeeId);
            $clearEmployeeStmt->execute();
            $clearEmployeeStmt->close();
            
            $success = 'Force logout successful. Employee has been logged out from the device.';
        } else {
            $error = 'No active session found on this device.';
        }
    } elseif ($action === 'delete_device') {
        $deviceId = (int) ($_POST['device_id'] ?? 0);
        
        // Check if device has active session
        $stmt = $connect->prepare("SELECT current_employee_id FROM pos_devices WHERE id = ?");
        $stmt->bind_param('i', $deviceId);
        $stmt->execute();
        $device = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($device && $device['current_employee_id']) {
            $error = 'Cannot delete device with active session. Force logout first.';
        } else {
            $deleteStmt = $connect->prepare("DELETE FROM pos_devices WHERE id = ?");
            $deleteStmt->bind_param('i', $deviceId);
            $deleteStmt->execute();
            $deleteStmt->close();
            $success = 'Device deleted successfully.';
        }
    }
}

// Fetch all devices with branch and employee info
$devicesStmt = $connect->prepare("
    SELECT 
        pd.id,
        pd.device_code,
        pd.device_name,
        pd.branch_id,
        pd.location,
        pd.device_status,
        pd.current_employee_id,
        pd.session_id,
        pd.last_activity,
        pd.is_locked,
        b.branch_name,
        e.employee_name as current_employee_name,
        e.email as current_employee_email
    FROM pos_devices pd
    LEFT JOIN branches b ON pd.branch_id = b.id
    LEFT JOIN employees e ON pd.current_employee_id = e.id
    ORDER BY pd.branch_id, pd.device_name
");
$devicesStmt->execute();
$devices = $devicesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$devicesStmt->close();

// Fetch all branches for dropdown
$branchStmt = $connect->prepare("SELECT id, branch_name FROM branches WHERE status = 'active' ORDER BY branch_name");
$branchStmt->execute();
$branches = $branchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$branchStmt->close();
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
    <title>BoyCold - Device Management</title>
    <style>
        .device-management-container {
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
        .add-device-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .add-device-form h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Afacad', sans-serif;
        }
        .btn-add {
            padding: 10px 20px;
            background: #6F4E37;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Afacad', sans-serif;
        }
        .btn-add:hover {
            background: #5a3d2d;
        }
        .devices-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .devices-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .devices-table th,
        .devices-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .devices-table th {
            background: #f8f8f8;
            font-weight: 600;
            color: #333;
        }
        .devices-table tr:hover {
            background: #f9f9f9;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-inactive { background: #ffebee; color: #c62828; }
        .status-pending { background: #fff3e0; color: #e65100; }
        .locked-badge {
            background: #ffebee;
            color: #c62828;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-left: 10px;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-lock { background: #ffebee; color: #c62828; }
        .btn-unlock { background: #e8f5e9; color: #2e7d32; }
        .btn-logout { background: #fff3e0; color: #e65100; }
        .btn-delete { background: #ffebee; color: #c62828; }
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .session-info {
            font-size: 12px;
            color: #666;
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
                        <a href="super-admin.php">
                            <span class="nav-icon"><i class="fa-solid fa-chart-line"></i></span>
                            <span class="nav-label">All Branches Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="device-management.php" class="active">
                            <span class="nav-icon"><i class="fa-solid fa-desktop"></i></span>
                            <span class="nav-label">Device Management</span>
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
                        <a href="../auth/logout.php" class="logout-link">
                            <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                            <span class="nav-label">Log Out</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-panel">
            <div class="device-management-container">
                <div class="page-header">
                    <h1>Device Management</h1>
                    <p>Register and manage POS devices across all branches</p>
                </div>

                <?php if (isset($success)): ?>
                    <div class="success-message"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="add-device-form">
                    <h2>Add New Device</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add_device">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="device_code">Device Code *</label>
                                <input type="text" id="device_code" name="device_code" placeholder="e.g., POS-BAL-001" required>
                            </div>
                            <div class="form-group">
                                <label for="device_name">Device Name *</label>
                                <input type="text" id="device_name" name="device_name" placeholder="e.g., Baliuag Counter 1" required>
                            </div>
                            <div class="form-group">
                                <label for="branch_id">Branch *</label>
                                <select id="branch_id" name="branch_id" required>
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= $branch['id'] ?>"><?= htmlspecialchars($branch['branch_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" id="location" name="location" placeholder="e.g., Main Counter">
                            </div>
                        </div>
                        <button type="submit" class="btn-add">Add Device</button>
                    </form>
                </div>

                <div class="devices-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Device Code</th>
                                <th>Device Name</th>
                                <th>Branch</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Current User</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devices as $device): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($device['device_code']) ?></strong>
                                    <?php if ($device['is_locked']): ?>
                                        <span class="locked-badge"><i class="fa-solid fa-lock"></i> Locked</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($device['device_name']) ?></td>
                                <td><?= htmlspecialchars($device['branch_name']) ?></td>
                                <td><?= htmlspecialchars($device['location'] ?? '-') ?></td>
                                <td>
                                    <span class="status-badge status-<?= $device['device_status'] ?>">
                                        <?= ucfirst($device['device_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($device['current_employee_name']): ?>
                                        <div>
                                            <strong><?= htmlspecialchars($device['current_employee_name']) ?></strong>
                                            <div class="session-info"><?= htmlspecialchars($device['current_employee_email']) ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #999;">No active session</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($device['last_activity']): ?>
                                        <?= date('M d, Y H:i', strtotime($device['last_activity'])) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($device['is_locked']): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="unlock_device">
                                                <input type="hidden" name="device_id" value="<?= $device['id'] ?>">
                                                <button type="submit" class="btn-action btn-unlock" title="Unlock Device">
                                                    <i class="fa-solid fa-unlock"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="lock_device">
                                                <input type="hidden" name="device_id" value="<?= $device['id'] ?>">
                                                <button type="submit" class="btn-action btn-lock" title="Lock Device">
                                                    <i class="fa-solid fa-lock"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($device['current_employee_id']): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="force_logout">
                                                <input type="hidden" name="device_id" value="<?= $device['id'] ?>">
                                                <button type="submit" class="btn-action btn-logout" title="Force Logout">
                                                    <i class="fa-solid fa-right-from-bracket"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (!$device['current_employee_id']): ?>
                                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this device?');">
                                                <input type="hidden" name="action" value="delete_device">
                                                <input type="hidden" name="device_id" value="<?= $device['id'] ?>">
                                                <button type="submit" class="btn-action btn-delete" title="Delete Device">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
