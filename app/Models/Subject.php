<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $table = 'tbl_scanup_subjects';

    protected $fillable = [
        'name',
        'school_id',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'tbl_scanup_student_subject')
            ->withTimestamps();
    }
}

