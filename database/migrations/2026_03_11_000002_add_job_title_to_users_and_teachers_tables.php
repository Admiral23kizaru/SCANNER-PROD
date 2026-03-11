<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'job_title')) {
            Schema::table('users', function (Blueprint $table) {
                // Do not rely on a specific column order (some schemas do not have `school_id`).
                $table->string('job_title', 50)->nullable();
            });
        }

        // Optional compatibility: some deployments may use a dedicated `teachers` table.
        if (Schema::hasTable('teachers') && !Schema::hasColumn('teachers', 'job_title')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->string('job_title', 50)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'job_title')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('job_title');
            });
        }

        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'job_title')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropColumn('job_title');
            });
        }
    }
};

