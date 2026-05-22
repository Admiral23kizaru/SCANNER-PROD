<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminCalendarEvent extends Model
{
    protected $table = 'tbl_scanup_calendar_events';

    protected $fillable = [
        'school_id',
        'title',
        'event_date',
        'color',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date:Y-m-d',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
