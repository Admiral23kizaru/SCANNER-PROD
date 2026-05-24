-- System Admin module support tables and optional sample data.
-- Safe for phpMyAdmin. This does not alter original EHRIS master tables.
-- It only creates/uses tbl_scanup_* tables used by Project TEA.

USE `ehris2`;

CREATE TABLE IF NOT EXISTS `tbl_scanup_parent_guardians` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int unsigned DEFAULT NULL,
  `school_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `relationship` varchar(80) NOT NULL DEFAULT 'Guardian',
  `contact_number` varchar(80) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_scanup_parent_guardians_student_id_foreign` (`student_id`),
  KEY `tbl_scanup_parent_guardians_school_id_relationship_index` (`school_id`, `relationship`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_scanup_assessment_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int unsigned DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `school_id` bigint unsigned NOT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `assessment_type` varchar(100) NOT NULL DEFAULT 'Semestral Assessment',
  `score` smallint unsigned NOT NULL DEFAULT 0,
  `total_items` smallint unsigned NOT NULL DEFAULT 0,
  `least_mastered_skills` json DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_scanup_assessment_logs_subject_id_foreign` (`subject_id`),
  KEY `tbl_scanup_assessment_logs_school_id_foreign` (`school_id`),
  KEY `tbl_scanup_assessment_logs_student_id_foreign` (`student_id`),
  KEY `scanup_assessment_scope_idx` (`school_id`, `school_year`, `grade_level`, `section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tbl_scanup_learning_assessment_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `school_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `analyzed_at` date NOT NULL,
  `sheet_title` varchar(100) DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `student_count` int unsigned NOT NULL DEFAULT 0,
  `item_count` int unsigned NOT NULL DEFAULT 0,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `analysis_payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_scanup_learning_assessment_files_school_id_index` (`school_id`),
  KEY `tbl_scanup_learning_assessment_files_created_by_index` (`created_by`),
  KEY `tbl_scanup_learning_assessment_files_subject_id_index` (`subject_id`),
  KEY `scanup_la_files_school_date_idx` (`school_id`, `analyzed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: seed parent/guardian records from existing learner guardian fields.
INSERT INTO `tbl_scanup_parent_guardians`
  (`student_id`, `school_id`, `name`, `relationship`, `contact_number`, `email`, `is_primary`, `created_at`, `updated_at`)
SELECT
  s.`id`,
  s.`school_id`,
  COALESCE(NULLIF(s.`guardian`, ''), 'Guardian'),
  'Guardian',
  COALESCE(NULLIF(s.`guardian_contact`, ''), NULLIF(s.`contact_number`, '')),
  NULLIF(s.`guardian_email`, ''),
  1,
  NOW(),
  NOW()
FROM `tbl_scanup_students` s
WHERE s.`school_id` IS NOT NULL
  AND NULLIF(s.`guardian`, '') IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `tbl_scanup_parent_guardians` pg WHERE pg.`student_id` = s.`id`
  )
LIMIT 300;

-- Optional: one visible Learning Assessment sample based on existing school/subject records.
-- This powers the System Admin Least Mastered Skills pie chart if no real analysis has been saved yet.
INSERT INTO `tbl_scanup_learning_assessment_files`
  (`school_id`, `created_by`, `subject_id`, `title`, `analyzed_at`, `sheet_title`, `grade_level`, `section`,
   `student_count`, `item_count`, `filename`, `file_path`, `analysis_payload`, `created_at`, `updated_at`)
SELECT
  st.`school_id`,
  NULL,
  sub.`id`,
  'Sample Learning Assessment Item Analysis',
  CURDATE(),
  sub.`name`,
  st.`grade`,
  st.`section`,
  COUNT(st.`id`),
  5,
  'sample_learning_assessment.xlsx',
  'learning-assessment/analyzed/sample_learning_assessment.xlsx',
  JSON_OBJECT(
    'item_numbers', JSON_ARRAY(1, 2, 3, 4, 5),
    'answer_key', JSON_ARRAY('A', 'B', 'C', 'D', 'A'),
    'students', JSON_ARRAY(),
    'item_stats', JSON_ARRAY(
      JSON_OBJECT('item', 1, 'total_correct', 8, 'examinees', 10, 'difficulty_pct', 80, 'difficulty_level', 'Easy'),
      JSON_OBJECT('item', 2, 'total_correct', 5, 'examinees', 10, 'difficulty_pct', 50, 'difficulty_level', 'Average'),
      JSON_OBJECT('item', 3, 'total_correct', 3, 'examinees', 10, 'difficulty_pct', 30, 'difficulty_level', 'Difficult'),
      JSON_OBJECT('item', 4, 'total_correct', 2, 'examinees', 10, 'difficulty_pct', 20, 'difficulty_level', 'Difficult'),
      JSON_OBJECT('item', 5, 'total_correct', 4, 'examinees', 10, 'difficulty_pct', 40, 'difficulty_level', 'Difficult')
    )
  ),
  NOW(),
  NOW()
FROM `tbl_scanup_students` st
JOIN `tbl_scanup_subjects` sub ON sub.`school_id` = st.`school_id`
WHERE NOT EXISTS (
  SELECT 1 FROM `tbl_scanup_learning_assessment_files` f
  WHERE f.`title` = 'Sample Learning Assessment Item Analysis'
)
GROUP BY st.`school_id`, sub.`id`, sub.`name`, st.`grade`, st.`section`
LIMIT 1;
