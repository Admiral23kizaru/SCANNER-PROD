# Admin EHRIS Subject Sync

This document explains the Admin `Manage Subjects` EHRIS subject fetch/sync feature.

## Goal

Admin can fetch subject names that already exist in EHRIS for the Admin's assigned school, preview them, and sync selected subjects into the local Project TEA subject table.

The fetch uses two read-only EHRIS sources:

- teacher-specific subject assignments from `tbl_emp_official_subject_taught`
- active master subject names from `tbl_subject_library`, when that table has rows in the live database

EHRIS is treated as read-only. The app does not update EHRIS tables.

## Database Tables Used

### `tbl_scanup_schools`

Purpose:
Stores the local Project TEA school records.

Fields used:

- `id`
  Local school primary key used by Project TEA tables.

- `deped_school_id`
  Matched against EHRIS `tbl_user.department_id`.

Data flow:

- Read by `AdminSubjectController::authSchool()`.
- Used to know which EHRIS school the logged-in Admin belongs to.

### `tbl_scanup_subjects`

Purpose:
Stores local school subjects used by Project TEA.

Fields used:

- `id`
  Subject primary key used by Learning Assessment and learner subject assignment.

- `name`
  Subject display name.

- `school_id`
  Links the subject to one local school.

Data flow:

- Read by `GET /api/admin/subjects`.
- Written by manual Add Subject.
- Written by `POST /api/admin/subjects/sync-ehris`.
- Used by Learning Assessment subject dropdown.

### `tbl_user`

Purpose:
EHRIS user table.

Fields used:

- `hrId`
  Teacher employee/HR identifier.

- `department_id`
  School assignment in EHRIS. Matched to `tbl_scanup_schools.deped_school_id`.

- `role`
  Filtered to `Teacher`.

- `active`
  Filtered to active users only.

- `firstname`, `lastname`
  Used only for preview text in the EHRIS subject modal.

Data flow:

- Read only.
- Used to find active EHRIS teachers in the Admin's school.
- Does not receive writes from Project TEA.

### `tbl_emp_official_subject_taught`

Purpose:
EHRIS teacher-subject assignment table.

Fields used:

- `hrid`
  Matched to EHRIS `tbl_user.hrId`.

- `subject_name`
  Subject name assigned to a teacher.

- `sort_order`
  Used to keep subject preview ordering stable.

Data flow:

- Read only.
- Used by `GET /api/admin/subjects/ehris`.
- Subject names from this table are copied into `tbl_scanup_subjects` only when Admin syncs.

### `tbl_subject_library`

Purpose:
EHRIS master subject library.

Fields used:

- `name`
  Master subject display name.

- `is_active`
  Only active rows are offered in the fetch modal.

Data flow:

- Read only.
- Used by `GET /api/admin/subjects/ehris` as an additional source because some schools have teachers in `tbl_user` but no rows yet in `tbl_emp_official_subject_taught`.
- Subject names from this table are copied into `tbl_scanup_subjects` only when Admin syncs.

## Backend Files

### `app/Http/Controllers/Api/AdminSubjectController.php`

Purpose:
Handles Admin subject management.

Important methods:

- `index()`
  Reads local subjects from `tbl_scanup_subjects` for the logged-in Admin's school.

- `store()`
  Creates one manual local subject in `tbl_scanup_subjects`.

- `update()`
  Updates one local subject in `tbl_scanup_subjects`.

- `destroy()`
  Deletes one local subject from `tbl_scanup_subjects`.

- `ehris()`
  Reads EHRIS subject names for the Admin's school.
  It does not write data.

- `syncEhris()`
  Copies selected or all EHRIS subject names into `tbl_scanup_subjects`.
  It skips duplicate subject names for the same school.

- `authSchool()`
  Resolves the logged-in Admin's local school from `school_id`.

- `ehrisSubjectRowsForSchool()`
  Finds EHRIS teachers for the selected school and groups their assigned subjects.
  It also merges active `tbl_subject_library` rows as master-list options.

## API Routes

### `GET /api/admin/subjects`

Reads:

- `tbl_scanup_subjects`

Returns:

- Current local subjects for the Admin's school.

### `GET /api/admin/subjects/ehris`

Reads:

- `tbl_scanup_schools`
- `tbl_user`
- `tbl_emp_official_subject_taught`
- `tbl_subject_library`
- `tbl_scanup_subjects`

Returns:

- Subject names found in EHRIS for teachers in the Admin's school.
- Teacher count per subject.
- Sample teacher names.
- Whether each subject already exists in `tbl_scanup_subjects`.

Writes:

- None.

### `POST /api/admin/subjects/sync-ehris`

Reads:

- `tbl_scanup_schools`
- `tbl_user`
- `tbl_emp_official_subject_taught`
- `tbl_scanup_subjects`

Writes:

- `tbl_scanup_subjects`

Request body:

```json
{
  "subjects": ["English", "Science"]
}
```

If `subjects` is omitted, the endpoint syncs all available EHRIS subjects for that school.

## Frontend File

### `resources/js/components/admin/ManageSubjects.vue`

Purpose:
Admin UI for local subject management and EHRIS subject import.

Important UI actions:

- `Add Subject`
  Opens the manual subject creation modal.

- `Fetch EHRIS Subjects`
  Opens the EHRIS preview modal.

- `Refresh`
  Calls `GET /api/admin/subjects/ehris`.

- `Sync Selected`
  Calls `POST /api/admin/subjects/sync-ehris` with selected subject names.

- `Sync All`
  Calls `POST /api/admin/subjects/sync-ehris` without a subject list.

## Data Flow

1. Admin opens `Manage Subjects`.
2. Vue calls `GET /api/admin/subjects`.
3. Backend returns local subjects from `tbl_scanup_subjects`.
4. Admin clicks `Fetch EHRIS Subjects`.
5. Vue calls `GET /api/admin/subjects/ehris`.
6. Backend finds the Admin school from `tbl_scanup_schools`.
7. Backend uses `deped_school_id` to find EHRIS teachers in `tbl_user`.
8. Backend uses teacher `hrId` values to read `tbl_emp_official_subject_taught`.
9. Backend reads active `tbl_subject_library` rows when available.
10. Backend merges both sources by subject name and returns them to the modal with a source label.
11. Admin syncs selected/all subjects.
12. Backend inserts missing subjects into `tbl_scanup_subjects`.
13. Learning Assessment and System Admin teacher views can now use those subjects through the existing local subject list.

## Why EHRIS Is Not Written

EHRIS is the source system.

Project TEA only reads:

- `tbl_user`
- `tbl_emp_official_subject_taught`
- `tbl_subject_library`

Project TEA only writes:

- `tbl_scanup_subjects`

This prevents accidental changes to official EHRIS records.
