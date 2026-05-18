<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ScannerHeartbeat tracks whether a school scanner terminal is alive.
 */
class ScannerHeartbeat extends Model
{
    protected $table = 'tbl_scanup_scanner_heartbeats';

    protected $fillable = [
        'school_id',
        'deped_school_id',
        'scanner_key',
        'camera_status',
        'last_seen_at',
        'user_agent',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
