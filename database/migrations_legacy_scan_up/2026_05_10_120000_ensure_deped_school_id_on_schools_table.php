<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PURPOSE: Ensure schools can store DepEd IDs for EHRIS bridging.
     * WHY: Older ScanUp databases may lack deped_school_id; EHRIS login provisioning requires it.
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasTable('schools')) {
            return;
        }

        if (! Schema::hasColumn('schools', 'deped_school_id')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('deped_school_id', 50)->nullable()->after('name');
                $table->index('deped_school_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     * PURPOSE: Roll back the column when reversing migrations.
     * WHY: Keeps migration reversible for deployment tooling.
     *
     * @return void
     */
    public function down(): void
    {
        if (! Schema::hasTable('schools')) {
            return;
        }

        if (Schema::hasColumn('schools', 'deped_school_id')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('deped_school_id');
            });
        }
    }
};
