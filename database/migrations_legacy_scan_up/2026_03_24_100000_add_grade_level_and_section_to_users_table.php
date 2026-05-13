<?php

/**
 * Action: Implementing Section-based Teacher Assignment and Gender-specific Dashboard Analytics.
 *
 * This migration adds grade_level and section columns to the users table
 * to support Teacher-to-Section assignment.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'grade_level')) {
                $table->string('grade_level', 20)->nullable()->after('school_name');
            }
            if (!Schema::hasColumn('users', 'section')) {
                $table->string('section', 50)->nullable()->after('grade_level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('users', 'grade_level')) $cols[] = 'grade_level';
            if (Schema::hasColumn('users', 'section'))     $cols[] = 'section';
            if ($cols) $table->dropColumn($cols);
        });
    }
};
