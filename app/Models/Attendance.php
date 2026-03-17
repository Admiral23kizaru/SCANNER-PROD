<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance model — each row represents a single QR scan event.
 *
 * Linked to a student, optionally to the guard (scanner) who recorded it,
 * a school, and an active school year.
 */
class Attendance extends Model
{
    protected $table = 'attendance';

    /* ------------------------------------------------------------------ */
    /*  Mass-Assignment                                                    */
    /* ------------------------------------------------------------------ */

    protected $fillable = [
        'student_id',
        'scanned_by',
        'scanned_at',
        'session',
        'status',
        'school_year_id',
        'school_id',
    ];

    /* ------------------------------------------------------------------ */
    /*  Casts                                                              */
    /* ------------------------------------------------------------------ */

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    /** The student who was scanned. */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** The guard/user who performed the scan. */
    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
