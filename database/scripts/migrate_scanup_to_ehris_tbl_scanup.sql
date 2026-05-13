-- =============================================================================
-- ScanUp legacy DB -> ehris2 `tbl_scanup_*` data migration (explicit columns).
-- =============================================================================
--
-- PREFLIGHT (run in MySQL against `scan_up` before editing or executing this script):
--
--   DESCRIBE scan_up.schools;
--   DESCRIBE scan_up.users;
--   DESCRIBE scan_up.students;
--   DESCRIBE scan_up.teachers;
--   DESCRIBE scan_up.sections;
--   DESCRIBE scan_up.subjects;
--   DESCRIBE scan_up.student_subject;
--   DESCRIBE scan_up.gmrc_scores;
--
-- Also confirm any other source tbl you will INSERT from exists (e.g. `roles`, `attendance`,
-- `school_settings`, `school_years`, `password_resets`) and that its
-- columns match the chosen INSERT block below.
-- Note: Sanctum tokens are NOT copied — see `tbl_scanup_personal_access_tokens` section.
--
-- Rules:
--   - If a source tbl does not exist, comment out that INSERT block.
--   - If a source column does not exist for the default INSERT, switch to the commented fallback
--     block for that section (or add NULL AS `column` in the SELECT — keep destination column order).
--   - Do NOT run this script until DESCRIBE output matches the INSERT you keep active.
--
-- Before running:
--   1) Back up `scan_up` and `ehris2`.
--   2) On ehris2, apply Laravel migrations from `database/migrations` only (unprefixed ScanUp
--      history lives in `database/migrations_legacy_scan_up/` and is NOT loaded by Artisan).
--      Prefer: `php artisan migrate --path=database/migrations/2026_05_14_000000_create_scanup_tbls_in_ehris.php`
--      then optional `--path=` for 000001 / 000002, or a controlled full migrate if policy allows.
--   3) Replace `scan_up` / `ehris2` below if your schema names differ.
--
-- Do NOT use `INSERT ... SELECT *`.
--
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------- tbl_scanup_roles
-- Skip plain INSERT when Laravel migration `2026_05_14_000001_add_ehris_roles_to_tbl_scanup_roles`
-- already ran: it seeds stable ids 1–6 (Admin, Teacher, Guard, Reporting Manager, Adviser, Subject Teacher).
-- Copying `scan_up.roles` after that causes duplicate primary key / unique `name` failures.
--
-- Option A (recommended): Run migrations 000000 + 000001 before this SQL script — leave the block below commented.
--
-- Option B (legacy DB-only restore, no 000001): uncomment ON DUPLICATE KEY UPDATE block so merges win without failing.
--
-- INSERT INTO `ehris2`.`tbl_scanup_roles` (`id`, `name`, `created_at`, `updated_at`)
-- SELECT `id`, `name`, `created_at`, `updated_at`
-- FROM `scan_up`.`roles`
-- ON DUPLICATE KEY UPDATE
--   `name`       = VALUES(`name`),
--   `updated_at` = VALUES(`updated_at`);

-- ----------------------------------------------------------------------------- schools (default: full column copy from current scan_up)
INSERT INTO `ehris2`.`tbl_scanup_schools` (`id`, `name`, `deped_school_id`, `address`, `contact_number`, `principal_name`, `logo_path`, `created_at`, `updated_at`)
SELECT
  `id`,
  `name`,
  `deped_school_id`,
  `address`,
  `contact_number`,
  `principal_name`,
  `logo_path`,
  `created_at`,
  `updated_at`
FROM `scan_up`.`schools`;

-- Fallback: older `scan_up.schools` without `deped_school_id`, `contact_number`, or `principal_name`.
-- INSERT INTO `ehris2`.`tbl_scanup_schools` (`id`, `name`, `deped_school_id`, `address`, `contact_number`, `principal_name`, `logo_path`, `created_at`, `updated_at`)
-- SELECT `id`, `name`, NULL, `address`, NULL, NULL, `logo_path`, `created_at`, `updated_at`
-- FROM `scan_up`.`schools`;

-- ----------------------------------------------------------------------------- users (default: preserve auth / profile columns when present on source)
INSERT INTO `ehris2`.`tbl_scanup_users` (
  `id`, `role_id`, `status`, `name`, `email`, `password`, `designation`, `employee_id`, `school_id`,
  `profile_photo`, `job_title`, `school_name`, `grade_level`, `section`, `signature_path`,
  `remember_token`, `email_verified_at`, `ehris_user_id`, `deleted_at`, `created_at`, `updated_at`
)
SELECT
  `id`,
  `role_id`,
  `status`,
  `name`,
  `email`,
  `password`,
  `designation`,
  `employee_id`,
  `school_id`,
  `profile_photo`,
  `job_title`,
  `school_name`,
  `grade_level`,
  `section`,
  `signature_path`,
  `remember_token`,
  `email_verified_at`,
  NULL,
  `deleted_at`,
  `created_at`,
  `updated_at`
FROM `scan_up`.`users`;

-- Fallback: minimal `scan_up.users` (no `grade_level`, `section`, `signature_path`, `remember_token`, `email_verified_at`).
-- INSERT INTO `ehris2`.`tbl_scanup_users` (
--   `id`, `role_id`, `status`, `name`, `email`, `password`, `designation`, `employee_id`, `school_id`,
--   `profile_photo`, `job_title`, `school_name`, `grade_level`, `section`, `signature_path`,
--   `remember_token`, `email_verified_at`, `ehris_user_id`, `deleted_at`, `created_at`, `updated_at`
-- )
-- SELECT
--   `id`, `role_id`, `status`, `name`, `email`, `password`, `designation`, `employee_id`, `school_id`,
--   `profile_photo`, `job_title`, `school_name`,
--   NULL, NULL, NULL, NULL, NULL, NULL,
--   `deleted_at`, `created_at`, `updated_at`
-- FROM `scan_up`.`users`;

-- ----------------------------------------------------------------------------- teachers
INSERT INTO `ehris2`.`tbl_scanup_teachers` (
  `id`, `school_id`, `first_name`, `last_name`, `email`, `password`, `designation`, `profile_photo`,
  `employee_id`, `status`, `job_title`, `school_name`, `ehris_user_id`, `department_id`, `deleted_at`,
  `created_at`, `updated_at`
)
SELECT
  `id`,
  `school_id`,
  `first_name`,
  `last_name`,
  `email`,
  `password`,
  `designation`,
  `profile_photo`,
  `employee_id`,
  `status`,
  `job_title`,
  `school_name`,
  NULL,
  NULL,
  `deleted_at`,
  `created_at`,
  `updated_at`
FROM `scan_up`.`teachers`;

-- ----------------------------------------------------------------------------- sections (comment out if tbl missing in source)
INSERT INTO `ehris2`.`tbl_scanup_sections` (`id`, `name`, `grade_level`, `teacher_id`, `school_id`, `created_at`, `updated_at`)
SELECT `id`, `name`, `grade_level`, `teacher_id`, `school_id`, `created_at`, `updated_at`
FROM `scan_up`.`sections`;

-- ----------------------------------------------------------------------------- school_settings
INSERT INTO `ehris2`.`tbl_scanup_school_settings` (`id`, `school_id`, `logo_path`, `address`, `late_threshold`, `absence_threshold`, `created_at`, `updated_at`)
SELECT `id`, `school_id`, `logo_path`, `address`, `late_threshold`, `absence_threshold`, `created_at`, `updated_at`
FROM `scan_up`.`school_settings`;

-- ----------------------------------------------------------------------------- school_years
INSERT INTO `ehris2`.`tbl_scanup_school_years` (`id`, `school_id`, `name`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`)
SELECT `id`, `school_id`, `name`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`
FROM `scan_up`.`school_years`;

-- ----------------------------------------------------------------------------- subjects (comment out if tbl missing in source)
INSERT INTO `ehris2`.`tbl_scanup_subjects` (`id`, `name`, `school_id`, `created_at`, `updated_at`)
SELECT `id`, `name`, `school_id`, `created_at`, `updated_at`
FROM `scan_up`.`subjects`;

-- ----------------------------------------------------------------------------- students (default: preserve `gender` and `section_id` when present on source)
INSERT INTO `ehris2`.`tbl_scanup_students` (
  `id`, `teacher_id`, `created_by`, `student_number`, `first_name`, `last_name`, `gender`, `middle_name`,
  `grade_section`, `grade`, `section`, `section_id`, `guardian`, `guardian_email`, `contact_number`,
  `emergency_contact`, `notification_preference`, `notification_pref_int`, `last_sms_sent_date`,
  `guardian_contact`, `photo_path`, `qr_version`, `school_id`, `deleted_at`, `created_at`, `updated_at`
)
SELECT
  `id`,
  `teacher_id`,
  `created_by`,
  `student_number`,
  `first_name`,
  `last_name`,
  `gender`,
  `middle_name`,
  `grade_section`,
  `grade`,
  `section`,
  `section_id`,
  `guardian`,
  `guardian_email`,
  `contact_number`,
  `emergency_contact`,
  `notification_preference`,
  `notification_pref_int`,
  `last_sms_sent_date`,
  `guardian_contact`,
  `photo_path`,
  `qr_version`,
  `school_id`,
  `deleted_at`,
  `created_at`,
  `updated_at`
FROM `scan_up`.`students`;

-- Fallback: older `scan_up.students` without `gender` or `section_id`.
-- INSERT INTO `ehris2`.`tbl_scanup_students` (
--   `id`, `teacher_id`, `created_by`, `student_number`, `first_name`, `last_name`, `gender`, `middle_name`,
--   `grade_section`, `grade`, `section`, `section_id`, `guardian`, `guardian_email`, `contact_number`,
--   `emergency_contact`, `notification_preference`, `notification_pref_int`, `last_sms_sent_date`,
--   `guardian_contact`, `photo_path`, `qr_version`, `school_id`, `deleted_at`, `created_at`, `updated_at`
-- )
-- SELECT
--   `id`, `teacher_id`, `created_by`, `student_number`, `first_name`, `last_name`, NULL, `middle_name`,
--   `grade_section`, `grade`, `section`, NULL, `guardian`, `guardian_email`, `contact_number`,
--   `emergency_contact`, `notification_preference`, `notification_pref_int`, `last_sms_sent_date`,
--   `guardian_contact`, `photo_path`, `qr_version`, `school_id`, `deleted_at`, `created_at`, `updated_at`
-- FROM `scan_up`.`students`;

-- ----------------------------------------------------------------------------- attendance
INSERT INTO `ehris2`.`tbl_scanup_attendance` (
  `id`, `student_id`, `scanned_by`, `scanned_at`, `session`, `status`, `school_year_id`, `school_id`, `created_at`, `updated_at`
)
SELECT
  `id`,
  `student_id`,
  `scanned_by`,
  `scanned_at`,
  `session`,
  `status`,
  `school_year_id`,
  `school_id`,
  `created_at`,
  `updated_at`
FROM `scan_up`.`attendance`;

-- ----------------------------------------------------------------------------- student_subject pivot (comment out if tbl missing)
INSERT INTO `ehris2`.`tbl_scanup_student_subject` (`id`, `student_id`, `subject_id`, `created_at`, `updated_at`)
SELECT `id`, `student_id`, `subject_id`, `created_at`, `updated_at`
FROM `scan_up`.`student_subject`;

-- ----------------------------------------------------------------------------- gmrc_scores (comment out if tbl missing)
INSERT INTO `ehris2`.`tbl_scanup_gmrc_scores` (
  `id`, `student_id`, `subject_id`, `section`, `grade_level`, `wrong_items`, `total_items`, `score`, `created_at`, `updated_at`
)
SELECT
  `id`,
  `student_id`,
  `subject_id`,
  `section`,
  `grade_level`,
  `wrong_items`,
  `total_items`,
  `score`,
  `created_at`,
  `updated_at`
FROM `scan_up`.`gmrc_scores`;

-- ----------------------------------------------------------------------------- tbl_scanup_personal_access_tokens (Sanctum)
-- Do NOT copy legacy tokens from `scan_up.personal_access_tokens`. Runbook requires all users to
-- re-login after migration; old bearer tokens must not carry over.
--
-- (Legacy copy — keep disabled.)
-- INSERT INTO `ehris2`.`tbl_scanup_personal_access_tokens` (
--   `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`
-- )
-- SELECT
--   `id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`
-- FROM `scan_up`.`personal_access_tokens`;

-- ----------------------------------------------------------------------------- password resets
INSERT INTO `ehris2`.`tbl_scanup_password_resets` (`email`, `token`, `created_at`)
SELECT `email`, `token`, `created_at`
FROM `scan_up`.`password_resets`;

SET FOREIGN_KEY_CHECKS = 1;

-- tokens intentionally invalidated; all users must re-login (clears `tbl_scanup_personal_access_tokens`
-- after load, including if a legacy INSERT was temporarily enabled).
DELETE FROM `ehris2`.`tbl_scanup_personal_access_tokens`;

-- =============================================================================
-- POST-RUN VERIFICATION (row counts on destination `tbl_scanup_*`)
-- =============================================================================
SELECT 'tbl_scanup_roles' AS tbl_name, COUNT(*) AS row_count FROM `ehris2`.`tbl_scanup_roles`
UNION ALL SELECT 'tbl_scanup_schools', COUNT(*) AS row_count FROM `ehris2`.`tbl_scanup_schools`
UNION ALL SELECT 'tbl_scanup_users', COUNT(*) AS row_count FROM `ehris2`.`tbl_scanup_users`
UNION ALL SELECT 'tbl_scanup_teachers', COUNT(*) AS row_count FROM `ehris2`.`tbl_scanup_teachers`
UNION ALL SELECT 'tbl_scanup_students', COUNT(*) AS row_count FROM `ehris2`.`tbl_scanup_students`
UNION ALL SELECT 'tbl_scanup_attendance', COUNT(*) AS row_count FROM `ehris2`.`tbl_scanup_attendance`;

-- Bridge / data-quality (nullable FKs: only count rows where the FK column is non-NULL but target row is missing)
SELECT COUNT(*) AS schools_missing_deped_school_id
FROM `ehris2`.`tbl_scanup_schools`
WHERE `deped_school_id` IS NULL OR `deped_school_id` = '';

SELECT COUNT(*) AS school_scoped_users_missing_school_id
FROM `ehris2`.`tbl_scanup_users` u
INNER JOIN `ehris2`.`tbl_scanup_roles` r ON r.`id` = u.`role_id`
WHERE r.`name` IN ('Teacher', 'Guard', 'Reporting Manager', 'Subject Teacher', 'Adviser')
  AND u.`school_id` IS NULL;

-- =============================================================================
-- FK integrity (orphans) — use after `SET FOREIGN_KEY_CHECKS = 0` loads; expect 0 on every count.
-- =============================================================================
SELECT COUNT(*) AS orphan_users_role_id
FROM `ehris2`.`tbl_scanup_users` u
LEFT JOIN `ehris2`.`tbl_scanup_roles` r ON r.`id` = u.`role_id`
WHERE r.`id` IS NULL;

SELECT COUNT(*) AS orphan_users_school_id
FROM `ehris2`.`tbl_scanup_users` u
LEFT JOIN `ehris2`.`tbl_scanup_schools` s ON s.`id` = u.`school_id`
WHERE u.`school_id` IS NOT NULL AND s.`id` IS NULL;

SELECT COUNT(*) AS orphan_teachers_school_id
FROM `ehris2`.`tbl_scanup_teachers` t
LEFT JOIN `ehris2`.`tbl_scanup_schools` s ON s.`id` = t.`school_id`
WHERE s.`id` IS NULL;

SELECT COUNT(*) AS orphan_sections_school_id
FROM `ehris2`.`tbl_scanup_sections` sec
LEFT JOIN `ehris2`.`tbl_scanup_schools` s ON s.`id` = sec.`school_id`
WHERE sec.`school_id` IS NOT NULL AND s.`id` IS NULL;

SELECT COUNT(*) AS orphan_sections_teacher_id
FROM `ehris2`.`tbl_scanup_sections` sec
LEFT JOIN `ehris2`.`tbl_scanup_users` u ON u.`id` = sec.`teacher_id`
WHERE sec.`teacher_id` IS NOT NULL AND u.`id` IS NULL;

SELECT COUNT(*) AS orphan_students_school_id
FROM `ehris2`.`tbl_scanup_students` st
LEFT JOIN `ehris2`.`tbl_scanup_schools` s ON s.`id` = st.`school_id`
WHERE s.`id` IS NULL;

SELECT COUNT(*) AS orphan_students_section_id
FROM `ehris2`.`tbl_scanup_students` st
LEFT JOIN `ehris2`.`tbl_scanup_sections` sec ON sec.`id` = st.`section_id`
WHERE st.`section_id` IS NOT NULL AND sec.`id` IS NULL;

SELECT COUNT(*) AS orphan_students_teacher_id
FROM `ehris2`.`tbl_scanup_students` st
LEFT JOIN `ehris2`.`tbl_scanup_users` u ON u.`id` = st.`teacher_id`
WHERE st.`teacher_id` IS NOT NULL AND u.`id` IS NULL;

SELECT COUNT(*) AS orphan_students_created_by
FROM `ehris2`.`tbl_scanup_students` st
LEFT JOIN `ehris2`.`tbl_scanup_users` u ON u.`id` = st.`created_by`
WHERE u.`id` IS NULL;

SELECT COUNT(*) AS orphan_attendance_student_id
FROM `ehris2`.`tbl_scanup_attendance` a
LEFT JOIN `ehris2`.`tbl_scanup_students` st ON st.`id` = a.`student_id`
WHERE st.`id` IS NULL;

SELECT COUNT(*) AS orphan_attendance_scanned_by
FROM `ehris2`.`tbl_scanup_attendance` a
LEFT JOIN `ehris2`.`tbl_scanup_users` u ON u.`id` = a.`scanned_by`
WHERE a.`scanned_by` IS NOT NULL AND u.`id` IS NULL;

SELECT COUNT(*) AS orphan_attendance_school_id
FROM `ehris2`.`tbl_scanup_attendance` a
LEFT JOIN `ehris2`.`tbl_scanup_schools` s ON s.`id` = a.`school_id`
WHERE s.`id` IS NULL;

SELECT COUNT(*) AS orphan_subjects_school_id
FROM `ehris2`.`tbl_scanup_subjects` sub
LEFT JOIN `ehris2`.`tbl_scanup_schools` s ON s.`id` = sub.`school_id`
WHERE sub.`school_id` IS NOT NULL AND s.`id` IS NULL;

SELECT COUNT(*) AS orphan_student_subject_student_id
FROM `ehris2`.`tbl_scanup_student_subject` ss
LEFT JOIN `ehris2`.`tbl_scanup_students` st ON st.`id` = ss.`student_id`
WHERE st.`id` IS NULL;

SELECT COUNT(*) AS orphan_student_subject_subject_id
FROM `ehris2`.`tbl_scanup_student_subject` ss
LEFT JOIN `ehris2`.`tbl_scanup_subjects` sub ON sub.`id` = ss.`subject_id`
WHERE sub.`id` IS NULL;

SELECT COUNT(*) AS orphan_gmrc_student_id
FROM `ehris2`.`tbl_scanup_gmrc_scores` g
LEFT JOIN `ehris2`.`tbl_scanup_students` st ON st.`id` = g.`student_id`
WHERE st.`id` IS NULL;

SELECT COUNT(*) AS orphan_gmrc_subject_id
FROM `ehris2`.`tbl_scanup_gmrc_scores` g
LEFT JOIN `ehris2`.`tbl_scanup_subjects` sub ON sub.`id` = g.`subject_id`
WHERE g.`subject_id` IS NOT NULL AND sub.`id` IS NULL;

-- After load, reset AUTO_INCREMENT to max(id)+1 per tbl if needed, e.g.:
-- ALTER TABLE `ehris2`.`tbl_scanup_users` AUTO_INCREMENT = 1000;
