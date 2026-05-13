<?php

/**
 * ScanUp EHRIS coexistence: creates `tbl_scanup_*` tbls on the default connection.
 *
 * Ordering: this file is `2026_05_14_000000_*`. Follow-up migrations on the same day are
 * `000001_add_ehris_roles_to_tbl_scanup_roles` and `000002_add_tbl_scanup_scaling_performance_indexes`.
 * Unprefixed ScanUp history migrations are archived under `database/migrations_legacy_scan_up/`
 * so `php artisan migrate` on ehris2 cannot recreate legacy `users` / `students` / etc.
 *
 * Run after pointing default `mysql` at ehris2 (see project runbook). Prefer targeted `--path=`
 * in production if your runbook requires it.
 * Does not touch EHRIS `tbl_user`, `tbl_attendance`, or other existing EHRIS tbls.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_scanup_roles', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 64)->unique();
            $table->timestamps();
        });

        Schema::create('tbl_scanup_schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('deped_school_id', 50)->nullable()->unique();
            $table->string('address', 500)->nullable();
            $table->string('contact_number', 64)->nullable();
            $table->string('principal_name', 255)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_scanup_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('role_id');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('designation')->nullable();
            $table->string('employee_id', 50)->nullable();
            $table->foreignId('school_id')->nullable()->constrained('tbl_scanup_schools')->nullOnDelete();
            $table->string('profile_photo')->nullable();
            $table->string('job_title', 50)->nullable();
            $table->string('school_name')->nullable();
            $table->string('grade_level', 20)->nullable();
            $table->string('section', 50)->nullable();
            $table->string('signature_path')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->unsignedBigInteger('ehris_user_id')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('tbl_scanup_roles')->cascadeOnUpdate();
        });

        Schema::create('tbl_scanup_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('tbl_scanup_schools')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('designation');
            $table->string('profile_photo')->nullable();
            $table->string('employee_id', 50)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('job_title', 50)->nullable();
            $table->string('school_name')->nullable();
            $table->unsignedBigInteger('ehris_user_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tbl_scanup_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('grade_level', 50);
            $table->unsignedInteger('teacher_id')->nullable();
            $table->foreignId('school_id')->nullable()->constrained('tbl_scanup_schools')->nullOnDelete();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('tbl_scanup_users')->nullOnDelete();
        });

        Schema::create('tbl_scanup_school_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('tbl_scanup_schools')->cascadeOnDelete();
            $table->string('logo_path', 255)->nullable();
            $table->string('address', 500)->nullable();
            $table->time('late_threshold')->default('07:30:00');
            $table->unsignedTinyInteger('absence_threshold')->default(3);
            $table->timestamps();
        });

        Schema::create('tbl_scanup_school_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('tbl_scanup_schools')->cascadeOnDelete();
            $table->string('name', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tbl_scanup_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('school_id')->nullable()->constrained('tbl_scanup_schools')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'name']);
        });

        Schema::create('tbl_scanup_students', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('teacher_id')->nullable();
            $table->unsignedInteger('created_by');
            $table->string('student_number', 64);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 10)->nullable();
            $table->string('middle_name')->nullable();
            $table->string('grade_section', 64)->nullable();
            $table->string('grade', 32)->nullable();
            $table->string('section', 32)->nullable();
            $table->foreignId('section_id')->nullable()->constrained('tbl_scanup_sections')->nullOnDelete();
            $table->string('guardian')->nullable();
            $table->string('guardian_email')->nullable();
            $table->string('contact_number', 64)->nullable();
            $table->string('emergency_contact', 64)->nullable();
            $table->unsignedTinyInteger('notification_preference')->default(0);
            $table->unsignedTinyInteger('notification_pref_int')->default(0);
            $table->date('last_sms_sent_date')->nullable();
            $table->string('guardian_contact', 64)->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedTinyInteger('qr_version')->default(1);
            $table->foreignId('school_id')->constrained('tbl_scanup_schools')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('tbl_scanup_users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('tbl_scanup_users')->cascadeOnUpdate();
            $table->unique(['student_number', 'school_id']);
            $table->index('student_number');
            $table->index(['grade', 'section']);
        });

        Schema::create('tbl_scanup_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('student_id');
            $table->unsignedInteger('scanned_by')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->enum('session', ['morning', 'lunch_out', 'lunch_return', 'dismissal'])->default('morning');
            $table->enum('status', ['on_time', 'late'])->default('on_time');
            $table->foreignId('school_year_id')->nullable()->constrained('tbl_scanup_school_years')->nullOnDelete();
            $table->foreignId('school_id')->constrained('tbl_scanup_schools')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('tbl_scanup_students')->cascadeOnDelete();
            $table->foreign('scanned_by')->references('id')->on('tbl_scanup_users')->nullOnDelete();
            $table->index('scanned_at');
            $table->index('session');
            $table->index('status');
            $table->index('school_year_id');
        });

        Schema::create('tbl_scanup_student_subject', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('tbl_scanup_students')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('tbl_scanup_subjects')->cascadeOnDelete();
            $table->unique(['student_id', 'subject_id']);
            $table->index(['subject_id', 'student_id']);
        });

        Schema::create('tbl_scanup_gmrc_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('student_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('section', 100);
            $table->string('grade_level', 50);
            $table->json('wrong_items')->nullable();
            $table->unsignedSmallInteger('total_items')->default(50);
            $table->unsignedSmallInteger('score');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('tbl_scanup_students')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('tbl_scanup_subjects')->nullOnDelete();
            $table->index(['grade_level', 'section']);
            $table->index(['student_id', 'created_at']);
            $table->index(['subject_id', 'created_at']);
        });

        Schema::create('tbl_scanup_personal_access_tokens', function (Blueprint $table) {
            $table->id();
            // Manual morph columns + short index name (avoids MySQL 64-char identifier limit vs `$table->morphs()`).
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->index(['tokenable_type', 'tokenable_id'], 'scanup_pat_tokenable_idx');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tbl_scanup_password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_scanup_password_resets');
        Schema::dropIfExists('tbl_scanup_personal_access_tokens');
        Schema::dropIfExists('tbl_scanup_gmrc_scores');
        Schema::dropIfExists('tbl_scanup_student_subject');
        Schema::dropIfExists('tbl_scanup_attendance');
        Schema::dropIfExists('tbl_scanup_students');
        Schema::dropIfExists('tbl_scanup_subjects');
        Schema::dropIfExists('tbl_scanup_school_years');
        Schema::dropIfExists('tbl_scanup_school_settings');
        Schema::dropIfExists('tbl_scanup_sections');
        Schema::dropIfExists('tbl_scanup_teachers');
        Schema::dropIfExists('tbl_scanup_users');
        Schema::dropIfExists('tbl_scanup_schools');
        Schema::dropIfExists('tbl_scanup_roles');
    }
};
