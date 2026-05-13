<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Upgrade notification_preference from ENUM('email','sms') to TINYINT(0/1/2)
 * and add last_sms_sent_date for the per-day Regular SMS tracking.
 *
 * 0 = No SMS  — email only (always sent)
 * 1 = Regular SMS — one SMS per day + email
 * 2 = VIP SMS — SMS on every scan + email
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Step 1: Add a temp integer column next to the existing enum column.
         */
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedTinyInteger('notification_pref_int')->default(0)->after('notification_preference');
        });

        // Migrate existing data: 'sms' → 1, everything else → 0
        DB::statement("
            UPDATE students
            SET notification_pref_int = CASE
                WHEN notification_preference = 'sms' THEN 1
                ELSE 0
            END
        ");

        // Drop the old enum column
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('notification_preference');
        });

        // Rename temp column to the canonical name
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('notification_pref_int', 'notification_preference');
        });

        // Add per-day Regular SMS tracking column — in a separate call after rename completes
        Schema::table('students', function (Blueprint $table) {
            $table->date('last_sms_sent_date')->nullable()->after('notification_preference');
        });
    }


    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['last_sms_sent_date']);
        });

        // Add temp column
        Schema::table('students', function (Blueprint $table) {
            $table->enum('notification_preference_old', ['email', 'sms'])->default('email')->after('notification_preference');
        });

        DB::statement("
            UPDATE students
            SET notification_preference_old = CASE
                WHEN notification_preference = 1 OR notification_preference = 2 THEN 'sms'
                ELSE 'email'
            END
        ");

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('notification_preference');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('notification_preference_old', 'notification_preference');
        });
    }
};
