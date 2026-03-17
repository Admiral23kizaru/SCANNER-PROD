<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SchoolSetting model — per-school configuration (logo, thresholds, etc.).
 */
class SchoolSetting extends Model
{
    protected $fillable = [
        'school_id',
        'logo_path',
        'address',
        'late_threshold',
        'absence_threshold',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
