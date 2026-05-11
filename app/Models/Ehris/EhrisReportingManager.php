<?php

namespace App\Models\Ehris;

use Illuminate\Database\Eloquent\Model;

/**
 * EhrisReportingManager — Read-Only Model
 *
 * PURPOSE: Identifies which EHRIS user is the
 * Reporting Manager per school/department.
 *
 * TABLE: tbl_reporting_manager in ehris2
 * CONNECTION: 'ehris' (read-only)
 *
 * KEY FIELDS:
 * - department_id: school DepEd ID (unique)
 * - manager_name: tbl_user.userId of the manager
 *
 * USAGE:
 * EhrisReportingManager::where(
 *   'manager_name', $ehrisUser->userId
 * )->exists()
 */
class EhrisReportingManager extends Model
{
    protected $connection = 'ehris';

    protected $table = 'tbl_reporting_manager';

    public $timestamps = false;

    protected $guarded = ['*'];
}
