-- Migration: Add max_stock column to ingredients table
-- Date: 2026-07-17
-- Description: Add max_stock column to ingredients table

-- Add max_stock column to ingredients table
ALTER TABLE `ingredients` ADD COLUMN `max_stock` decimal(10,3) DEFAULT NULL AFTER `stock`;

-- Update existing ingredients with max_stock values (only if max_stock is NULL)
UPDATE `ingredients` SET `max_stock` = 10000.000 WHERE `name` = 'Whole Milk' AND `unit` = 'ml' AND `max_stock` IS NULL;
UPDATE `ingredients` SET `max_stock` = 5000.000 WHERE `name` = 'Oat Milk' AND `unit` = 'ml' AND `max_stock` IS NULL;
UPDATE `ingredients` SET `max_stock` = 2000.000 WHERE `name` = 'Espresso Shot' AND `unit` = 'shot' AND `max_stock` IS NULL;
UPDATE `ingredients` SET `max_stock` = 2000.000 WHERE `name` = 'Whipped Cream' AND `unit` = 'g' AND `max_stock` IS NULL;
