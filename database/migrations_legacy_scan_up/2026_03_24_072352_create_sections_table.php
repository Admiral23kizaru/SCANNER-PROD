<?php

/**
 * Action: Implementing Section Management and fixing school-level data scoping.
 * // Description: create_sections_table - Creates the sections table to support
 * //   formal section-teacher assignment and student grouping.
 * // Author: Antigravity System Agent
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                          // e.g. "Section A"
            $table->string('grade_level', 50);                    // e.g. "Grade 7"
            $table->unsignedInteger('teacher_id')->nullable();    // assigned teacher (users.id)
            $table->unsignedBigInteger('school_id')->nullable();  // school scope
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
        });

        // Add section_id FK to the students table
        if (!Schema::hasColumn('students', 'section_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('section_id')->nullable()->after('section');
                $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'section_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['section_id']);
                $table->dropColumn('section_id');
            });
        }
        Schema::dropIfExists('sections');
    }
};
