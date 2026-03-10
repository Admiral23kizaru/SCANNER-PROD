<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocatorSlip extends Model
{
    protected $fillable = [
        'teacher_id',
        'date_of_filing',
        'name',
        'position',
        'permanent_station',
        'destination',
        'purpose_of_travel',
        'official_type',
        'date_time',
        'time_out',
        'expected_return',
        'status',
        'admin_remarks',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'date_of_filing' => 'date',
        'date_time'      => 'datetime',
        'reviewed_at'    => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
