-- Add signature_path column to users table (for Admin digital signature)
-- Run this in phpMyAdmin on the 'scan_up' database

USE scan_up;

ALTER TABLE users
  ADD COLUMN signature_path VARCHAR(255) NULL AFTER profile_photo;

-- The signature_path stores the relative path from /public
-- Example value: 'signatures/admin_1_signature.png'
-- Upload the Admin's PNG signature file to: public/signatures/
