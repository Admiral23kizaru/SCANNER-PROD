<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Action: Implementing Section Management and fixing school-level data scoping.
 * // Description: Section - Represents a class section (e.g. "Grade 7 - Section A").
 * //   Each section belongs to one teacher and one school, and has many students.
 * // Author: Antigravity System Agent
 */
class Section extends Model
{
    protected $fillable = [
        'name',
        'grade_level',
        'teacher_id',
        'school_id',
    ];

    /** The teacher assigned to this section. */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** The school this section belongs to. */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /** All students enrolled in this section. */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'section_id');
    }
}
