<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            if (!Schema::hasColumn('admins', 'position')) {
                $table->string('position')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('admins', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('position');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'profile_photo')) {
                $table->dropColumn('profile_photo');
            }

            if (Schema::hasColumn('admins', 'position')) {
                $table->dropColumn('position');
            }

            if (Schema::hasColumn('admins', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};

