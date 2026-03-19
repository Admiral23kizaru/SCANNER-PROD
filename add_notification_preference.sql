-- ============================================================
-- Migration: Add notification_preference to students table
-- Run this in phpMyAdmin on database: scan_up
-- ============================================================

-- Step 1: Add the column (skip if already exists)
ALTER TABLE `students`
  ADD COLUMN `notification_preference` ENUM('email', 'sms') NOT NULL DEFAULT 'email'
  AFTER `guardian_email`;

-- Step 2: Verify the column was added successfully
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'scan_up'
  AND TABLE_NAME   = 'students'
  AND COLUMN_NAME  = 'notification_preference';

-- Expected result:
-- COLUMN_NAME             | COLUMN_TYPE       | COLUMN_DEFAULT | IS_NULLABLE
-- notification_preference | enum('email','sms')| email         | NO
