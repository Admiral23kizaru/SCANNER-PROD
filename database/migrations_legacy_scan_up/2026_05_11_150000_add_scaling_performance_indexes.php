<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: add_scaling_performance_indexes (ScanUp `tbl_scanup_*` only)
 *
 * PURPOSE: Add non-unique indexes on ScanUp tbls for common multi-school query patterns.
 * WHY: Improves lookup time for admin lists, scanner attendance, and auth at scale (~58 schools).
 *
 * SAFETY: Targets only `tbl_scanup_*` names so this migration never touches EHRIS-owned tbls
 * (e.g. `users`, `attendance`, `tbl_user`, `tbl_attendance`) on the same ehris2 database.
 *
 * ADDITIVE ONLY: Uses try/catch so re-runs or duplicate index names do not fail deployments.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * PURPOSE: Create performance indexes where the ScanUp tbl and column exist.
     *
     * @return void
     */
    public function up(): void
    {
        $safeIndex = function (string $tbl, callable $callback): void {
            if (! Schema::hasTable($tbl)) {
                return;
            }
            try {
                Schema::table($tbl, $callback);
            } catch (\Throwable $e) {
                // Index may already exist on some environments.
            }
        };

        $safeIndex('tbl_scanup_users', function (Blueprint $table) {
            $table->index(['school_id', 'role_id'], 'scanup_users_school_role_idx');
        });

        $safeIndex('tbl_scanup_students', function (Blueprint $table) {
            $table->index('school_id', 'scanup_students_school_idx');
            $table->index(['school_id', 'grade', 'section'], 'scanup_students_school_grade_sec_idx');
        });

        $safeIndex('tbl_scanup_attendance', function (Blueprint $table) {
            $table->index(['school_id', 'scanned_at'], 'scanup_attendance_school_scanned_idx');
            $table->index(['school_id', 'student_id'], 'scanup_attendance_school_student_idx');
        });

        $safeIndex('tbl_scanup_teachers', function (Blueprint $table) {
            $table->index(['school_id', 'employee_id'], 'scanup_teachers_school_employee_idx');
        });

        // `tbl_scanup_personal_access_tokens` already defines `scanup_pat_tokenable_idx` on
        // (`tokenable_type`, `tokenable_id`) in `2026_05_14_000000_create_scanup_tbls_in_ehris.php`; no duplicate index here.
    }

    /**
     * Reverse the migrations.
     * PURPOSE: Drop indexes added by this migration when rolling back.
     *
     * @return void
     */
    public function down(): void
    {
        $drop = function (string $tbl, string $index): void {
            if (! Schema::hasTable($tbl)) {
                return;
            }
            try {
                Schema::table($tbl, function (Blueprint $t) use ($index) {
                    $t->dropIndex($index);
                });
            } catch (\Throwable $e) {
            }
        };

        $drop('tbl_scanup_users', 'scanup_users_school_role_idx');
        $drop('tbl_scanup_students', 'scanup_students_school_idx');
        $drop('tbl_scanup_students', 'scanup_students_school_grade_sec_idx');
        $drop('tbl_scanup_attendance', 'scanup_attendance_school_scanned_idx');
        $drop('tbl_scanup_attendance', 'scanup_attendance_school_student_idx');
        $drop('tbl_scanup_teachers', 'scanup_teachers_school_employee_idx');
    }
};
