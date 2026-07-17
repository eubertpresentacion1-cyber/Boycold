-- Migration: Add ingredients and mapping tables
-- Run this SQL against the boycold database to enable ingredient tracking for POS orders

CREATE TABLE IF NOT EXISTS `ingredients` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `unit` VARCHAR(32) NOT NULL DEFAULT 'unit',
    `stock` DECIMAL(10,3) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ingredient_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_ingredients` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_name` VARCHAR(150) NOT NULL,
    `ingredient_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,3) NOT NULL DEFAULT 0, -- amount of ingredient PER UNIT of product (eg ml, g, pcs)
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_name` (`product_name`),
    KEY `idx_ingredient_id` (`ingredient_id`),
    CONSTRAINT `fk_pi_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_ingredients` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `ingredient_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(10,3) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_order_ingredient` (`ingredient_id`),
    CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_oi_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: a few example ingredients (adjust names/units/stock as needed)
INSERT INTO `ingredients` (`name`, `unit`, `stock`) VALUES
('Whole Milk', 'ml', 5000),
('Oat Milk', 'ml', 2000),
('Espresso Shot', 'shot', 1000),
('Whipped Cream', 'g', 1000)
ON DUPLICATE KEY UPDATE stock = VALUES(stock);

-- Example mapping: associate sample products to ingredients (update product names to match your menu)
INSERT INTO `product_ingredients` (`product_name`, `ingredient_id`, `amount`) VALUES
('Spanish Latte', (SELECT id FROM ingredients WHERE name = 'Whole Milk'), 150),
('French Vanilla', (SELECT id FROM ingredients WHERE name = 'Whole Milk'), 150),
('Biscoff frappe', (SELECT id FROM ingredients WHERE name = 'Whole Milk'), 200)
ON DUPLICATE KEY UPDATE amount = VALUES(amount);
