-- Add designation and profile_photo to users table (for teachers)
-- Run this if you did not use: php artisan migrate
-- Database: scan_up (or your app database)

USE scan_up;

-- Option A: Add columns after password (preferred)
ALTER TABLE users
  ADD COLUMN designation VARCHAR(255) NULL AFTER password,
  ADD COLUMN profile_photo VARCHAR(255) NULL AFTER designation;

-- If Option A fails (e.g. column order), use Option B instead:
-- ALTER TABLE users ADD COLUMN designation VARCHAR(255) NULL;
-- ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL;
