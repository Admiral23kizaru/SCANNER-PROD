-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 19, 2026 at 01:59 AM
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
  `scanned_by` int(10) UNSIGNED DEFAULT NULL,
  `scanned_at` timestamp NULL DEFAULT NULL,
  `session` enum('morning','lunch_out','lunch_return','dismissal') NOT NULL DEFAULT 'morning',
  `status` enum('on_time','late') NOT NULL DEFAULT 'on_time',
  `school_year_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `scanned_by`, `scanned_at`, `session`, `status`, `school_year_id`, `created_at`, `updated_at`, `school_id`) VALUES
(3, 7, 3, '2026-03-01 01:30:43', 'morning', 'on_time', 2, '2026-03-01 01:30:43', '2026-03-01 01:30:43', 3),
(12, 7, 3, '2026-03-04 17:07:50', 'morning', 'on_time', 2, '2026-03-04 17:07:50', '2026-03-04 17:07:50', 3),
(20, 9, 3, '2026-03-11 23:39:02', 'morning', 'on_time', 2, '2026-03-11 23:39:02', '2026-03-11 23:39:02', 3),
(21, 7, 3, '2026-03-11 23:39:23', 'morning', 'on_time', 2, '2026-03-11 23:39:23', '2026-03-11 23:39:23', 3),
(22, 7, 3, '2026-03-13 02:54:23', 'morning', 'on_time', 2, '2026-03-13 02:54:23', '2026-03-13 02:54:23', 3),
(44, 7, 7, '2026-03-16 03:36:03', 'morning', 'late', 2, '2026-03-16 03:36:03', '2026-03-16 03:36:03', 3),
(45, 9, 7, '2026-03-16 03:37:02', 'morning', 'late', 2, '2026-03-16 03:37:02', '2026-03-16 03:37:02', 3),
(46, 7, 7, '2026-03-16 16:58:58', 'morning', 'on_time', 2, '2026-03-16 16:58:58', '2026-03-16 16:58:58', 3);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Who performed the action',
  `action` varchar(20) NOT NULL COMMENT 'created, updated, deleted, restored',
  `model` varchar(100) NOT NULL COMMENT 'e.g. Student, Teacher, Attendance',
  `model_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID of the affected record',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Values before the change' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Values after the change' CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(10, '2026_03_11_000002_add_job_title_to_users_and_teachers_tables', 9),
(11, '2026_03_16_010432_add_school_name_to_users_and_teachers_tables', 10);

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
(167, 'App\\Models\\User', 1, 'api-token', '5bb40622df6588e0834875f3f795492e44f41525484a6e18e4d6412a053d7113', '[\"*\"]', '2026-03-17 22:12:10', NULL, '2026-03-17 19:48:57', '2026-03-17 22:12:10'),
(168, 'App\\Models\\User', 7, 'api-token', 'bc8a7449b8bf87582f95393275cf77fb9a464bf6eda4ff0f11e015d7c2338b9c', '[\"*\"]', '2026-03-18 16:40:16', NULL, '2026-03-17 21:18:37', '2026-03-18 16:40:16');

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
(4, 'Guard', '2026-03-15 04:04:34', '2026-03-15 04:04:34');

-- --------------------------------------------------------

--
-- Table structure for table `scan_sessions`
--

CREATE TABLE `scan_sessions` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'morning, lunch_out, lunch_return, dismissal',
  `label` varchar(100) NOT NULL COMMENT 'Display name shown on scanner',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scan_sessions`
--

INSERT INTO `scan_sessions` (`id`, `name`, `label`, `start_time`, `end_time`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'morning', 'Morning Entry', '06:00:00', '08:00:00', 1, '2026-03-15 10:57:26', '2026-03-15 10:57:26'),
(2, 'lunch_out', 'Lunch Out', '11:00:00', '12:00:00', 0, '2026-03-15 10:57:26', '2026-03-15 10:57:26'),
(3, 'lunch_return', 'Lunch Return', '12:00:00', '13:00:00', 0, '2026-03-15 10:57:26', '2026-03-15 10:57:26'),
(4, 'dismissal', 'Afternoon Dismissal', '16:00:00', '17:30:00', 0, '2026-03-15 10:57:26', '2026-03-15 10:57:26');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(500) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `address`, `logo_path`, `created_at`, `updated_at`) VALUES
(2, 'Baybay Central', NULL, NULL, '2026-03-12 05:50:37', '2026-03-12 05:50:37'),
(3, 'Ozamiz City Central', NULL, NULL, '2026-03-12 05:50:37', '2026-03-12 05:50:37'),
(4, 'Catadman ES', NULL, NULL, '2026-03-12 05:50:37', '2026-03-12 05:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `school_settings`
--

CREATE TABLE `school_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `late_threshold` time NOT NULL DEFAULT '07:30:00' COMMENT 'Time after which a scan is marked late',
  `absence_threshold` tinyint(3) UNSIGNED NOT NULL DEFAULT 3 COMMENT 'Number of absences before student is auto-flagged',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_settings`
--

INSERT INTO `school_settings` (`id`, `school_id`, `logo_path`, `address`, `late_threshold`, `absence_threshold`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, NULL, '07:30:00', 3, '2026-03-15 10:57:26', '2026-03-15 10:57:26'),
(2, 3, NULL, NULL, '07:30:00', 3, '2026-03-15 10:57:26', '2026-03-15 10:57:26'),
(3, 4, NULL, NULL, '07:30:00', 3, '2026-03-15 10:57:26', '2026-03-15 10:57:26');

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(20) NOT NULL COMMENT 'e.g. 2025-2026',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`id`, `school_id`, `name`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, '2025-2026', '2025-06-02', '2026-03-27', 1, '2026-03-15 10:57:26', '2026-03-15 10:57:26'),
(2, 3, '2025-2026', '2025-06-02', '2026-03-27', 1, '2026-03-15 10:57:26', '2026-03-15 10:57:26'),
(3, 4, '2025-2026', '2025-06-02', '2026-03-27', 1, '2026-03-15 10:57:26', '2026-03-15 10:57:26');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `student_number` varchar(64) NOT NULL,
  `grade_section` varchar(64) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `grade` varchar(32) DEFAULT NULL,
  `section` varchar(32) DEFAULT NULL,
  `guardian` varchar(255) DEFAULT NULL,
  `guardian_email` varchar(255) DEFAULT NULL,
  `contact_number` varchar(64) DEFAULT NULL,
  `emergency_contact` varchar(64) DEFAULT NULL,
  `guardian_contact` varchar(64) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `qr_version` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `school_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `teacher_id`, `created_by`, `student_number`, `grade_section`, `first_name`, `last_name`, `middle_name`, `grade`, `section`, `guardian`, `guardian_email`, `contact_number`, `emergency_contact`, `guardian_contact`, `photo_path`, `qr_version`, `created_at`, `updated_at`, `deleted_at`, `school_id`) VALUES
(6, 8, 8, '9371618383', NULL, 'aaron', 'gemang', 'L', 'Grade 8', 'molave', 'joera', NULL, NULL, NULL, '999383833', NULL, 1, '2026-02-22 16:50:07', '2026-03-11 23:43:13', NULL, 2),
(7, 7, 7, '128164200066', 'Grade 2-molave', 'xyion', 'catedral', 'p', 'Grade 2', 'molave', 'Regie Akiatan Catedral', 'zzephyr934@gmail.com', '09461075459', '09461075459', '999383833', 'students/128164200066.png', 1, '2026-02-22 21:57:01', '2026-03-15 19:33:15', NULL, 3),
(9, 7, 7, '334343434234', 'Grade 2-molave', 'ZEPHYR', 'Z', 'E', 'Grade 2', 'molave', 'aaron', NULL, NULL, NULL, '09461075459', 'students/334343434234.png', 1, '2026-03-08 21:08:26', '2026-03-15 18:21:19', NULL, 3),
(10, 7, 7, '124523212131', 'grade 7-rose', 'jasfer', 'maghanoy', 't', 'grade 7', 'rose', 'pandero jylie', 'zzephyr934@gmail.com', '09461075459', '09461075459', NULL, 'students/124523212131.png', 1, '2026-03-15 19:21:05', '2026-03-15 19:21:05', NULL, 3);

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
  `employee_id` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `password` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `job_title` varchar(50) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `school_id`, `first_name`, `last_name`, `email`, `employee_id`, `status`, `password`, `designation`, `profile_photo`, `created_at`, `updated_at`, `deleted_at`, `job_title`, `school_name`) VALUES
(2, 2, 'Jane', 'Teacher', 'teacher@scanup.local', '2323232', 'active', '$2y$12$kwi3aQVD7wnThL9HobR5JOWE6R0.7eLn7XJviU0F5RdsXAwTBU.CO', 'Teacher', 'teachers/teacher_2.png', '2026-02-22 08:42:45', '2026-03-15 16:34:01', NULL, 'ADASIII', NULL),
(5, 3, 'Jane', 'Teacher', 'teacher@gmail.com', NULL, 'active', '$2y$12$MZlmiad.DHmQwI4nXMBcp.8iQA6PFUtCmmX6IHKsbtsK34Sdz36rO', 'Teacher', 'teachers/teacher_5.png', '2026-02-22 02:54:11', '2026-03-15 16:34:01', NULL, NULL, NULL),
(7, 3, 'Aaron', 'Aaron', 'aaron@gmail.com', '34342424242', 'active', '$2y$12$YKFaHu6tpcJIog.mL/dqwed78J0J/X1Rd9tY9YFZX1yxt2mOjfO8G', 'Principal', 'teachers/teacher_7.jpg', '2026-02-22 03:24:54', '2026-03-15 16:34:01', NULL, 'DENTII', NULL),
(8, 2, 'Berdon', 'Ian', 'berdon@gmail.com', NULL, 'active', '$2y$12$vi3/pXRU0xmD9/z7CaOny.4Mh92X1RnF0fTo76YaRLw5NkTt2q6u.', 'Teacher', 'teachers/teacher_8.png', '2026-02-22 14:01:22', '2026-03-15 16:34:01', NULL, NULL, NULL),
(9, 2, 'jasfer', 'maghanoy', 'jasfer@gmail.com', NULL, 'active', '$2y$12$6QWl1tqEbGdEluQC.zuYP.v7vlglaYP7dTfD2qB/nLgWBXhTS0bcu', 'Teacher', NULL, '2026-02-23 16:57:33', '2026-02-23 16:57:33', NULL, NULL, NULL),
(10, 4, 'Akashi', 'Akashi', 'daikiaaron54@gmail.com', '232323', 'active', '$2y$12$hD5A192zlhmshBSYLKuoPuZBUsmKg9h94ORnh7GVQfZYBin3omNoG', 'Adviser', 'teachers/teacher_10.png', '2026-03-02 04:41:30', '2026-03-15 16:34:01', NULL, NULL, NULL),
(11, 2, 'neil', 'neil', 'neilchristian.pangasian@nmsc.edu.ph', '443424241', 'active', '$2y$12$DpCoosuAj4zc5hgVgvmBU.1ojtip9KTOgvVHzMdR4bDvrcyYcwLxy', 'adviser', 'teachers/teacher_11.png', '2026-03-02 20:58:26', '2026-03-15 16:34:01', NULL, 'AOII', NULL),
(12, 2, 'Joera', 'Vicente', '42424242@deped.local', '42424242', 'active', '$2y$12$pLM6dDP6vuETU9WqCug3BuWvW1G60RV5h.zL8blwttYcnpBgMbvfO', 'Teacher', NULL, '2026-03-11 21:29:09', '2026-03-11 21:29:09', NULL, 'AOV', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `school_id` bigint(20) UNSIGNED DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `job_title` varchar(50) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `status`, `name`, `email`, `password`, `designation`, `employee_id`, `school_id`, `profile_photo`, `created_at`, `updated_at`, `deleted_at`, `job_title`, `school_name`) VALUES
(1, 1, 'active', 'System Admin', 'admin@gmail.com', '$2y$12$1NhyIF7j/HJdNFs7iGluN.adJGxfkW95kekgadZqgRpTCjLapH97K', NULL, NULL, NULL, 'admin_photos/tz6Zc7fVNbsIWeAERHQVZTC8Yx3hBe9cDzW4kAuY.jpg', '2026-02-22 08:42:45', '2026-03-17 17:26:56', NULL, NULL, NULL),
(2, 2, 'active', 'Jane Teacher', 'teacher@scanup.local', '$2y$12$kwi3aQVD7wnThL9HobR5JOWE6R0.7eLn7XJviU0F5RdsXAwTBU.CO', NULL, '2323232', 2, 'teachers/teacher_2.png', '2026-02-22 08:42:45', '2026-03-15 16:34:01', NULL, 'ADASIII', NULL),
(7, 2, 'active', 'Aaron', 'aaron@gmail.com', '$2y$12$YKFaHu6tpcJIog.mL/dqwed78J0J/X1Rd9tY9YFZX1yxt2mOjfO8G', 'Principal', '34342424242', 3, 'teachers/teacher_7.jpg', '2026-02-22 03:24:54', '2026-03-15 16:34:01', NULL, 'DENTII', NULL),
(8, 2, 'active', 'Berdon Ian', 'berdon@gmail.com', '$2y$12$vi3/pXRU0xmD9/z7CaOny.4Mh92X1RnF0fTo76YaRLw5NkTt2q6u.', NULL, NULL, NULL, 'teachers/teacher_8.png', '2026-02-22 14:01:22', '2026-03-15 16:34:01', NULL, NULL, NULL),
(9, 2, 'active', 'jasfer maghanoy', 'jasfer@gmail.com', '$2y$12$6QWl1tqEbGdEluQC.zuYP.v7vlglaYP7dTfD2qB/nLgWBXhTS0bcu', NULL, NULL, NULL, NULL, '2026-02-23 16:57:33', '2026-02-23 16:57:33', NULL, NULL, NULL),
(10, 2, 'active', 'Akashi', 'daikiaaron54@gmail.com', '$2y$12$hD5A192zlhmshBSYLKuoPuZBUsmKg9h94ORnh7GVQfZYBin3omNoG', 'Adviser', '232323', 4, 'teachers/teacher_10.png', '2026-03-02 04:41:30', '2026-03-15 16:34:01', NULL, NULL, NULL),
(11, 2, 'active', 'neil', 'neilchristian.pangasian@nmsc.edu.ph', '$2y$12$ISnOUuVjL8F9xj6NtTW4vusHdkbT9bgmkWsk0XGudt0tmjLOzEGGG', 'adviser', '443424241', NULL, 'teachers/teacher_11.png', '2026-03-02 20:58:26', '2026-03-16 23:45:11', NULL, 'AOII', NULL),
(12, 2, 'active', 'Joera Vicente', '42424242@deped.local', '$2y$12$pLM6dDP6vuETU9WqCug3BuWvW1G60RV5h.zL8blwttYcnpBgMbvfO', NULL, '42424242', NULL, NULL, '2026-03-11 21:29:09', '2026-03-11 21:29:09', NULL, 'AOV', NULL),
(13, 1, 'active', 'Aaron', 'aaronryo@gmail.com', '$2y$12$bc0mFvIj/ynJEr08VRsZJuYX36XxQhoXG6HgwPUiMJrplw9/FqRma', NULL, NULL, NULL, 'admin_photos/GGvRuWqMBRMWTrWd58HINar1dDblK1Ne8B24QtVw.jpg', '2026-03-12 06:03:40', '2026-03-17 17:01:07', NULL, NULL, NULL),
(14, 4, 'active', 'Guard Terminal', 'guard@system.local', '$2y$12$11mFSb2arUuQ3EIj4YbxnOXy8r6gre3V8CmUIvnmiNT1CXPMYTQiS', NULL, NULL, NULL, NULL, '2026-03-15 04:04:34', '2026-03-15 04:04:34', NULL, NULL, NULL);

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
  ADD KEY `attendance_school_id_foreign` (`school_id`),
  ADD KEY `attendance_scanned_at_index` (`scanned_at`),
  ADD KEY `attendance_session_index` (`session`),
  ADD KEY `attendance_status_index` (`status`),
  ADD KEY `attendance_school_year_id_index` (`school_year_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`),
  ADD KEY `audit_logs_model_index` (`model`),
  ADD KEY `audit_logs_created_at_index` (`created_at`);

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
-- Indexes for table `scan_sessions`
--
ALTER TABLE `scan_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scan_sessions_name_unique` (`name`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `school_settings`
--
ALTER TABLE `school_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_settings_school_id_unique` (`school_id`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_years_school_id_name_unique` (`school_id`,`name`),
  ADD KEY `school_years_school_id_foreign` (`school_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_student_number_unique` (`student_number`),
  ADD KEY `students_created_by_foreign` (`created_by`),
  ADD KEY `students_student_number_index` (`student_number`),
  ADD KEY `students_school_id_foreign` (`school_id`),
  ADD KEY `students_first_name_index` (`first_name`),
  ADD KEY `students_last_name_index` (`last_name`),
  ADD KEY `students_grade_index` (`grade`),
  ADD KEY `students_section_index` (`section`),
  ADD KEY `students_teacher_id_foreign` (`teacher_id`);

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
  ADD KEY `users_role_id_foreign` (`role_id`),
  ADD KEY `users_school_id_foreign` (`school_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `scan_sessions`
--
ALTER TABLE `scan_sessions`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `school_settings`
--
ALTER TABLE `school_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_scanned_by_foreign` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_school_year_id_foreign` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `school_settings`
--
ALTER TABLE `school_settings`
  ADD CONSTRAINT `school_settings_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_years`
--
ALTER TABLE `school_years`
  ADD CONSTRAINT `school_years_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `users_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
