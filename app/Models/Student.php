<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Student model — represents enrolled learners in the QR-ID system.
 *
 * Each student has a unique 12-digit LRN (`student_number`),
 * and is optionally linked to a teacher and a school.
 */
class Student extends Model
{
    protected $table = 'students';

    /* ------------------------------------------------------------------ */
    /*  Mass-Assignment                                                    */
    /* ------------------------------------------------------------------ */

    protected $fillable = [
        'teacher_id',
        'created_by',
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'grade_section',
        'grade',
        'section',
        'guardian',
        'guardian_email',
        'contact_number',
        'emergency_contact',
        'photo_path',
        'school_id',
        'notification_preference',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    /** The teacher assigned to this student. */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** The admin/teacher who created this record. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** All attendance records for this student. */
    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
