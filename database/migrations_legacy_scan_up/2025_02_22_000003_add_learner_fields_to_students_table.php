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
        Schema::table('students', function (Blueprint $table) {
            $table->string('middle_name', 255)->nullable()->after('last_name');
            $table->string('grade', 32)->nullable()->after('grade_section');
            $table->string('section', 32)->nullable()->after('grade');
            $table->string('guardian', 255)->nullable()->after('section');
            $table->string('contact_number', 64)->nullable()->after('guardian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['middle_name', 'grade', 'section', 'guardian', 'contact_number']);
        });
    }
};
