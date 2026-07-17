<?php
session_name('POS_SESSION');
session_start();
require_once '../config/db_config.php';

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$employeeId = isset($_SESSION['employee_id']) ? (int) $_SESSION['employee_id'] : 0;
$deviceId = isset($_SESSION['device_id']) ? (int) $_SESSION['device_id'] : 0;

// Check for active shift
$shiftStmt = $connect->prepare("SELECT id FROM shift_logs WHERE employee_id = ? AND device_id = ? AND status = 'open' LIMIT 1");
$shiftStmt->bind_param('ii', $employeeId, $deviceId);
$shiftStmt->execute();
$shiftResult = $shiftStmt->get_result()->fetch_assoc();
$shiftStmt->close();

// If no open shift, redirect to shift page
if (!$shiftResult) {
    header('Location: pos-shift.php');
    exit;
}
?>
