<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolSetting extends Model
{
    protected $fillable = [
        'school_id',
        'logo_path',
        'address',
        'late_threshold',
        'absence_threshold',
    ];
}
