<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration: add_ehris_roles
 *
 * PURPOSE: Adds 3 new roles for EHRIS-sourced users.
 * - Reporting Manager: from EHRIS tbl_reporting_manager
 * - Adviser: assigned inside ScanUp by RM (Phase 2)
 * - Subject Teacher: assigned inside ScanUp by RM
 *
 * ADDITIVE ONLY: existing roles untouched.
 * Uses firstOrCreate — safe to re-run.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * PURPOSE: Ensure EHRIS-related ScanUp roles exist.
     * WHY: Provision and RBAC need stable role names.
     *
     * @return void
     */
    public function up(): void
    {
        Role::firstOrCreate(
            ['name' => 'Reporting Manager']
        );
        Role::firstOrCreate(
            ['name' => 'Adviser']
        );
        Role::firstOrCreate(
            ['name' => 'Subject Teacher']
        );
    }

    /**
     * Reverse the migrations.
     * PURPOSE: Remove EHRIS-only roles when rolling back.
     * WHY: Keeps migration reversible without touching core roles.
     *
     * @return void
     */
    public function down(): void
    {
        Role::whereIn('name', [
            'Reporting Manager',
            'Adviser',
            'Subject Teacher',
        ])->delete();
    }
};
