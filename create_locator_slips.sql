DROP TABLE IF EXISTS `locator_slips`;

CREATE TABLE `locator_slips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` int(10) unsigned NOT NULL,
  `date_of_filing` date NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `permanent_station` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `purpose_of_travel` text NOT NULL,
  `official_type` enum('Official Business','Official Time') NOT NULL,
  `date_time` datetime NOT NULL,
  `time_out` time NOT NULL,
  `expected_return` time NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locator_slips_teacher_id_foreign` (`teacher_id`),
  KEY `locator_slips_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `locator_slips_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `locator_slips_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
