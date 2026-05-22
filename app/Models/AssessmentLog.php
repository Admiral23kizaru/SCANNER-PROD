<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentLog extends Model
{
    protected $table = 'tbl_scanup_assessment_logs';

    protected $fillable = [
        'student_id',
        'subject_id',
        'school_id',
        'school_year',
        'grade_level',
        'section',
        'assessment_type',
        'score',
        'total_items',
        'least_mastered_skills',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'least_mastered_skills' => 'array',
        'score' => 'integer',
        'total_items' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
