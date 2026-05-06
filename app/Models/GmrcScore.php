<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmrcScore extends Model
{
    protected $table = 'gmrc_scores';

    protected $fillable = [
        'student_id',
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
}

