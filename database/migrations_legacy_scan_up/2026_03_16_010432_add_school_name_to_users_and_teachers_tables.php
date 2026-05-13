<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'school_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('school_name')->nullable();
            });
        }

        if (Schema::hasTable('teachers') && !Schema::hasColumn('teachers', 'school_name')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->string('school_name')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'school_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('school_name');
            });
        }

        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'school_name')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropColumn('school_name');
            });
        }
    }
};
