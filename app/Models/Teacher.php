<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Teacher model — mirrors a subset of users who hold the "Teacher" role.
 *
 * Linked to a school through the `school_id` foreign key.
 */
class Teacher extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'teachers';

    /* ------------------------------------------------------------------ */
    /*  Mass-Assignment                                                    */
    /* ------------------------------------------------------------------ */

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'designation',
        'profile_photo',
        'job_title',
        'employee_id',
        'school_name',
        'school_id',
    ];

    protected $hidden = [
        'password',
    ];

    /* ------------------------------------------------------------------ */
    /*  Casts                                                              */
    /* ------------------------------------------------------------------ */

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    /** The school this teacher belongs to. */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
