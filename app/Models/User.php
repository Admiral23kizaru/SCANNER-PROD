<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model — represents Admin, Teacher, and Guard accounts.
 *
 * Teachers are stored here with role_id pointing to the "Teacher" role.
 * The virtual `school_id` accessor resolves the school through multiple fallback layers.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /* ------------------------------------------------------------------ */
    /*  Mass-Assignment                                                    */
    /* ------------------------------------------------------------------ */

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'designation',
        'profile_photo',
        'employee_id',
        'school_name',
        'grade_level',
        'section',
        'job_title',
        'signature_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
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

    /** The role assigned to this user (Admin / Teacher / Guard). */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Students created by this user (when role is Teacher). */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'created_by');
    }

    /* ------------------------------------------------------------------ */
    /*  Accessors                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Resolve the user's school_id through a cascade:
     *   1. Teacher profile (teachers table)
     *   2. school_name field → schools table
     *   3. Fallback → first school in DB
     */
    public function getSchoolIdAttribute(): ?int
    {
        $teacher = Teacher::where('email', $this->email)->first();
        if ($teacher?->school_id) {
            return $teacher->school_id;
        }

        if ($this->school_name) {
            $school = School::where('name', 'like', '%' . $this->school_name . '%')->first();
            if ($school) {
                return $school->id;
            }
        }

        return School::first()?->id;
    }
}
