-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2026 at 01:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `scan_up`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `scanned_by` int(10) UNSIGNED NOT NULL,
  `scanned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `school_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `scanned_by`, `scanned_at`, `created_at`, `updated_at`, `school_id`) VALUES
(3, 7, 3, '2026-03-01 01:30:43', '2026-03-01 01:30:43', '2026-03-01 01:30:43', NULL),
(12, 7, 3, '2026-03-04 17:07:50', '2026-03-04 17:07:50', '2026-03-04 17:07:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_02_22_000001_add_emergency_contact_to_students_table', 1),
(2, '2025_02_22_000002_add_expires_at_to_personal_access_tokens_table', 2),
(3, '2025_02_22_000003_add_learner_fields_to_students_table', 3),
(4, '2026_02_26_055024_add_guardian_email_to_students_table', 4),
(5, '2026_02_26_061701_add_student_number_index_to_students_table', 5),
(6, '2026_02_27_010601_create_schools_and_teachers_tables', 6),
(7, '2026_03_01_000001_add_designation_and_profile_photo_to_users_table', 7),
(8, '2026_03_09_052641_create_locator_slips_table', 8),
(9, '2026_03_11_000001_drop_locator_slips_table', 9),
(10, '2026_03_11_000002_add_job_title_to_users_and_teachers_tables', 9);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(3, 'App\\Models\\User', 1, 'api-token', '1decc873e975bda8037c4607897fbdb87dccd77abd0795daad131da04128bc1e', '[\"*\"]', '2026-02-22 00:45:59', NULL, '2026-02-22 00:45:58', '2026-02-22 00:45:59'),
(4, 'App\\Models\\User', 3, 'api-token', '0e67dd7cb4e10a9ce17c11facd23063c6e104426ced20eef05dd833dc83ad390', '[\"*\"]', '2026-02-22 00:46:00', NULL, '2026-02-22 00:45:59', '2026-02-22 00:46:00'),
(128, 'App\\Models\\User', 4, 'api-token', 'e3e0286d8eaadf10ba83a7029673c5e89f41bba47a08e0fc2f33f26dcdab95e0', '[\"*\"]', '2026-03-11 16:16:43', NULL, '2026-03-11 16:10:24', '2026-03-11 16:16:43');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Admin', '2026-02-22 08:42:45', '2026-02-22 08:42:45'),
(2, 'Teacher', '2026-02-22 08:42:45', '2026-02-22 08:42:45'),
(3, 'Guard', '2026-02-22 08:42:45', '2026-02-22 08:42:45');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Main Campus', '2026-02-26 17:11:19', '2026-02-26 17:11:19');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `student_number` varchar(64) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `grade_section` varchar(64) DEFAULT NULL,
  `grade` varchar(32) DEFAULT NULL,
  `section` varchar(32) DEFAULT NULL,
  `guardian` varchar(255) DEFAULT NULL,
  `guardian_email` varchar(255) DEFAULT NULL,
  `contact_number` varchar(64) DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `school_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `teacher_id`, `created_by`, `student_number`, `first_name`, `last_name`, `middle_name`, `grade_section`, `grade`, `section`, `guardian`, `guardian_email`, `contact_number`, `emergency_contact`, `created_at`, `updated_at`, `school_id`) VALUES
(6, 8, 8, '9371618383', 'aaron', 'gemang', 'L', 'garde 8-molave', 'garde 8', 'molave', 'micah', NULL, '999383833', '999383833', '2026-02-22 16:50:07', '2026-02-22 16:50:07', NULL),
(7, 7, 7, '128164200066', 'xyion', 'catedral', 'p', 'garde2-molave', 'garde2', 'molave', 'Regie Akiatan Catedral', 'admiralzephyr723@gmail.com', '999383833', '999383833', '2026-02-22 21:57:01', '2026-02-25 22:30:13', NULL),
(9, 7, 7, '334343434234', 'ZEPHYR', 'Z', 'E', 'grade 2-molave', 'grade 2', 'molave', 'aaron', 'zzephyr934@gmail.com', '09461075459', '09461075459', '2026-03-08 21:08:26', '2026-03-08 21:08:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `job_title` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `job_title` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `designation`, `employee_id`, `school_name`, `profile_photo`, `created_at`, `updated_at`, `job_title`) VALUES
(1, 1, 'System Admin', 'admin@scanup.local', '$2y$12$mfr0KvDBIZUmdniNBsFItu7afg.kmkEwCNW2BqduPR9OLwFwQb3qa', NULL, NULL, NULL, NULL, '2026-02-22 08:42:45', '2026-02-22 02:28:18', NULL),
(2, 2, 'Jane Teacher', 'teacher@scanup.local', '$2y$12$kwi3aQVD7wnThL9HobR5JOWE6R0.7eLn7XJviU0F5RdsXAwTBU.CO', NULL, '2323232', 'Baybay Central', 'storage/teachers/teacher_2.png', '2026-02-22 08:42:45', '2026-03-10 22:03:36', 'ADASIII'),
(3, 3, 'Guard Post One', 'guard@scanup.local', '$2y$12$J3tO6W0RSFwh/KRyWu.u4O2Q1zrzsoBioAgDrjgi7Kwl.ZGfzjWAi', NULL, NULL, NULL, NULL, '2026-02-22 08:42:45', '2026-02-22 02:28:19', NULL),
(4, 1, 'System Admin', 'admin@gmail.com', '$2y$12$MuYEFfAcnrCEAaRjMjMB4uD6wqF9Qel1KPnqMqmBJuI2tyiEHx/2K', NULL, NULL, NULL, NULL, '2026-02-22 02:54:11', '2026-02-22 03:23:49', NULL),
(5, 2, 'Jane Teacher', 'teacher@gmail.com', '$2y$12$MZlmiad.DHmQwI4nXMBcp.8iQA6PFUtCmmX6IHKsbtsK34Sdz36rO', NULL, '888888', 'Ozamiz City Central', 'storage/teachers/teacher_5.png', '2026-02-22 02:54:11', '2026-03-05 00:44:31', NULL),
(6, 3, 'Guard Post One', 'guard@gmail.com', '$2y$12$m/lF6uEVNOfgvi7YfiGV3..96T1/Qp2BlntqWOCm6upMRAczibO2i', NULL, NULL, NULL, NULL, '2026-02-22 02:54:11', '2026-02-22 02:54:11', NULL),
(7, 2, 'Aaron', 'aaron@gmail.com', '$2y$12$YKFaHu6tpcJIog.mL/dqwed78J0J/X1Rd9tY9YFZX1yxt2mOjfO8G', 'Principal', '34342424242', 'Ozamiz City Central', 'storage/teachers/teacher_7.png', '2026-02-22 03:24:54', '2026-03-10 21:52:41', 'DENTII'),
(8, 2, 'Berdon Ian', 'berdon@gmail.com', '$2y$12$vi3/pXRU0xmD9/z7CaOny.4Mh92X1RnF0fTo76YaRLw5NkTt2q6u.', NULL, NULL, NULL, 'storage/teachers/teacher_8.png', '2026-02-22 14:01:22', '2026-03-01 04:48:14', NULL),
(9, 2, 'jasfer maghanoy', 'jasfer@gmail.com', '$2y$12$6QWl1tqEbGdEluQC.zuYP.v7vlglaYP7dTfD2qB/nLgWBXhTS0bcu', NULL, NULL, NULL, NULL, '2026-02-23 16:57:33', '2026-02-23 16:57:33', NULL),
(10, 2, 'Akashi', 'daikiaaron54@gmail.com', '$2y$12$hD5A192zlhmshBSYLKuoPuZBUsmKg9h94ORnh7GVQfZYBin3omNoG', 'Adviser', '232323', 'Catadman ES', 'storage/teachers/teacher_10.png', '2026-03-02 04:41:30', '2026-03-09 00:02:45', NULL),
(11, 2, 'neil', 'neilchristian.pangasian@nmsc.edu.ph', '$2y$12$DpCoosuAj4zc5hgVgvmBU.1ojtip9KTOgvVHzMdR4bDvrcyYcwLxy', 'adviser', NULL, NULL, 'storage/teachers/teacher_11.png', '2026-03-02 20:58:26', '2026-03-02 20:58:27', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_student_id_foreign` (`student_id`),
  ADD KEY `attendance_scanned_by_foreign` (`scanned_by`),
  ADD KEY `attendance_school_id_foreign` (`school_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_student_number_unique` (`student_number`),
  ADD KEY `students_teacher_id_foreign` (`teacher_id`),
  ADD KEY `students_created_by_foreign` (`created_by`),
  ADD KEY `students_student_number_index` (`student_number`),
  ADD KEY `students_school_id_foreign` (`school_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teachers_email_unique` (`email`),
  ADD KEY `teachers_school_id_foreign` (`school_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_scanned_by_foreign` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `students_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
