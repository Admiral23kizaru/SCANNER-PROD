-- ScanUp tbl creation script for phpMyAdmin / live ehris2.
-- SAFE MODE: no DROP, no TRUNCATE, no DELETE, no UPDATE.
-- Creates only ehris2.tbl_scanup_* tbls. Does not touch EHRIS tbls.
--
-- IMPORTANT:
-- 1. Run this from phpMyAdmin while logged in as a user that can CREATE on ehris2.
-- 2. This script uses fully-qualified tbl names: `ehris2`.`tbl_scanup_*`.
-- 3. If phpMyAdmin stops after one statement, use the Import tab instead of SQL paste.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_roles` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_scanup_roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_schools` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `deped_school_id` varchar(50) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `contact_number` varchar(64) DEFAULT NULL,
  `principal_name` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_scanup_schools_deped_school_id_unique` (`deped_school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` tinyint unsigned NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `school_id` bigint unsigned DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `job_title` varchar(50) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `ehris_user_id` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_scanup_users_email_unique` (`email`),
  KEY `tbl_scanup_users_school_id_foreign` (`school_id`),
  KEY `tbl_scanup_users_ehris_user_id_index` (`ehris_user_id`),
  KEY `scanup_users_school_role_idx` (`school_id`, `role_id`),
  KEY `tbl_scanup_users_role_id_foreign` (`role_id`),
  CONSTRAINT `tbl_scanup_users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `ehris2`.`tbl_scanup_roles` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `tbl_scanup_users_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `ehris2`.`tbl_scanup_schools` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_teachers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint unsigned NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `job_title` varchar(50) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `ehris_user_id` bigint unsigned DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_scanup_teachers_email_unique` (`email`),
  KEY `tbl_scanup_teachers_school_id_foreign` (`school_id`),
  KEY `tbl_scanup_teachers_ehris_user_id_index` (`ehris_user_id`),
  KEY `tbl_scanup_teachers_department_id_index` (`department_id`),
  KEY `scanup_teachers_school_employee_idx` (`school_id`, `employee_id`),
  CONSTRAINT `tbl_scanup_teachers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `ehris2`.`tbl_scanup_schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `teacher_id` int unsigned DEFAULT NULL,
  `school_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_scanup_sections_teacher_id_foreign` (`teacher_id`),
  KEY `tbl_scanup_sections_school_id_foreign` (`school_id`),
  CONSTRAINT `tbl_scanup_sections_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `ehris2`.`tbl_scanup_schools` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_scanup_sections_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `ehris2`.`tbl_scanup_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_school_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint unsigned NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `late_threshold` time NOT NULL DEFAULT '07:30:00',
  `absence_threshold` tinyint unsigned NOT NULL DEFAULT '3',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_scanup_school_settings_school_id_unique` (`school_id`),
  CONSTRAINT `tbl_scanup_school_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `ehris2`.`tbl_scanup_schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_school_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint unsigned NOT NULL,
  `name` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_scanup_school_years_school_id_foreign` (`school_id`),
  CONSTRAINT `tbl_scanup_school_years_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `ehris2`.`tbl_scanup_schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `school_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_scanup_subjects_school_id_name_index` (`school_id`, `name`),
  CONSTRAINT `tbl_scanup_subjects_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `ehris2`.`tbl_scanup_schools` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_students` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` int unsigned DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `student_number` varchar(64) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `grade_section` varchar(64) DEFAULT NULL,
  `grade` varchar(32) DEFAULT NULL,
  `section` varchar(32) DEFAULT NULL,
  `section_id` bigint unsigned DEFAULT NULL,
  `guardian` varchar(255) DEFAULT NULL,
  `guardian_email` varchar(255) DEFAULT NULL,
  `contact_number` varchar(64) DEFAULT NULL,
  `emergency_contact` varchar(64) DEFAULT NULL,
  `notification_preference` tinyint unsigned NOT NULL DEFAULT '0',
  `notification_pref_int` tinyint unsigned NOT NULL DEFAULT '0',
  `last_sms_sent_date` date DEFAULT NULL,
  `guardian_contact` varchar(64) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `qr_version` tinyint unsigned NOT NULL DEFAULT '1',
  `school_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_scanup_students_student_number_school_id_unique` (`student_number`, `school_id`),
  KEY `tbl_scanup_students_student_number_index` (`student_number`),
  KEY `tbl_scanup_students_grade_section_index` (`grade`, `section`),
  KEY `tbl_scanup_students_section_id_foreign` (`section_id`),
  KEY `tbl_scanup_students_school_id_foreign` (`school_id`),
  KEY `tbl_scanup_students_teacher_id_foreign` (`teacher_id`),
  KEY `tbl_scanup_students_created_by_foreign` (`created_by`),
  KEY `scanup_students_school_idx` (`school_id`),
  KEY `scanup_students_school_grade_sec_idx` (`school_id`, `grade`, `section`),
  CONSTRAINT `tbl_scanup_students_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `ehris2`.`tbl_scanup_users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `tbl_scanup_students_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `ehris2`.`tbl_scanup_schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_scanup_students_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `ehris2`.`tbl_scanup_sections` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_scanup_students_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `ehris2`.`tbl_scanup_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_attendance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int unsigned NOT NULL,
  `scanned_by` int unsigned DEFAULT NULL,
  `scanned_at` timestamp NULL DEFAULT NULL,
  `session` enum('morning','lunch_out','lunch_return','dismissal') NOT NULL DEFAULT 'morning',
  `status` enum('on_time','late') NOT NULL DEFAULT 'on_time',
  `school_year_id` bigint unsigned DEFAULT NULL,
  `school_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_scanup_attendance_student_id_foreign` (`student_id`),
  KEY `tbl_scanup_attendance_scanned_by_foreign` (`scanned_by`),
  KEY `tbl_scanup_attendance_scanned_at_index` (`scanned_at`),
  KEY `tbl_scanup_attendance_session_index` (`session`),
  KEY `tbl_scanup_attendance_status_index` (`status`),
  KEY `tbl_scanup_attendance_school_year_id_index` (`school_year_id`),
  KEY `tbl_scanup_attendance_school_id_foreign` (`school_id`),
  KEY `scanup_attendance_school_scanned_idx` (`school_id`, `scanned_at`),
  KEY `scanup_attendance_school_student_idx` (`school_id`, `student_id`),
  CONSTRAINT `tbl_scanup_attendance_scanned_by_foreign` FOREIGN KEY (`scanned_by`) REFERENCES `ehris2`.`tbl_scanup_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_scanup_attendance_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `ehris2`.`tbl_scanup_schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_scanup_attendance_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `ehris2`.`tbl_scanup_school_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_scanup_attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `ehris2`.`tbl_scanup_students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_student_subject` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_scanup_student_subject_student_id_subject_id_unique` (`student_id`, `subject_id`),
  KEY `tbl_scanup_student_subject_subject_id_student_id_index` (`subject_id`, `student_id`),
  CONSTRAINT `tbl_scanup_student_subject_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `ehris2`.`tbl_scanup_students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_scanup_student_subject_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `ehris2`.`tbl_scanup_subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_gmrc_scores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int unsigned NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `section` varchar(100) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `wrong_items` json DEFAULT NULL,
  `total_items` smallint unsigned NOT NULL DEFAULT '50',
  `score` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_scanup_gmrc_scores_grade_level_section_index` (`grade_level`, `section`),
  KEY `tbl_scanup_gmrc_scores_student_id_created_at_index` (`student_id`, `created_at`),
  KEY `tbl_scanup_gmrc_scores_subject_id_created_at_index` (`subject_id`, `created_at`),
  CONSTRAINT `tbl_scanup_gmrc_scores_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `ehris2`.`tbl_scanup_students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_scanup_gmrc_scores_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `ehris2`.`tbl_scanup_subjects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_scanup_personal_access_tokens_token_unique` (`token`),
  KEY `scanup_pat_tokenable_idx` (`tokenable_type`, `tokenable_id`),
  KEY `tbl_scanup_personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `tbl_scanup_password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ehris2`.`tbl_scanup_migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `ehris2`.`tbl_scanup_roles` (`id`, `name`, `created_at`, `updated_at`) VALUES
  (1, 'Admin', NOW(), NOW()),
  (2, 'Teacher', NOW(), NOW()),
  (3, 'Guard', NOW(), NOW()),
  (4, 'Reporting Manager', NOW(), NOW()),
  (5, 'Adviser', NOW(), NOW()),
  (6, 'Subject Teacher', NOW(), NOW()),
  (7, 'System Admin', NOW(), NOW());

INSERT INTO `ehris2`.`tbl_scanup_migrations` (`migration`, `batch`)
SELECT '2026_05_14_000000_create_scanup_tbls_in_ehris', 1
WHERE NOT EXISTS (
  SELECT 1 FROM `ehris2`.`tbl_scanup_migrations`
  WHERE `migration` = '2026_05_14_000000_create_scanup_tbls_in_ehris'
);

INSERT INTO `ehris2`.`tbl_scanup_migrations` (`migration`, `batch`)
SELECT '2026_05_14_000001_add_ehris_roles_to_tbl_scanup_roles', 1
WHERE NOT EXISTS (
  SELECT 1 FROM `ehris2`.`tbl_scanup_migrations`
  WHERE `migration` = '2026_05_14_000001_add_ehris_roles_to_tbl_scanup_roles'
);

INSERT INTO `ehris2`.`tbl_scanup_migrations` (`migration`, `batch`)
SELECT '2026_05_14_000002_add_tbl_scanup_scaling_performance_indexes', 1
WHERE NOT EXISTS (
  SELECT 1 FROM `ehris2`.`tbl_scanup_migrations`
  WHERE `migration` = '2026_05_14_000002_add_tbl_scanup_scaling_performance_indexes'
);

SELECT 'ScanUp tbl creation script finished. Refresh phpMyAdmin left sidebar and check ehris2 tbl_scanup_*.' AS result;
