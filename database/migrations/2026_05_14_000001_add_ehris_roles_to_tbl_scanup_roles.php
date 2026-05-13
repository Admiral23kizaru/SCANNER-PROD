<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ScanUp: canonical role rows in `tbl_scanup_roles` (stable ids, no Eloquent).
 *
 * PURPOSE: Ensures ids 1–6 exist before FK-heavy data loads; avoids Role model side effects.
 * Runs after `2026_05_14_000000_create_scanup_tbls_in_ehris`.
 *
 * ADDITIVE: `updateOrInsert` by primary key `id` — safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $roles = [
            1 => 'Admin',
            2 => 'Teacher',
            3 => 'Guard',
            4 => 'Reporting Manager',
            5 => 'Adviser',
            6 => 'Subject Teacher',
        ];

        foreach ($roles as $id => $name) {
            DB::table('tbl_scanup_roles')->updateOrInsert(
                ['id' => $id],
                ['name' => $name, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        // Remove EHRIS-oriented roles only; keeps core Admin/Teacher/Guard ids if referenced elsewhere.
        DB::table('tbl_scanup_roles')->whereIn('id', [4, 5, 6])->delete();
    }
};
