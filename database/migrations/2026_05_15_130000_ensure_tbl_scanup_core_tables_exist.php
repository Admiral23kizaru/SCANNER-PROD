<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures `tbl_scanup_roles`, `tbl_scanup_schools`, `tbl_scanup_users`, and
 * `tbl_scanup_personal_access_tokens` exist on the default DB connection.
 *
 * WHY: Some environments still have legacy `schools` / `users` tables while
 * Eloquent models point at `tbl_scanup_*`. Login then fails with "table not found".
 *
 * ADDITIVE: Only creates missing tables; copies `schools` → `tbl_scanup_schools` when the
 * legacy table exists and the new table is empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_scanup_roles')) {
            Schema::create('tbl_scanup_roles', function (Blueprint $table) {
                $table->tinyIncrements('id');
                $table->string('name', 64)->unique();
                $table->timestamps();
            });
        }

        $this->seedRoles();

        if (! Schema::hasTable('tbl_scanup_schools')) {
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
        }

        $this->copyLegacySchoolsIfNeeded();

        if (! Schema::hasTable('tbl_scanup_users')) {
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
        }

        if (! Schema::hasTable('tbl_scanup_personal_access_tokens')) {
            Schema::create('tbl_scanup_personal_access_tokens', function (Blueprint $table) {
                $table->id();
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
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_scanup_personal_access_tokens')) {
            Schema::dropIfExists('tbl_scanup_personal_access_tokens');
        }
        if (Schema::hasTable('tbl_scanup_users')) {
            Schema::dropIfExists('tbl_scanup_users');
        }
        if (Schema::hasTable('tbl_scanup_schools')) {
            Schema::dropIfExists('tbl_scanup_schools');
        }
        if (Schema::hasTable('tbl_scanup_roles')) {
            Schema::dropIfExists('tbl_scanup_roles');
        }
    }

    private function seedRoles(): void
    {
        if (! Schema::hasTable('tbl_scanup_roles')) {
            return;
        }

        $now = now();
        $roles = [
            1 => 'Admin',
            2 => 'Teacher',
            3 => 'Guard',
            4 => 'Reporting Manager',
            5 => 'Adviser',
            6 => 'Subject Teacher',
            7 => 'System Admin',
        ];

        foreach ($roles as $id => $name) {
            DB::table('tbl_scanup_roles')->updateOrInsert(
                ['id' => $id],
                ['name' => $name, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    private function copyLegacySchoolsIfNeeded(): void
    {
        if (! Schema::hasTable('tbl_scanup_schools')) {
            return;
        }

        if (DB::table('tbl_scanup_schools')->count() > 0) {
            return;
        }

        if (! Schema::hasTable('schools')) {
            return;
        }

        $hasDeped = Schema::hasColumn('schools', 'deped_school_id');

        $now = now();
        $rows = DB::table('schools')->orderBy('id')->get();
        foreach ($rows as $r) {
            DB::table('tbl_scanup_schools')->insert([
                'id' => $r->id,
                'name' => $r->name,
                'deped_school_id' => $hasDeped ? ($r->deped_school_id ?? null) : null,
                'address' => Schema::hasColumn('schools', 'address') ? ($r->address ?? null) : null,
                'contact_number' => Schema::hasColumn('schools', 'contact_number') ? ($r->contact_number ?? null) : null,
                'principal_name' => Schema::hasColumn('schools', 'principal_name') ? ($r->principal_name ?? null) : null,
                'logo_path' => Schema::hasColumn('schools', 'logo_path') ? ($r->logo_path ?? null) : null,
                'created_at' => $r->created_at ?? $now,
                'updated_at' => $r->updated_at ?? $now,
            ]);
        }

        $maxId = (int) DB::table('tbl_scanup_schools')->max('id');
        if ($maxId > 0) {
            DB::statement('ALTER TABLE `tbl_scanup_schools` AUTO_INCREMENT = ' . ($maxId + 1));
        }
    }
};
