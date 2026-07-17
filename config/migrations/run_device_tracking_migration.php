<?php
require_once __DIR__ . '/../db_config.php';

// Function to check if column exists
function columnExists($connect, $table, $column) {
    $result = $connect->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Add session tracking columns to pos_devices
$columnsToAdd = [
    'current_employee_id' => "ADD COLUMN `current_employee_id` int DEFAULT NULL AFTER `device_status`",
    'session_id' => "ADD COLUMN `session_id` varchar(255) DEFAULT NULL AFTER `current_employee_id`",
    'last_activity' => "ADD COLUMN `last_activity` timestamp NULL DEFAULT NULL AFTER `session_id`",
    'is_locked' => "ADD COLUMN `is_locked` tinyint DEFAULT '0' AFTER `last_activity`"
];

foreach ($columnsToAdd as $column => $sql) {
    if (!columnExists($connect, 'pos_devices', $column)) {
        $fullSql = "ALTER TABLE `pos_devices` $sql";
        if ($connect->query($fullSql)) {
            echo "✓ Added $column to pos_devices table\n";
        } else {
            echo "✗ Failed to add $column to pos_devices: " . $connect->error . "\n";
        }
    } else {
        echo "✓ $column already exists in pos_devices table\n";
    }
}

// Add indexes for pos_devices
$indexesToAdd = [
    'idx_current_employee' => "ADD INDEX `idx_current_employee` (`current_employee_id`)",
    'idx_session_id' => "ADD INDEX `idx_session_id` (`session_id`)"
];

foreach ($indexesToAdd as $indexName => $sql) {
    $checkIndex = $connect->query("SHOW INDEX FROM `pos_devices` WHERE Key_name = '$indexName'");
    if ($checkIndex && $checkIndex->num_rows == 0) {
        $fullSql = "ALTER TABLE `pos_devices` $sql";
        if ($connect->query($fullSql)) {
            echo "✓ Added index $indexName to pos_devices table\n";
        } else {
            echo "✗ Failed to add index $indexName: " . $connect->error . "\n";
        }
    } else {
        echo "✓ Index $indexName already exists in pos_devices table\n";
    }
}

// Add device_id to employees table
$employeeColumns = [
    'current_device_id' => "ADD COLUMN `current_device_id` int DEFAULT NULL AFTER `branch_id`",
    'last_login_device_id' => "ADD COLUMN `last_login_device_id` int DEFAULT NULL AFTER `current_device_id`"
];

foreach ($employeeColumns as $column => $sql) {
    if (!columnExists($connect, 'employees', $column)) {
        $fullSql = "ALTER TABLE `employees` $sql";
        if ($connect->query($fullSql)) {
            echo "✓ Added $column to employees table\n";
        } else {
            echo "✗ Failed to add $column to employees: " . $connect->error . "\n";
        }
    } else {
        echo "✓ $column already exists in employees table\n";
    }
}

// Add index for employees
if (!columnExists($connect, 'employees', 'current_device_id')) {
    $checkIndex = $connect->query("SHOW INDEX FROM `employees` WHERE Key_name = 'idx_current_device'");
    if ($checkIndex && $checkIndex->num_rows == 0) {
        $fullSql = "ALTER TABLE `employees` ADD INDEX `idx_current_device` (`current_device_id`)";
        if ($connect->query($fullSql)) {
            echo "✓ Added index idx_current_device to employees table\n";
        } else {
            echo "✗ Failed to add index idx_current_device: " . $connect->error . "\n";
        }
    }
}

echo "\nDevice tracking migration completed.\n";
