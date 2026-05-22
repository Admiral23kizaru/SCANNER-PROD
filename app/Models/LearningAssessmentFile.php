<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningAssessmentFile extends Model
{
    protected $table = 'tbl_scanup_learning_assessment_files';

    protected $fillable = [
        'school_id',
        'created_by',
        'subject_id',
        'title',
        'analyzed_at',
        'sheet_title',
        'grade_level',
        'section',
        'student_count',
        'item_count',
        'filename',
        'file_path',
        'analysis_payload',
    ];

    protected $casts = [
        'analyzed_at' => 'date:Y-m-d',
        'analysis_payload' => 'array',
        'student_count' => 'integer',
        'item_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
