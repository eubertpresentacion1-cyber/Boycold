-- Migration: Enhance device tracking for device lock system
-- This adds session tracking to prevent simultaneous logins and device lock functionality

-- Add session tracking columns to pos_devices
ALTER TABLE `pos_devices` 
ADD COLUMN `current_employee_id` int DEFAULT NULL AFTER `device_status`,
ADD COLUMN `session_id` varchar(255) DEFAULT NULL AFTER `current_employee_id`,
ADD COLUMN `last_activity` timestamp NULL DEFAULT NULL AFTER `session_id`,
ADD COLUMN `is_locked` tinyint DEFAULT '0' AFTER `last_activity`,
ADD INDEX `idx_current_employee` (`current_employee_id`),
ADD INDEX `idx_session_id` (`session_id`);

-- Add device_id to employees table to track which device an employee is using
ALTER TABLE `employees` 
ADD COLUMN `current_device_id` int DEFAULT NULL AFTER `branch_id`,
ADD COLUMN `last_login_device_id` int DEFAULT NULL AFTER `current_device_id`,
ADD INDEX `idx_current_device` (`current_device_id`);
