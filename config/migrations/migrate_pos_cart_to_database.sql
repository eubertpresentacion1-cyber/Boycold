-- Migration: Migrate POS cart and receipt data from localStorage to database
-- Date: 2026-07-17
-- Description: Create tables to store POS cart data and receipt counters in database

-- Table: pos_cart
-- Stores temporary cart items for POS sessions
CREATE TABLE IF NOT EXISTS `pos_cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `product_id` varchar(100) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `milk` varchar(80) DEFAULT NULL,
  `milk_price` decimal(10,2) DEFAULT 0.00,
  `addons` json DEFAULT NULL,
  `order_type` varchar(40) DEFAULT 'Dine In',
  `quantity` int NOT NULL DEFAULT 1,
  `item_total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_session` (`employee_id`, `session_id`),
  KEY `idx_branch_session` (`branch_id`, `session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: receipt_counters
-- Stores receipt number counters per branch
CREATE TABLE IF NOT EXISTS `receipt_counters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `counter` int NOT NULL DEFAULT 0,
  `prefix` varchar(10) DEFAULT 'BC',
  `last_used_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initialize receipt counters for existing branches
INSERT IGNORE INTO `receipt_counters` (`branch_id`, `counter`, `prefix`)
SELECT `id`, 0, 'BC' FROM `branches`;

-- Table: pos_sessions
-- Tracks active POS sessions for cart management
CREATE TABLE IF NOT EXISTS `pos_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `shift_id` int DEFAULT NULL,
  `status` enum('active','completed','abandoned') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_session_id` (`session_id`),
  KEY `idx_employee_status` (`employee_id`, `status`),
  KEY `idx_branch_status` (`branch_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
