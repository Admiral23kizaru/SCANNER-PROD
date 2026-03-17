<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * School model — represents a school within the Division of Ozamiz City.
 */
class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(SchoolSetting::class);
    }

    public function schoolYears(): HasMany
    {
        return $this->hasMany(SchoolYear::class);
    }
}
