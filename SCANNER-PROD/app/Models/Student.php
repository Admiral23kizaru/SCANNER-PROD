<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $table = 'students';

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
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
