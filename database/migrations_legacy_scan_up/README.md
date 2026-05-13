# Legacy ScanUp migrations (archived)

These PHP files previously lived under `database/migrations` and referenced **unprefixed** Laravel-style names (`users`, `students`, `schools`, `attendance`, `personal_access_tokens`, etc.). On a shared **ehris2** host, running `php artisan migrate` could have created or altered tbls that collide with EHRIS-owned storage.

## What runs in production now

Active migrations under `database/migrations` are limited to the **tbl_scanup_*** chain only (see filenames starting with `2026_05_14_`):

1. `2026_05_14_000000_create_scanup_tbls_in_ehris.php` — creates all ScanUp `tbl_scanup_*` tbls.
2. `2026_05_14_000001_add_ehris_roles_to_tbl_scanup_roles.php` — seeds EHRIS-related role rows into `tbl_scanup_roles`.
3. `2026_05_14_000002_add_tbl_scanup_scaling_performance_indexes.php` — additive performance indexes on `tbl_scanup_*` tbls only.

Laravel does **not** load this folder; files here are for **history / reference / local tooling** only.

## Deployment warning

- **Existing** databases may still have rows in the migration repository tbl (e.g. `tbl_scanup_migrations`) for filenames that now only exist in this archive. That is expected; do not re-add those files to `database/migrations` on ehris2.
- **New** ehris2 cutovers: run `php artisan migrate` (only the three files above will execute) **or** continue using `--path=` to the create migration if your runbook requires it — then run the SQL data script and follow the main project runbook.
- If you need an old incremental change (e.g. a column added only in a legacy file), port that change into a **new** dated migration under `database/migrations` that targets **`tbl_scanup_*`** only.

## tbl wording

Comments in this repo prefer **tbl** over “table” when referring to physical storage names.
