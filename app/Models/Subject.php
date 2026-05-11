<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $table = 'subjects';

    protected $fillable = [
        'name',
        'school_id',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_subject')
            ->withTimestamps();
    }
}

