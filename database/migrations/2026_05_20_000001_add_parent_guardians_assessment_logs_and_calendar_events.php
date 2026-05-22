<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_scanup_parent_guardians')) {
            Schema::create('tbl_scanup_parent_guardians', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('student_id')->nullable();
                $table->foreignId('school_id')->constrained('tbl_scanup_schools')->cascadeOnDelete();
                $table->string('name');
                $table->string('relationship', 80)->default('Guardian');
                $table->string('contact_number', 80)->nullable();
                $table->string('email')->nullable();
                $table->string('address', 500)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('tbl_scanup_students')->nullOnDelete();
                $table->index(['school_id', 'relationship']);
            });
        }

        if (! Schema::hasTable('tbl_scanup_assessment_logs')) {
            Schema::create('tbl_scanup_assessment_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('student_id')->nullable();
                $table->foreignId('subject_id')->nullable()->constrained('tbl_scanup_subjects')->nullOnDelete();
                $table->foreignId('school_id')->constrained('tbl_scanup_schools')->cascadeOnDelete();
                $table->string('school_year', 20)->nullable();
                $table->string('grade_level', 50)->nullable();
                $table->string('section', 100)->nullable();
                $table->string('assessment_type', 100)->default('Semestral Assessment');
                $table->unsignedSmallInteger('score')->default(0);
                $table->unsignedSmallInteger('total_items')->default(0);
                $table->json('least_mastered_skills')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('student_id')->references('id')->on('tbl_scanup_students')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('tbl_scanup_users')->nullOnDelete();
                $table->index(['school_id', 'school_year', 'grade_level', 'section'], 'scanup_assessment_scope_idx');
            });
        }

        if (! Schema::hasTable('tbl_scanup_calendar_events')) {
            Schema::create('tbl_scanup_calendar_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('tbl_scanup_schools')->cascadeOnDelete();
                $table->string('title');
                $table->date('event_date');
                $table->string('color', 20)->default('#14b8a6');
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('tbl_scanup_users')->nullOnDelete();
                $table->index(['school_id', 'event_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_scanup_calendar_events');
        Schema::dropIfExists('tbl_scanup_assessment_logs');
        Schema::dropIfExists('tbl_scanup_parent_guardians');
    }
};
