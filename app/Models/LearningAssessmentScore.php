<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningAssessmentScore extends Model
{
    protected $table = 'tbl_scanup_gmrc_scores';

    protected $fillable = [
        'student_id',
        'subject_id',
        'section',
        'grade_level',
        'wrong_items',
        'total_items',
        'score',
    ];

    protected $casts = [
        'wrong_items' => 'array',
        'total_items' => 'integer',
        'score' => 'integer',
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
