<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_scanup_teacher_subject_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('tbl_scanup_schools')->cascadeOnDelete();
            $table->unsignedInteger('teacher_id');
            $table->foreignId('subject_id')->constrained('tbl_scanup_subjects')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('tbl_scanup_sections')->cascadeOnDelete();
            $table->string('grade_level', 50);
            $table->timestamps();

            $table->unique(['teacher_id', 'subject_id', 'section_id'], 'scanup_teacher_subject_section_unique');
            $table->index(['school_id', 'teacher_id'], 'scanup_teacher_subject_school_teacher_idx');
            $table->foreign('teacher_id')->references('id')->on('tbl_scanup_users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_scanup_teacher_subject_sections');
    }
};
