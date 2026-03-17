# ScanUp — Ozamiz City Schools QR-ID Attendance System

> A full-stack Laravel + Vue.js web application for automated student attendance recording
> via QR code scanning, used across schools in the Division of Ozamiz City, DEPED Region X.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Technical Stack](#2-technical-stack)
3. [Architecture & File Structure](#3-architecture--file-structure)
4. [Data Flow](#4-data-flow)
5. [SMS Integration (Semaphore)](#5-sms-integration-semaphore)
6. [Duplicate Scan Prevention](#6-duplicate-scan-prevention)
7. [Attendance Sessions](#7-attendance-sessions)
8. [Installation & Setup](#8-installation--setup)
9. [Environment Variables](#9-environment-variables)
10. [Running the Queue Worker](#10-running-the-queue-worker)
11. [API Reference](#11-api-reference)

---

## 1. Project Overview

**ScanUp** is a web-based student ID and attendance tracking system built for
Ozamiz City public schools. Each student is issued a QR-code ID card generated
from their 12-digit Learner Reference Number (LRN).

When a student arrives at school, the gate guard scans the QR code using a
tablet or laptop camera. The system:
- Records the scan in the `attendance` table (with session, status, timestamp).
- Displays the student's photo and name on the Guard Terminal screen.
- Sends an SMS message to the student's guardian via the **Semaphore** API.
- Updates live statistics (present / late / absent counts).

The **Admin dashboard** provides school-wide reporting, teacher management,
student CRUD, CSV exports, and chart-based attendance trend visualisation.

---

## 2. Technical Stack

| Layer | Technology |
|---|---|
| Backend | **Laravel 11** (PHP 8.2+) |
| Frontend | **Vue 3** (Composition API, `<script setup>`) |
| Build Tool | **Vite** |
| Styling | **Tailwind CSS** |
| HTTP Client | **Axios** (via centralized service files) |
| Charts | **Chart.js** via `vue-chartjs` |
| QR Scanner | **html5-qrcode** |
| Auth | **Laravel Sanctum** (Bearer token, SPA) |
| Database | **MySQL** (via XAMPP / MariaDB) |
| Queues | **Laravel Database Queue** (`php artisan queue:work`) |
| SMS Gateway | **Semaphore** (`semaphore.co/api/v4/messages`) |
| PDF Generation | **TCPDF** / **Endroid QR Code** |
| Email | **PHPMailer** (SMTP/Gmail) |

---

## 3. Architecture & File Structure

```
SCANNER-PROD/
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── AttendanceController.php   # QR scan processing + stats
│   │   ├── AuthController.php         # Login / logout / user
│   │   ├── AdminController.php        # Admin dashboard entry
│   │   ├── AdminStudentController.php # Admin student CRUD
│   │   ├── IdCardController.php       # Student & teacher ID card PDF
│   │   ├── PasswordResetController.php# OTP email password reset
│   │   ├── StatsController.php        # Dashboard stats & charts
│   │   ├── StudentController.php      # Teacher-scoped student CRUD
│   │   └── TeacherManagementController.php # Admin teacher CRUD
│   ├── Jobs/
│   │   └── SendSmsNotification.php    # Queued Semaphore SMS job
│   ├── Models/
│   │   ├── Attendance.php             # attendance table
│   │   ├── Role.php
│   │   ├── School.php
│   │   ├── SchoolSetting.php
│   │   ├── SchoolYear.php
│   │   ├── Student.php
│   │   ├── Teacher.php
│   │   └── User.php
│   └── Services/
│       ├── IdCardImageService.php     # TCPDF ID card rendering
│       └── MailerService.php          # PHPMailer wrapper
│
├── resources/js/
│   ├── composables/
│   │   ├── useScanner.js              # Guard Terminal logic (camera, debounce, feed)
│   │   ├── useAdminProfile.js         # Reactive admin profile state linked to profile service
│   │   └── useLogout.js               # Reactive logout helper linked to auth service
│   ├── services/
│   │   ├── adminService.js            # /api/admin/* Axios calls
│   │   ├── adminProfileService.js     # /api/admin/profile/* Axios calls
│   │   ├── attendanceService.js       # /api/attendance/* Axios calls
│   │   ├── authService.js             # /api/logout, /api/user Axios calls
│   │   └── studentService.js          # /api/teacher/students/* Axios calls
│   ├── components/
│   │   ├── GuardScanner.vue           # Guard Terminal UI (uses useScanner)
│   │   └── admin/
│   │       ├── AdminDashboardStats.vue
│   │       ├── AdminLayout.vue
│   │       ├── AdminStudentsPage.vue
│   │       └── AdminTeachersPage.vue
│   └── views/
│       └── Login.vue
│
└── routes/
    └── api.php                        # All API routes (controller groups)
```

---

## 4. Data Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                      STUDENT SCAN DATA FLOW                         │
│                                                                     │
│  [Student QR Code]                                                  │
│       │                                                             │
│       ▼                                                             │
│  GuardScanner.vue                                                   │
│  │  html5-qrcode decodes the QR barcode via device camera          │
│  │  onScanSuccess(decodedText) is called                           │
│  │  Client-side debounce check (2500ms cooldown per value)        │
│       │                                                             │
│       ▼                                                             │
│  attendanceService.js → scanAttendancePublic(studentId)            │
│  │  Axios POST /api/attendance/scan                                 │
│  │  Headers: { Content-Type: application/json }                    │
│       │                                                             │
│       ▼                                                             │
│  api.php → POST /attendance/scan                                   │
│  │  Route resolves to: AttendanceController@scanPublic             │
│       │                                                             │
│       ▼                                                             │
│  AttendanceController::scanPublic()                                 │
│  │  1. Validate input                                               │
│  │  2. Resolve student (student_number) or teacher (employee_id)   │
│  │  3. 5-second server-side anti-spam guard                        │
│  │  4. Check session (morning if hour < 12, else afternoon)        │
│  │  5. Guard against duplicate session scan (same student + session)│
│  │  6. Resolve school_id and school settings (late threshold)      │
│  │  7. Determine attendance status: on_time | late                 │
│  │  8. INSERT into attendance table                                 │
│  │  9. Dispatch SendSmsNotification job to queue                   │
│  │  10. Return JSON with student + attendance + live stats         │
│       │                                                             │
│       ▼                                                             │
│  [Queue Worker] php artisan queue:work                             │
│  │  Picks up SendSmsNotification job                               │
│  │  Rapid-fire cooldown check (1 min, per student)                 │
│  │  Session lock check (12 hrs, per student per session)           │
│  │  Normalise phone → 639XXXXXXXXX                                 │
│  │  POST https://semaphore.co/api/v4/messages                      │
│  │  Log success / failure                                           │
│  │  Set Redis/DB cache locks on success                            │
│       │                                                             │
│       ▼                                                             │
│  [Guardian's phone] receives SMS: "ScanUp: Juan dela Cruz has     │
│                      successfully entered the campus at 07:45 AM   │
│                      (Morning)."                                    │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 5. SMS Integration (Semaphore)

ScanUp uses the [Semaphore](https://semaphore.co) SMS gateway to notify student guardians.

### How it works

1. On a successful scan, `AttendanceController` dispatches `SendSmsNotification` to the queue.
2. The queue worker executes the job asynchronously (non-blocking for the Guard Terminal).
3. The job sends an HTTP POST to `https://semaphore.co/api/v4/messages`.

### Message format

```
ScanUp: {first_name} {last_name} has successfully entered the campus at {time} ({session}).
```

Example:
```
ScanUp: Maria Santos has successfully entered the campus at 07:32 AM (Morning).
```

### Phone number normalisation

Semaphore requires the Philippine international format `639XXXXXXXXX`.

| Input format | Normalised |
|---|---|
| `09171234567` (11-digit, starts with 09) | `639171234567` |
| `9171234567` (10-digit, no leading zero) | `639171234567` |
| `639171234567` (already correct) | `639171234567` |

### Required `.env` variables

```dotenv
SEMAPHORE_API_KEY=your_api_key_here
SEMAPHORE_SENDER_NAME=FINGERLINGS
```

---

## 6. Duplicate Scan Prevention

ScanUp has **three independent layers** of duplicate prevention to conserve
Semaphore SMS tokens and prevent database bloat:

### Layer 1 — Vue Client Debounce (2500ms)
**Location:** `useScanner.js → isDebounceLocked()`

The camera decodes QR codes at up to 15 fps. Without debouncing, a single scan
would trigger 30+ API calls per second. The client tracks the last decoded value
and the timestamp it was processed. If the same value arrives again within 2500ms,
it is silently dropped.

```js
// Returns true if the same QR was processed within 2500ms
function isDebounceLocked(value) {
    const now = Date.now();
    return value === lastScannedValue.value && (now - lastScannedAt.value) < DEBOUNCE_MS;
}
```

### Layer 2 — Server Anti-Spam Guard (5 seconds)
**Location:** `AttendanceController::scanPublic()`

Even if the client debounce is bypassed (e.g., two devices scanning the same
student simultaneously), the server checks whether an attendance record for the
same student was created within the last 5 seconds.

### Layer 3 — Session Lock (per student, per session, per day)
**Location:** `AttendanceController::scanPublic()`

Once a student is successfully recorded for the **Morning** session, any further
scans during the morning period return `already_scanned` without inserting a new
row. Same logic for the **Afternoon** session.

### Layer 4 — SMS Cooldown Cache (SendSmsNotification)
**Location:** `SendSmsNotification::handle()`

Even if multiple attendance rows are inserted (e.g. admin overrides), the SMS job:
- Checks a **1-minute rapid-fire key** — prevents SMS if a job ran < 60s ago.
- Checks a **12-hour session key** — one SMS per session per day, per student.

---

## 7. Attendance Sessions

| Session | Trigger condition | Late threshold |
|---|---|---|
| **Morning** | Scan before 12:00 PM | Configurable via `school_settings.late_threshold` |
| **Afternoon** | Scan from 12:00 PM onwards | No late check for afternoon |

The session is determined server-side: `now()->hour < 12 ? 'morning' : 'afternoon'`.

---

## 8. Installation & Setup

### Prerequisites

- PHP 8.2+, Composer
- Node.js 18+, npm
- MySQL (XAMPP recommended for local)
- XAMPP Apache running at `http://localhost`

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/YOUR_ORG/SCANNER-PROD.git
cd SCANNER-PROD

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Copy and configure environment
cp .env.example .env
php artisan key:generate

# 5. Run database migrations
php artisan migrate

# 6. Link the public storage directory
php artisan storage:link

# 7. Build frontend assets
npm run dev
# or for production:
npm run build
```

---

## 9. Environment Variables

```dotenv
# App
APP_NAME="ScanUp"
APP_URL=http://localhost/SCANNER_PROD1/SCANNER-PROD/public

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scanner_db
DB_USERNAME=root
DB_PASSWORD=

# Queue driver (use 'database' for XAMPP — no Redis required)
QUEUE_CONNECTION=database

# Email (PHPMailer via Gmail SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-gmail@gmail.com
MAIL_FROM_NAME="ScanUp"

# Semaphore SMS
SEMAPHORE_API_KEY=your_semaphore_api_key
SEMAPHORE_SENDER_NAME=FINGERLINGS
```

---

## 10. Running the Queue Worker

SMS notifications are processed asynchronously by the Laravel queue worker.
You **must** keep this running for SMS to be delivered.

```bash
# Basic (processes all queued jobs until manually stopped)
php artisan queue:work

# With retry attempts and detailed logging (recommended for production)
php artisan queue:work --tries=3 --timeout=60 --sleep=3

# Run failed jobs (retry SMS that failed due to network errors)
php artisan queue:retry all

# View pending and failed jobs
php artisan queue:failed
```

> **Tip for XAMPP:** Open a dedicated terminal window and run `php artisan queue:work`
> before starting your testing session. The queue worker must be running for any SMS to send.

---

## 11. API Reference

All API routes are prefixed with `/api`. Authenticated routes require the header:
```
Authorization: Bearer <token>
```

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/api/login` | ❌ | Authenticate and receive Bearer token |
| `POST` | `/api/logout` | ✅ | Revoke current token |
| `GET` | `/api/user` | ✅ | Get current authenticated user |
| `POST` | `/api/attendance/scan` | ❌ | Process a QR scan (public Guard Terminal) |
| `GET` | `/api/attendance/public/recent` | ❌ | Today's recent scan feed |
| `GET` | `/api/guard/stats` | ✅ | Live attendance stat counts |
| `GET` | `/api/admin/dashboard/stats` | ✅ Admin | School-wide dashboard stats (cached 3 min) |
| `GET` | `/api/admin/attendance/trends` | ✅ Admin | Time-series chart data |
| `GET` | `/api/admin/teachers` | ✅ Admin | List all teachers |
| `POST` | `/api/admin/teachers` | ✅ Admin | Create teacher account |
| `PUT` | `/api/admin/teachers/{id}` | ✅ Admin | Update teacher |
| `DELETE` | `/api/admin/teachers/{id}` | ✅ Admin | Delete teacher |
| `GET` | `/api/admin/students` | ✅ Admin | List all students (paginated) |
| `POST` | `/api/admin/students` | ✅ Admin | Create student |
| `PUT` | `/api/admin/students/{id}` | ✅ Admin | Update student |
| `DELETE` | `/api/admin/students/{id}` | ✅ Admin | Delete student |
| `GET` | `/api/teacher/students` | ✅ Teacher | Teacher's own student list |
| `POST` | `/api/teacher/students/import` | ✅ Teacher | Bulk import via Excel/CSV |
| `POST` | `/api/password/request-otp` | ❌ | Send OTP to email for password reset |
| `POST` | `/api/password/verify-otp` | ❌ | Verify OTP code |
| `POST` | `/api/password/reset` | ❌ | Apply new password |

---

## License

For academic and institutional use within the Division of Ozamiz City, DEPED Region X.
