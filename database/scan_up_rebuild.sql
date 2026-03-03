DROP DATABASE IF EXISTS scan_up;
CREATE DATABASE scan_up CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scan_up;

CREATE TABLE roles (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY roles_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id TINYINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email),
  KEY users_role_id_foreign (role_id),
  CONSTRAINT users_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE students (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  teacher_id INT UNSIGNED NULL DEFAULT NULL,
  created_by INT UNSIGNED NOT NULL,
  student_number VARCHAR(64) NOT NULL,
  first_name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255) NOT NULL,
  middle_name VARCHAR(255) NULL DEFAULT NULL,
  grade_section VARCHAR(64) NULL DEFAULT NULL,
  grade VARCHAR(32) NULL DEFAULT NULL,
  section VARCHAR(32) NULL DEFAULT NULL,
  guardian VARCHAR(255) NULL DEFAULT NULL,
  contact_number VARCHAR(64) NULL DEFAULT NULL,
  emergency_contact VARCHAR(255) NULL DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY students_student_number_unique (student_number),
  KEY students_teacher_id_foreign (teacher_id),
  KEY students_created_by_foreign (created_by),
  CONSTRAINT students_teacher_id_foreign FOREIGN KEY (teacher_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT students_created_by_foreign FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  scanned_by INT UNSIGNED NOT NULL,
  scanned_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY attendance_student_id_foreign (student_id),
  KEY attendance_scanned_by_foreign (scanned_by),
  CONSTRAINT attendance_student_id_foreign FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT attendance_scanned_by_foreign FOREIGN KEY (scanned_by) REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  email VARCHAR(255) NOT NULL,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  KEY password_resets_email_index (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  connection TEXT NOT NULL,
  queue TEXT NOT NULL,
  payload LONGTEXT NOT NULL,
  exception LONGTEXT NOT NULL,
  failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE personal_access_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  token VARCHAR(64) NOT NULL,
  abilities TEXT NULL DEFAULT NULL,
  last_used_at TIMESTAMP NULL DEFAULT NULL,
  expires_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY personal_access_tokens_token_unique (token),
  KEY personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (id, name, created_at, updated_at) VALUES
(1, 'Admin', NOW(), NOW()),
(2, 'Teacher', NOW(), NOW()),
(3, 'Guard', NOW(), NOW());

INSERT INTO users (id, role_id, name, email, password, created_at, updated_at) VALUES
(1, 1, 'System Admin', 'admin@scanup.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(2, 2, 'Jane Teacher', 'teacher@scanup.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW()),
(3, 3, 'Guard Post One', 'guard@scanup.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

INSERT INTO students (id, teacher_id, created_by, student_number, first_name, last_name, middle_name, grade_section, grade, section, guardian, contact_number, emergency_contact, created_at, updated_at) VALUES
(1, 2, 2, 'STU-2024-001', 'Juan', 'Dela Cruz', NULL, '7-A', '7', 'A', 'Parent', '09171234567', '09171234567', NOW(), NOW()),
(2, 2, 2, 'STU-2024-002', 'Maria', 'Santos', NULL, '7-B', '7', 'B', 'Guardian', '09187654321', '09187654321', NOW(), NOW()),
(3, 2, 1, 'STU-2024-003', 'Pedro', 'Reyes', NULL, '8-A', '8', 'A', NULL, NULL, NULL, NOW(), NOW());
