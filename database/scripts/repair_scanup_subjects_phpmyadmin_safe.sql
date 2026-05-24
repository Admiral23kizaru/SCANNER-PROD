-- Repair script for Admin > Manage Subjects.
-- Safe for phpMyAdmin on ehris2.
-- Purpose:
-- 1. Ensure tbl_scanup_subjects has the expected primary key.
-- 2. Ensure tbl_scanup_subjects.id auto-increments after SQL dump imports.
-- 3. Ensure the school/name lookup index exists.
--
-- Run this if POST /api/admin/subjects returns 500 and laravel.log mentions:
-- "Field 'id' doesn't have a default value", duplicate key/index issues, or missing subject indexes.

USE `ehris2`;

ALTER TABLE `tbl_scanup_subjects`
  MODIFY `id` bigint unsigned NOT NULL AUTO_INCREMENT;

SET @has_subject_school_name_index := (
  SELECT COUNT(1)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tbl_scanup_subjects'
    AND INDEX_NAME = 'tbl_scanup_subjects_school_id_name_index'
);

SET @add_subject_school_name_index := IF(
  @has_subject_school_name_index = 0,
  'ALTER TABLE `tbl_scanup_subjects` ADD INDEX `tbl_scanup_subjects_school_id_name_index` (`school_id`, `name`)',
  'SELECT ''tbl_scanup_subjects_school_id_name_index already exists'' AS status'
);

PREPARE stmt FROM @add_subject_school_name_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT
  TABLE_NAME,
  COLUMN_NAME,
  COLUMN_TYPE,
  EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tbl_scanup_subjects'
  AND COLUMN_NAME IN ('id', 'name', 'school_id', 'created_at', 'updated_at')
ORDER BY ORDINAL_POSITION;
