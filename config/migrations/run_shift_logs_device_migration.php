<?php
require_once __DIR__ . '/../db_config.php';

// SQL to make device_id nullable in shift_logs table
$sql = "ALTER TABLE shift_logs MODIFY COLUMN device_id int DEFAULT NULL";

if ($connect->query($sql)) {
    echo "✓ shift_logs table updated to make device_id nullable.\n";
} else {
    echo "✗ Failed to update shift_logs table: " . $connect->error . "\n";
}
