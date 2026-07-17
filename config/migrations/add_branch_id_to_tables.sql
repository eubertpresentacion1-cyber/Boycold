-- Migration: Add branch_id to tables for multi-branch support
-- This adds branch_id columns to tables that need branch-specific data isolation

-- Add branch_id to ingredients table (for branch-specific inventory)
ALTER TABLE `ingredients` 
ADD COLUMN `branch_id` int DEFAULT NULL AFTER `unit`,
ADD INDEX `idx_branch_id` (`branch_id`);

-- Add branch_id to daily_sales_summary table (for branch-specific sales analytics)
ALTER TABLE `daily_sales_summary` 
ADD COLUMN `branch_id` int DEFAULT NULL AFTER `sale_date`,
ADD INDEX `idx_branch_id` (`branch_id`);

-- Add branch_id to ingredient_usage_daily table (for branch-specific ingredient usage tracking)
ALTER TABLE `ingredient_usage_daily` 
ADD COLUMN `branch_id` int DEFAULT NULL AFTER `usage_date`,
ADD INDEX `idx_branch_id` (`branch_id`);

-- Update existing records to assign them to branch 1 (Baliuag) as default
-- This ensures existing data is not orphaned
UPDATE `ingredients` SET `branch_id` = 1 WHERE `branch_id` IS NULL;
UPDATE `daily_sales_summary` SET `branch_id` = 1 WHERE `branch_id` IS NULL;
UPDATE `ingredient_usage_daily` SET `branch_id` = 1 WHERE `branch_id` IS NULL;
