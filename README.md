## ScanUp (School QR Attendance & ID System)

ScanUp is a school-focused system for:

- **Teachers**: managing their class list and generating learner IDs.
- **Guards**: scanning learner QR codes for fast attendance.
- **Administrators**: managing students and teacher accounts.

This project is built with **Laravel** (PHP) on the backend and **Vue 3 + Vite** on the frontend.

---

## Prerequisites

- **PHP** 8.1+ with required extensions (OpenSSL, PDO, Mbstring, GD, etc.)
- **Composer**
- **Node.js** 18+ and **npm**
- A **MySQL/MariaDB** database

---

## Installation

1. **Clone the repository**

   ```bash
   git clone <your-repo-url> SCANNER_PROD
   cd SCANNER_PROD
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Copy and configure `.env`**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Then edit `.env` and set at least:

   - `APP_URL=http://localhost:8000`
   - Database settings (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)

4. **Prepare the database**

   You can either:

   - Run Laravel migrations:

     ```bash
     php artisan migrate
     ```

   - Or import the provided SQL snapshot (if you want a pre-filled database):

     ```bash
     # From the project root
     mysql -u <user> -p <database_name> < database/scan_up_rebuild.sql
     ```

---

## Running the application

You need **two** processes: one for the PHP backend and one for the frontend assets.

1. **Start the Laravel backend**

   ```bash
   php artisan serve
   ```

   By default this will serve the app at `http://localhost:8000`.

2. **Install JS dependencies (first time only)**

   ```bash
   npm install
   ```

3. **Start the Vite dev server**

   ```bash
   npm run dev
   ```

   Keep this process running while you are developing. The frontend will hotreload when you edit Vue/JS/CSS files.

Open your browser at `http://localhost:8000` and log in using the credentials you have configured/seeded.

**Teacher profile photos** require the storage symlink. Run once:

```bash
php artisan storage:link
```

---

## Teacher management (Admin)

From the **Admin** dashboard:

- The **Manage Teachers** screen allows the administrator to:
  - **Create** teacher accounts (name, email/username, password).
  - **Edit** existing teachers (name, email, optional new password).
  - **Delete** teachers (with protection when they have created student records).

The admin UI and API support:

- **Name**
- **Email**
- **Password / Confirm password**
- **School designation** (e.g. Adviser, Principal)
- **Profile photo** (optional, JPG/PNG, max 2 MB) — upload in Create or Edit modal

Teacher profile photos are stored under `storage/app/public/teachers/`. Run `php artisan storage:link` so they are served at `/storage/teachers/...`.

---

## Teacher Dashboard: Bulk Import

Teachers can bulk-import learners from **CSV or Excel** (`.csv`, `.xlsx`, `.xls`):

1. Click **Bulk Import** in the List of Learners section.
2. Select a CSV or Excel file.

**Expected columns** (headers in first row; names are case-insensitive, spaces become underscores):

- `last_name`, `first_name`, `middle_name`
- `student_number` or `lrn` (must be exactly 12 digits)
- `grade`, `section`
- `guardian`, `guardian_email`, `contact_number` (or `parent_email`, `contact`)

Rows with invalid LRN, duplicate LRN, or missing first/last name are skipped. The result shows how many were imported and how many were skipped.

**LRN validation**: When adding or editing a learner manually, the LRN must be exactly 12 digits. The Save/Create button is disabled until the LRN is valid.

---

## Student LRN, QR codes, and unique ID generation

### 1. Unique learner identifier (LRN)

- Each learner has a **Learner Reference Number (LRN)** stored as `student_number` in the `students` table.
- The database enforces **uniqueness**:
  - There is a unique index on `students.student_number`.
  - Validation rules in the API (`StudentController` and `AdminStudentController`) use:

    ```php
    'student_number' => ['required', 'string', 'max:64', 'unique:students,student_number'],
    ```

  - If a duplicate is submitted, the API responds with: **LRN already exists.**

This guarantees that every learners LRN is globally unique in the system.

### 2. What is encoded in the QR code?

- In the **Teacher Dashboard**, when showing a learners QR, the code uses:

  - The **LRN only** (`student_number`), no personal details.

- In the backend `IdCardController`, when generating printable student IDs (PDF), the QR payload is also:

  ```php
  $qrPayload = $lrn; // Minimal payload: numeric ID only
  ```

So the QR code is intentionally minimal: it uniquely identifies the student by LRN, without embedding names or other personal information.

### 3. Secure ID download link (signed URL)

When a teacher clicks **Make ID** in the Teacher Dashboard:

1. The frontend calls:

   ```text
   GET /api/teacher/students/{id}/id-url
   ```

2. `IdCardController::getSignedUrl`:

   - Verifies that the current user is allowed to access that student.
   - Computes a **hash** from the LRN and the application key:

     ```php
     $hash = md5($student->student_number . config('app.key'));
     ```

   - Generates a **temporary signed route** (`URL::temporarySignedRoute`) that:
     - Includes the `hash` and `id` parameters.
     - Has a short expiration (5 minutes).

3. The browser opens this signed URL, which hits `IdCardController::generateSecure`:

   - Validates the signed URL (`$request->hasValidSignature()`).
   - Recomputes the expected hash using the stored `student_number` and `APP_KEY`.
   - If the hash does not match, the request is rejected.
   - If everything is valid, a **PDF ID card** is generated on the fly. The card contains:
     - The **QR code** encoding the LRN.
     - The students name and LRN in human-readable form.
     - Optional student photo, if found at `public/school/{LRN}.png` or `.jpg`.

This combination of:

- **Unique `student_number` (LRN) in the database**, and
- **Hashed LRN + `APP_KEY` inside a short-lived signed URL**

is what gives ScanUp its **unique, secure ID generation** for each learner.

---

## Notes

- If you change `APP_KEY` after IDs have been issued, existing signed links will no longer validate (by design).
- Make sure PHP GD or Imagick and TCPDF are installed on the server; otherwise ID generation will fail (see `IdCardController` for the checks).
