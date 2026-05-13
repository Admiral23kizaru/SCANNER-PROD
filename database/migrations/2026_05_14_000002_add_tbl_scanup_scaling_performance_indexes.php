<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ScanUp: performance indexes on `tbl_scanup_*` tbls only.
 *
 * PURPOSE: Non-unique indexes for common multi-school query patterns.
 * Runs after `2026_05_14_000000_create_scanup_tbls_in_ehris` so target tbls exist.
 *
 * SAFETY: Never targets EHRIS-owned unprefixed tbls (`users`, `attendance`, etc.) on ehris2.
 *
 * ADDITIVE ONLY: try/catch tolerates duplicate index names on re-run.
 */
return new class extends Migration
{
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

        // `tbl_scanup_personal_access_tokens` already has `scanup_pat_tokenable_idx` from 000000.
    }

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
