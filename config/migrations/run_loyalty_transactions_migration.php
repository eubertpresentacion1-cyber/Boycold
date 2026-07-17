<?php
require_once __DIR__ . '/../db_config.php';

// SQL to create loyalty_transactions table
$sql = "CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `card_no` varchar(20) NOT NULL,
  `branch_id` int NOT NULL,
  `device_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `transaction_type` enum('bean_award','stamp_award','redemption') DEFAULT 'bean_award',
  `points_awarded` int DEFAULT 1,
  `previous_balance` int DEFAULT 0,
  `new_balance` int DEFAULT 0,
  `order_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_card_no` (`card_no`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($connect->query($sql)) {
    echo "✓ loyalty_transactions table created successfully.\n";
} else {
    echo "✗ Failed to create loyalty_transactions table: " . $connect->error . "\n";
}
