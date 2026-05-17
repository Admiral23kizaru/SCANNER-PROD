# ScanUp EHRIS Fetch and BAT Launcher Logic

This document explains how ScanUp reads EHRIS data, how the admin **Fetch from EHRIS** button works, and how the BAT launcher opens the live scanner with school isolation.

## Core Rule

EHRIS tbls are treated as read-only.

ScanUp only reads from EHRIS-owned tbls:

- `tbl_user`
- `tbl_depart`
- `tbl_reporting_manager`

ScanUp writes only to ScanUp-owned tbls:

- `tbl_scanup_schools`
- `tbl_scanup_teachers`
- `tbl_scanup_users`
- `tbl_scanup_personal_access_tokens`
- `tbl_scanup_attendance`
- other `tbl_scanup_*` tbls

Do not update, delete, truncate, or alter EHRIS-owned tbls from ScanUp.

## EHRIS Tbls Used

### `tbl_depart`

Purpose: Official EHRIS school/department directory.

Important columns:

- `department_id` - DepEd School ID, for example `304168`
- `department_name` - official school name
- `business_id` - district/grouping value from EHRIS

ScanUp use:

- Used to verify that a DepEd School ID exists.
- Used to resolve school name when `tbl_scanup_schools` does not yet have the school.
- Example: `304168` resolves to `Ozamiz City School of Arts and Trades`.

### `tbl_user`

Purpose: EHRIS employee/user account source.

Important columns:

- `userId` - EHRIS primary user ID
- `hrId` - employee ID, preferred for ScanUp teacher `employee_id`
- `email` - EHRIS login email
- `password` - EHRIS bcrypt password hash, checked with Laravel `Hash::check`
- `firstname`, `lastname`, `fullname`
- `job_title`
- `role`
- `active`
- `department_id` - DepEd School ID / school assignment

ScanUp use:

- Teacher fetch reads active EHRIS users with `role = 'Teacher'`.
- Login reads active users by email.
- BAT principal login reads active Reporting Manager/principal candidates.

Active rule:

```sql
active = 1
```

School isolation rule:

```sql
tbl_user.department_id = tbl_scanup_schools.deped_school_id
```

### `tbl_reporting_manager`

Purpose: Official EHRIS principal/reporting-manager mapping per school.

Important columns:

- `department_id` - DepEd School ID
- `manager_name` - EHRIS `tbl_user.userId` of the Reporting Manager

ScanUp use:

- Detects Reporting Manager role during EHRIS login.
- BAT scanner login can accept the mapped Reporting Manager/principal EHRIS password.
- Data isolation requires both `tbl_reporting_manager.department_id` and `tbl_user.department_id` to match the submitted DepEd School ID.

## Admin: Fetch from EHRIS Button

Admin location: Teacher Management page.

Frontend flow:

- Button opens the EHRIS preview modal.
- Calls `GET /api/admin/teachers/ehris`.
- Optional search filters by first name, last name, `hrId`, `userId`, or email.
- Preview is read-only from EHRIS.
- Admin can sync selected rows or all rows in preview.

Backend endpoint:

```http
GET /api/admin/teachers/ehris
```

Main backend logic:

- Gets the authenticated admin's ScanUp `school_id`.
- Loads that ScanUp school from `tbl_scanup_schools`.
- Requires `tbl_scanup_schools.deped_school_id`.
- Queries EHRIS `tbl_user` using:

```sql
active = 1
role = 'Teacher'
department_id = {admin_school_deped_school_id}
```

Returned preview fields:

- `ehris_user_id`
- `employee_id`
- `name`
- `email`
- `job_title`
- `department_id`
- `is_synced`

The `is_synced` flag checks whether the teacher already exists in ScanUp for the same school.

## Admin: Sync from EHRIS

Backend endpoint:

```http
POST /api/admin/teachers/sync-ehris
```

Payload options:

```json
{}
```

Syncs all active EHRIS teachers for the admin's school.

```json
{
  "employee_ids": ["19086", "19094"]
}
```

Syncs only selected teachers from the preview.

EHRIS read filter:

```sql
tbl_user.active = 1
tbl_user.role = 'Teacher'
tbl_user.department_id = current_admin_school_deped_id
```

ScanUp writes:

- Creates or updates `tbl_scanup_teachers`
- Creates, updates, or restores `tbl_scanup_users`
- Assigns `role_id` for ScanUp `Teacher`
- Sets `school_id` to the authenticated admin's school
- Sets `school_name` from `tbl_scanup_schools.name`
- Sets `status = 'active'`

EHRIS is not modified during sync.

## EHRIS Login Logic

Teacher and Reporting Manager login can use EHRIS credentials.

Flow:

1. Find active EHRIS user in `tbl_user` by email.
2. Require `active = 1`.
3. Verify password using `Hash::check` against `tbl_user.password`.
4. Check `tbl_reporting_manager.manager_name = tbl_user.userId`.
5. Map role:
   - if user is in `tbl_reporting_manager`, ScanUp role is `Reporting Manager`
   - otherwise ScanUp role is `Teacher`
6. Resolve school:
   - first look in `tbl_scanup_schools.deped_school_id`
   - fallback read-only lookup in `tbl_depart.department_id`
   - create only a ScanUp mirror row in `tbl_scanup_schools` if needed
7. Create, update, or restore the ScanUp login row in `tbl_scanup_users`.

ScanUp never stores or changes the real EHRIS password. EHRIS-authenticated ScanUp users get a random local password because real authentication stays in EHRIS.

## BAT Launcher Logic

BAT file purpose:

- Let a guard choose a DepEd School ID.
- Verify the school on the live ScanUp API.
- Authenticate the scanner session.
- Open Chrome/Edge directly to the school-scoped scanner.

Live base URL:

```text
http://58.69.118.16:85/qrid
```

### Step 1: Check School

BAT calls:

```http
GET /api/school/check/{deped_school_id}
```

Backend behavior:

- First checks `tbl_scanup_schools.deped_school_id`.
- If missing, reads EHRIS `tbl_depart.department_id`.
- Returns school name and principal/RM information when available.
- Does not modify EHRIS.

Example:

```text
304168 -> Ozamiz City School of Arts and Trades
```

### Step 2: Login Scanner

BAT calls:

```http
POST /api/guard/login
```

Payload:

```json
{
  "deped_school_id": "304168",
  "password": "entered-password"
}
```

Login accepts either:

- Existing local ScanUp scanner password for `school{deped_id}@deped.ozamiz.edu.ph`
- EHRIS password of the school's mapped Reporting Manager/principal

EHRIS principal validation:

- `tbl_reporting_manager.department_id` must match submitted DepEd School ID.
- `tbl_reporting_manager.manager_name` must match `tbl_user.userId`.
- `tbl_user.department_id` must match the same DepEd School ID.
- `tbl_user.active = 1`.
- Password must match `tbl_user.password`.

If successful, ScanUp creates or updates a local scanner user in `tbl_scanup_users` and issues a Sanctum token in `tbl_scanup_personal_access_tokens`.

### Step 3: Open Scanner

BAT opens:

```text
http://58.69.118.16:85/qrid/scanner?deped_id=304168&token={token}
```

Frontend behavior:

- Stores `token` in `localStorage` as `scan_up_token`.
- Stores DepEd ID in `localStorage` as `scan_up_deped_id`.
- Stores school name in `localStorage` as `scan_up_school_name`.
- Removes `token` from the visible browser URL.
- Keeps `deped_id` visible for school isolation:

```text
http://58.69.118.16:85/qrid/scanner?deped_id=304168
```

Why `deped_id` stays visible:

- It is not a secret.
- It shows which school owns the scanner session.
- It prevents invisible stale-school confusion.
- Stats, recent feed, and scan requests all use this school context.

## Scanner Data Isolation

Every public scanner request includes the DepEd School ID:

- scan submission
- recent attendance feed
- dashboard stat cards

Backend isolation:

- Resolves `deped_id` to `tbl_scanup_schools`.
- If a Sanctum token exists, the token user's `school_id` must match the resolved school.
- A token for School A cannot scan or read stats for School B by changing the URL.

Frontend isolation:

- If `/scanner` has no `deped_id` and there is no saved school, camera/scanning is blocked.
- If a saved school exists, `/scanner` is restored to `/scanner?deped_id={saved_id}`.
- Header displays the resolved school name, not `UNKNOWN SCHOOL`.

## Admin-Side Data Ownership

Admin teacher management is scoped by the authenticated admin's `school_id`.

Admin can only:

- preview EHRIS teachers for their own school DepEd ID
- sync teachers for their own school
- list teachers scoped to their own school
- update/delete teachers owned by their school

Admin cannot fetch all EHRIS teachers globally from the Fetch button. The query is always narrowed by:

```sql
tbl_user.department_id = current_admin_school.deped_school_id
```

## Example: DepEd School ID 304168

EHRIS school:

```text
tbl_depart.department_id = 304168
tbl_depart.department_name = Ozamiz City School of Arts and Trades
```

Principal/RM mapping:

```text
tbl_reporting_manager.department_id = 304168
tbl_reporting_manager.manager_name = 21490
tbl_user.userId = 21490
tbl_user.email = jean.alindo@deped.gov.ph
tbl_user.role = Reporting Manager
tbl_user.job_title = School Principal I
tbl_user.active = 1
```

Teacher fetch example:

```text
tbl_user.department_id = 304168
tbl_user.role = Teacher
tbl_user.active = 1
```

One sample active teacher from the backup:

```text
Name: Genevive Sumondong
Email: genevive.sumondong@deped.gov.ph
Role: Teacher
Job Title: Master Teacher II
Department ID: 304168
```

## Safe Read-Only SQL Checks

Check school in EHRIS:

```sql
SELECT department_id, department_name
FROM ehris2.tbl_depart
WHERE department_id = 304168;
```

Check mapped principal/RM:

```sql
SELECT rm.department_id, rm.manager_name, u.userId, u.email, u.fullname, u.job_title, u.role, u.active
FROM ehris2.tbl_reporting_manager rm
JOIN ehris2.tbl_user u ON u.userId = rm.manager_name
WHERE rm.department_id = 304168;
```

Check active EHRIS teachers for the school:

```sql
SELECT userId, hrId, email, fullname, job_title, role, active, department_id
FROM ehris2.tbl_user
WHERE department_id = 304168
  AND role = 'Teacher'
  AND active = 1
ORDER BY lastname, firstname;
```

Check ScanUp mirror school:

```sql
SELECT id, name, deped_school_id
FROM ehris2.tbl_scanup_schools
WHERE deped_school_id = '304168';
```

## Deployment Notes

After deploying frontend or route changes:

```bash
php artisan optimize:clear
npm run build
```

For production, deploy the generated `public/build` output with the updated PHP and JS files.

Do not run old unprefixed migrations against live `ehris2`. ScanUp production data belongs in `tbl_scanup_*` only.
