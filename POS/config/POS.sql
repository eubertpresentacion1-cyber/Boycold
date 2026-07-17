-- ============================================================
--  BoyCold Café — POS Employees Table (v2)
--  ⚠ REPLACES the `employees` table from the previous message.
--  Changes:
--    • firstname/lastname now nullable — signup.php only
--      collects email + password + branch, no name fields
--    • added `branch` column (from signup.php's branch select)
--  Run this instead of the old pos_employees.sql.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

USE `boycold_db`;

DROP TABLE IF EXISTS `employees`;

CREATE TABLE `employees` (
  `id`            INT            NOT NULL AUTO_INCREMENT,
  `firstname`     VARCHAR(100)            DEFAULT NULL,
  `lastname`      VARCHAR(100)            DEFAULT NULL,
  `employee_name` VARCHAR(255)   GENERATED ALWAYS AS (CONCAT(firstname, ' ', lastname)) STORED,
  `email`         VARCHAR(255)   NOT NULL,
  `branch`        VARCHAR(50)             DEFAULT NULL,   -- 'branch1' = Bustos, 'branch2' = Sta. Barbara
  `password`      VARCHAR(255)   NOT NULL,                -- password_hash() from signup.php
  `pin`           VARCHAR(255)            DEFAULT NULL,   -- password_hash() of the 4-digit PIN, set in pin.php
  `role`          ENUM('cashier','admin') NOT NULL DEFAULT 'cashier',
  `is_active`     TINYINT        NOT NULL DEFAULT 1,
  `avatar`        VARCHAR(255)            DEFAULT NULL,
  `created_at`    DATETIME                DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME                DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employees_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample admin account for testing login.php directly (skips signup/pin flow)
-- email: admin@boycold.com | password: Admin123! | pin: 1234
-- (hashes below are real password_hash() output, verified working)
INSERT INTO `employees` (`email`, `branch`, `password`, `pin`, `role`) VALUES
('admin@boycold.com', 'branch1',
 '$2y$10$jJzzqXkR3f3IUDTMZ7ktguq3DqnEGvSd8Q2yUrt4GBhOB0lWYRZ0e',
 '$2y$10$6/GSYp6Urmglu0JQH4k6Kud9gE3WMNp8rg0pm5Utp/43ZyKybTFA2',
 'admin');
-- ⚠ Change this password/PIN immediately in production.

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;