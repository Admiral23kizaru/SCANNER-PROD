<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SchoolYear model — represents an academic year (e.g. SY 2025-2026).
 */
class SchoolYear extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'is_active',
    ];

    /* ------------------------------------------------------------------ */
    /*  Casts                                                              */
    /* ------------------------------------------------------------------ */

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
