<?php
require_once __DIR__ . '/../db_config.php';

// SQL to create shift_logs table
$sql = "CREATE TABLE IF NOT EXISTS `shift_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `device_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `opening_cash_float` decimal(10,2) NOT NULL DEFAULT 0.00,
  `closing_cash_count` decimal(10,2) DEFAULT NULL,
  `cash_sales` decimal(10,2) DEFAULT 0.00,
  `gcash_sales` decimal(10,2) DEFAULT 0.00,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `cash_orders` int DEFAULT 0,
  `gcash_orders` int DEFAULT 0,
  `total_orders` int DEFAULT 0,
  `cash_difference` decimal(10,2) DEFAULT 0.00,
  `opened_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` timestamp NULL DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_opened_at` (`opened_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($connect->query($sql)) {
    echo "✓ shift_logs table created successfully.\n";
} else {
    echo "✗ Failed to create shift_logs table: " . $connect->error . "\n";
}
