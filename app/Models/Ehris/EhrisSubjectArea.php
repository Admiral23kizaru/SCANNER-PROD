<?php

namespace App\Models\Ehris;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only EHRIS subject areas (official names for Manage Subjects).
 *
 * TABLE: tbl_subject_area on connection ehris
 */
class EhrisSubjectArea extends Model
{
    protected $connection = 'ehris';

    protected $table = 'tbl_subject_area';

    public $timestamps = false;

    protected $guarded = ['*'];
}
