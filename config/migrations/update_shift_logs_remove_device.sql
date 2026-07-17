-- Migration: Update shift_logs table to remove device_id requirement
-- Since device registration has been removed, device_id is no longer needed

ALTER TABLE shift_logs MODIFY COLUMN device_id int DEFAULT NULL;
