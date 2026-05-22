<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_scanup_learning_assessment_files')) {
            return;
        }

        Schema::create('tbl_scanup_learning_assessment_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('title', 255);
            $table->date('analyzed_at');
            $table->string('sheet_title', 100)->nullable();
            $table->string('grade_level', 50)->nullable();
            $table->string('section', 100)->nullable();
            $table->unsignedInteger('student_count')->default(0);
            $table->unsignedInteger('item_count')->default(0);
            $table->string('filename', 255);
            $table->string('file_path', 500);
            $table->json('analysis_payload')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'analyzed_at'], 'scanup_la_files_school_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_scanup_learning_assessment_files');
    }
};
