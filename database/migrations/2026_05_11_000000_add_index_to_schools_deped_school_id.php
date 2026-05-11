<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schools') || ! Schema::hasColumn('schools', 'deped_school_id')) {
            return;
        }

        $dbName = (string) (config('database.connections.' . config('database.default') . '.database') ?? '');
        $exists = false;
        if ($dbName !== '') {
            $rows = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$dbName, 'schools', 'schools_deped_school_id_index']
            );
            $exists = ! empty($rows);
        }

        if (! $exists) {
            Schema::table('schools', function (Blueprint $table) {
                $table->index('deped_school_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('schools') || ! Schema::hasColumn('schools', 'deped_school_id')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table) {
            $table->dropIndex(['deped_school_id']);
        });
    }
};

