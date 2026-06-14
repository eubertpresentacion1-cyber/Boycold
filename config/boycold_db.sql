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
  `id`           INT      NOT NULL AUTO_INCREMENT,
  `user_id`      INT      NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity`     INT      NOT NULL DEFAULT 1,
  `milk`         VARCHAR(80)       DEFAULT NULL,
  `addons`       VARCHAR(255)      DEFAULT NULL,
  `order_type`   VARCHAR(40)       DEFAULT NULL,
  `notes`        TEXT              DEFAULT NULL,
  `created_at`   DATETIME          DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME          DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_user_product` (`user_id`, `product_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
--  TABLE: favorites
--  user_id ensures favorites are per-user.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `favorites` (
  `id`           INT      NOT NULL AUTO_INCREMENT,
  `user_id`      INT      NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `created_at`   DATETIME          DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fav_user_product` (`user_id`, `product_name`)
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
  `payment_method` ENUM('cod','gcash')                 DEFAULT 'cod',
  `payment_status` ENUM('unpaid','paid','cancelled')    DEFAULT 'unpaid',
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

-- ────────────────────────────────────────────────────────────
--  SAMPLE DATA: PRODUCTS
-- ────────────────────────────────────────────────────────────
INSERT INTO `products` (`product_name`, `description`, `price`, `image`, `category`, `is_available`) VALUES
-- Coffee
('Americano', 'Classic Americano', 69.00, '/picture/Americano.png', 'coffee', 1),
('Cafe Latte', 'Smooth and creamy latte', 85.00, '/picture/Cafe Latte.png', 'coffee', 1),
('Spanish Latte', 'Rich condensed milk latte', 95.00, '/picture/Spanish Latte.png', 'coffee', 1),
('Dark Mocha', 'Bold mocha blend', 99.00, '/picture/Dark Mocha.png', 'coffee', 1),
('White Mocha', 'Smooth white chocolate mocha', 99.00, '/picture/White Mocha.png', 'coffee', 1),
('Caramel Macchiato', 'Caramel layered macchiato', 89.00, '/picture/Caramel Macchiato.png', 'coffee', 1),
('Hazelnut Latte', 'Nutty hazelnut latte', 85.00, '/picture/Hazelnut Latte.png', 'coffee', 1),
('Tiramisu Latte', 'Tiramisu flavored latte', 95.00, '/picture/Tiramisu Latte.png', 'coffee', 1),

-- Special Coffee
('Sea Salt Latte', 'Savory sea salt latte', 115.00, '/picture/Sea salt Latte.png', 'special-coffee', 1),
('Salted Mango Dream', 'Mango with a hint of salt', 139.00, '/picture/Salted Mango Dream.png', 'special-coffee', 1),
('Biscoff Creamy Latte', 'Biscoff cookie latte', 109.00, '/picture/Biscoff Creamy Latte.png', 'special-coffee', 1),
('Butter scotch latte', 'Butterscotch flavored latte', 105.00, '/picture/Butter scotch latte.png', 'special-coffee', 1),
('Nutella Hazelnut latte', 'Nutella and hazelnut blend', 99.00, '/picture/Nutella Hazelnut latte.png', 'special-coffee', 1),
('Salted Caramel', 'Salted caramel delight', 99.00, '/picture/Salted Caramel.png', 'special-coffee', 1),
('Salted Macadamia', 'Macadamia nut with sea salt', 119.00, '/picture/Salted Macadamia.png', 'special-coffee', 1),

-- Matcha Fusion
('Pure matcha', 'Traditional pure matcha', 85.00, '/picture/Pure matcha.png', 'matcha-fusion', 1),
('Dirty Matcha', 'Matcha with espresso', 119.00, '/picture/Dirty Matcha.png', 'matcha-fusion', 1),
('Matcha Latte', 'Creamy matcha latte', 95.00, '/picture/Matcha Latte.png', 'matcha-fusion', 1),
('Cheesecake Matcha', 'Cheesecake matcha fusion', 125.00, '/picture/Cheesecake Matcha.png', 'matcha-fusion', 1),
('Choco Matcha', 'Chocolate matcha blend', 105.00, '/picture/Choco Matcha.png', 'matcha-fusion', 1),
('Lavender Matcha', 'Lavender infused matcha', 109.00, '/picture/Lavender Matcha.png', 'matcha-fusion', 1),
('Strawberry Matcha', 'Strawberry matcha fusion', 105.00, '/picture/Strawberry Matcha.png', 'matcha-fusion', 1),
('Seasalt Matcha', 'Matcha with sea salt', 99.00, '/picture/Seasalt Matcha.png', 'matcha-fusion', 1),
('Matcha Frappe', 'Cold matcha frappe', 99.00, '/picture/Matcha Frappe.png', 'matcha-fusion', 1),
('Matcha Freddo', 'Iced matcha freddo', 89.00, '/picture/Matcha Freddo.png', 'matcha-fusion', 1),
('Matcha banana Pudding', 'Matcha with banana', 119.00, '/picture/Matcha banana Pudding.png', 'matcha-fusion', 1),
('Matcha waffle', 'Matcha flavored waffle', 139.00, '/picture/Matcha waffle.png', 'matcha-fusion', 1),

-- Fruit Shake
('Strawberry Milk', 'Fresh strawberry milk shake', 79.00, '/picture/Strawberry Milk.png', 'fruit-shake', 1),
('Blueberry Milk', 'Blueberry milk shake', 79.00, '/picture/Blueberry Milk.png', 'fruit-shake', 1),
('BLUEBERRY SHAKE', 'Premium blueberry shake', 85.00, '/picture/BLUEBERRY SHAKE 1.png', 'fruit-shake', 1),
('Strawberry shake', 'Fresh strawberry shake', 79.00, '/picture/Strawberry shake.png', 'fruit-shake', 1),
('Mango graham', 'Mango with graham', 89.00, '/picture/Mango graham.png', 'fruit-shake', 1),
('Mango matcha', 'Mango and matcha fusion', 99.00, '/picture/Mango matcha.png', 'fruit-shake', 1),
('Berry mango', 'Berry and mango blend', 89.00, '/picture/Berry mango.png', 'fruit-shake', 1),
('Berry Caramel Bliss', 'Berry with caramel', 99.00, '/picture/Berry Caramel Bliss.png', 'fruit-shake', 1),
('Berry Oreo', 'Berry with Oreo', 99.00, '/picture/Berry Oreo.png', 'fruit-shake', 1),
('mango oreo', 'Mango and Oreo blend', 99.00, '/picture/mango oreo.png', 'fruit-shake', 1),

-- Frappe Series
('Caramel Frappe', 'Caramel iced frappe', 99.00, '/picture/Caramel Frappe.png', 'frappe-series', 1),
('Oreo Frappe', 'Oreo cookie frappe', 99.00, '/picture/Oreo Frappe.png', 'frappe-series', 1),
('Biscoff frappe', 'Biscoff cookie frappe', 99.00, '/picture/Biscoff frappe.png', 'frappe-series', 1),
('Cheesecake Frappe', 'Cheesecake flavored frappe', 99.00, '/picture/Cheesecake Frappe.png', 'frappe-series', 1),
('Nuttela Hazelnut Frappe', 'Nutella hazelnut frappe', 99.00, '/picture/Nuttela Hazelnut Frappe.png', 'frappe-series', 1),

-- Waffles
('Chocolate waffle', 'Chocolate flavored waffle', 129.00, '/picture/Chocolate waffle.png', 'waffles', 1),
('Biscoff waffle', 'Biscoff cookie waffle', 139.00, '/picture/Biscoff waffle.png', 'waffles', 1),
('Oreo waffle', 'Oreo cookie waffle', 139.00, '/picture/Oreo waffle.png', 'waffles', 1),
('Strawberry waffle', 'Fresh strawberry waffle', 139.00, '/picture/Strawberry waffle.png', 'waffles', 1),
('tiramisu waffle', 'Tiramisu flavored waffle', 149.00, '/picture/tiramisu waffle.png', 'waffles', 1),
('ube waffle', 'Ube flavored waffle', 149.00, '/picture/ube waffle.png', 'waffles', 1),

-- Non-Coffee
('Franch Vanilla', 'French vanilla drink', 75.00, '/picture/Franch Vanilla.png', 'non-coffee', 1),
('White cocoa', 'White chocolate cocoa', 85.00, '/picture/White cocoa.png', 'non-coffee', 1),
('Cheesecake Latte', 'Cheesecake flavored latte', 99.00, '/picture/Cheesecake Latte.png', 'non-coffee', 1),
('Choco Vanilla Cookie', 'Chocolate vanilla cookie drink', 129.00, '/picture/Choco Vanilla Cookie.png', 'non-coffee', 1),
('Choco Banana Pudding', 'Chocolate banana pudding drink', 179.00, '/picture/Choco Banana Pudding.png', 'non-coffee', 1),
('Milky Oreo', 'Oreo milk drink', 89.00, '/picture/Milky Oreo.png', 'non-coffee', 1),
('Java Chips', 'Java chips blended drink', 99.00, '/picture/Java Chips.png', 'non-coffee', 1),

-- Bites
('French Fries', 'Crispy French fries', 69.00, '/picture/Fries.png', 'bites', 1),
('Chicken Poppers', 'Crispy chicken poppers', 79.00, '/picture/Chicken Poppers.png', 'bites', 1),
('Chicken poppers and fries', 'Poppers with fries combo', 99.00, '/picture/Chicken poppers and fries.png', 'bites', 1),
('Beef Natchos', 'Beef loaded nachos', 149.00, '/picture/Beef Natchos.png', 'bites', 1),
('Fries and Chicken Poppers', 'Fries with chicken poppers', 99.00, '/picture/Chicken poppers and fries.png', 'bites', 1),

-- Quesadilla
('Beef Quesadilla', 'Grilled beef quesadilla', 149.00, '/picture/Beef Quesadilla.png', 'quesadilla', 1),
('Chicken Quesadilla', 'Grilled chicken quesadilla', 159.00, '/picture/Chicken Quesadilla.png', 'quesadilla', 1),
('Messy Tuna Spinach', 'Tuna and spinach quesadilla', 129.00, '/picture/Messy Tuna Spinach.png', 'quesadilla', 1);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;