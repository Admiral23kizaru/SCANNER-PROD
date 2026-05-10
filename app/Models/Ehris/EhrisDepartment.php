<?php

namespace App\Models\Ehris;

use Illuminate\Database\Eloquent\Model;

/**
 * EhrisDepartment — Read-Only Model
 *
 * PURPOSE: Maps EHRIS department_id to school name.
 * department_id = ScanUp schools.deped_school_id.
 *
 * TABLE: tbl_depart in ehris2
 * CONNECTION: 'ehris' (read-only)
 */
class EhrisDepartment extends Model
{
    protected $connection = 'ehris';

    protected $table = 'tbl_depart';

    public $timestamps = false;

    protected $guarded = ['*'];
}
