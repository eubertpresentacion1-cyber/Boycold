-- ============================================================
--  BoyCold Café — Updated Database Schema
--  Changes from v1:
--    • Added: orders table (with user_id FK)
--    • Added: order_items table
--    • cart and favorites already have user_id — FKs confirmed
--    • All tables: InnoDB, utf8mb4
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

CREATE DATABASE IF NOT EXISTS `boycold_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `boycold_db`;

-- ────────────────────────────────────────────────────────────
--  TABLE: users  (unchanged)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`          INT           NOT NULL AUTO_INCREMENT,
  `firstname`   VARCHAR(100)  NOT NULL,
  `lastname`    VARCHAR(100)  NOT NULL,
  `email`       VARCHAR(255)  NOT NULL,
  `password`    VARCHAR(255)  NOT NULL,
  `is_verified` TINYINT       NOT NULL DEFAULT 0,
  `phone`       VARCHAR(20)            DEFAULT NULL,
  `address`     VARCHAR(255)           DEFAULT NULL,
  `avatar`      VARCHAR(255)           DEFAULT NULL,
  `card_no`     VARCHAR(20)            DEFAULT NULL,
  `created_at`  DATETIME               DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME               DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: products  (unchanged)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `products` (
  `id`           INT            NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(150)   NOT NULL,
  `description`  TEXT                    DEFAULT NULL,
  `price`        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `image`        VARCHAR(255)            DEFAULT NULL,
  `category`     VARCHAR(80)             DEFAULT NULL,
  `is_available` TINYINT                 DEFAULT 1,
  `created_at`   DATETIME                DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME                DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: cart
--  user_id ensures cart items are per-user.
--  UNIQUE(user_id, product_id) prevents duplicate rows.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cart` (
  `id`         INT      NOT NULL AUTO_INCREMENT,
  `user_id`    INT      NOT NULL,
  `product_id` INT      NOT NULL,
  `quantity`   INT      NOT NULL DEFAULT 1,
  `milk`       VARCHAR(80)       DEFAULT NULL,
  `addons`     VARCHAR(255)      DEFAULT NULL,
  `order_type` VARCHAR(40)       DEFAULT NULL,
  `notes`      TEXT              DEFAULT NULL,
  `created_at` DATETIME          DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME          DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: favorites
--  user_id ensures favorites are per-user.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `favorites` (
  `id`         INT      NOT NULL AUTO_INCREMENT,
  `user_id`    INT      NOT NULL,
  `product_id` INT      NOT NULL,
  `created_at` DATETIME          DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: orders  ← NEW
--  Each order is owned by exactly one user via user_id.
--  Admins query without a WHERE user_id clause to see all.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `id`           INT            NOT NULL AUTO_INCREMENT,
  `user_id`      INT            NOT NULL,               -- FK → users.id
  `status`       ENUM('pending','confirmed','preparing',
                      'ready','delivered','cancelled')
                               NOT NULL DEFAULT 'pending',
  `order_type`   ENUM('dine-in','takeout','delivery')   DEFAULT 'dine-in',
  `subtotal`     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `delivery_fee` DECIMAL(10,2)           DEFAULT 0.00,
  `tax`          DECIMAL(10,2)           DEFAULT 0.00,
  `total`        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `address`      VARCHAR(255)            DEFAULT NULL,
  `notes`        TEXT                    DEFAULT NULL,
  `created_at`   DATETIME                DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME                DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: order_items  ← NEW
--  Line items for each order (snapshot of product at order time).
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`           INT            NOT NULL AUTO_INCREMENT,
  `order_id`     INT            NOT NULL,               -- FK → orders.id
  `product_name` VARCHAR(150)   NOT NULL,
  `product_image`VARCHAR(255)            DEFAULT NULL,
  `unit_price`   DECIMAL(10,2)  NOT NULL,
  `quantity`     INT            NOT NULL DEFAULT 1,
  `line_total`   DECIMAL(10,2)  NOT NULL,
  `milk`         VARCHAR(80)             DEFAULT NULL,
  `addons`       VARCHAR(255)            DEFAULT NULL,
  `order_type`   VARCHAR(40)             DEFAULT NULL,
  `notes`        TEXT                    DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  FOREIGN KEY CONSTRAINTS
-- ────────────────────────────────────────────────────────────
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE;
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE;

ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_fav_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE;
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_fav_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE;

-- ────────────────────────────────────────────────────────────
--  OTP TABLE  (unchanged)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `otp` (
  `id`         INT                            NOT NULL AUTO_INCREMENT,
  `firstname`  VARCHAR(100)                            DEFAULT NULL,
  `lastname`   VARCHAR(100)                            DEFAULT NULL,
  `email`      VARCHAR(255)                   NOT NULL,
  `password`   VARCHAR(255)                            DEFAULT NULL,
  `otp`        VARCHAR(6)                     NOT NULL,
  `type`       ENUM('register','reset')       NOT NULL DEFAULT 'register',
  `status`     ENUM('pending','verified','expired')    DEFAULT 'pending',
  `attempts`   INT                                     DEFAULT 0,
  `otp_sent`   DATETIME                                DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME                                DEFAULT NULL,
  `ip`         VARCHAR(45)                             DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER IF NOT EXISTS `set_otp_expiry`
BEFORE INSERT ON `otp`
FOR EACH ROW BEGIN
  SET NEW.expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE);
END $$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;