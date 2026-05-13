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
        /*
         * Protocol Comment: 
         * Source: Database Migration
         * Destination: students table
         * Function: Storing the parent's chosen communication channel (Default: Email/Free).
         */
        Schema::table('students', function (Blueprint $table) {
            $table->enum('notification_preference', ['email', 'sms'])->default('email')->after('emergency_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('notification_preference');
        });
    }
};
