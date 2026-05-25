# Admin Subject Area Flow

Admin Manage Subjects now uses `tbl_subject_area` as the official source of allowed subject names.

## Tables

### `tbl_subject_area`

Read-only EHRIS table.

Used fields:

- `id`
- `subject_area`

Purpose:

- Provides official subject area names such as English, Science, Mathematics, Filipino, and MAPEH/MSEP.
- Admin can only create/update local subjects using names from this table.

### `tbl_scanup_subjects`

Project TEA local linkage table.

Used fields:

- `id`
- `name`
- `school_id`
- `created_at`
- `updated_at`

Purpose:

- Stores the selected subject areas for one school.
- Keeps stable IDs for Learning Assessment, learner subject assignment, assessment logs, and System Admin filters.

## API

### `GET /api/admin/subjects`

Reads:

- `tbl_scanup_subjects`
- `tbl_subject_area`

Returns:

- `data`: school-local subjects already selected by the Admin.
- `areas`: official subject area names from `tbl_subject_area`.

### `GET /api/admin/subjects/areas`

Reads:

- `tbl_subject_area`

Returns:

- Official subject area names.

### `POST /api/admin/subjects`

Reads:

- `tbl_subject_area`
- `tbl_scanup_subjects`
- `tbl_scanup_schools`

Writes:

- `tbl_scanup_subjects`

Rule:

- The submitted `name` must exist in `tbl_subject_area.subject_area`.

## Removed Flow

Admin no longer fetches subjects from:

- `tbl_emp_official_subject_taught`
- `tbl_subject_library`

The old Fetch EHRIS Subjects button was removed from `ManageSubjects.vue`.
