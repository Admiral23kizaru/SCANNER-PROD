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
        'school_id',
        'status',
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
        // Prefer the actual DB column value if present.
        // Important: Super Admin accounts intentionally have school_id = NULL.
        if (array_key_exists('school_id', $this->attributes) && $this->attributes['school_id'] !== null) {
            return (int) $this->attributes['school_id'];
        }

        // If this is an Admin account and the DB column is NULL, treat it as Super Admin.
        // Do not "infer" a school from school_name/teachers table, otherwise the UI
        // cannot reliably show Super Admin-only features (e.g. Create School Account).
        if (($this->attributes['role_id'] ?? null) === 1) {
            return null;
        }

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

        // If we cannot resolve a school, keep NULL (do not default to "first school"),
        // otherwise role-based UI (Super Admin) cannot be detected reliably.
        return null;
    }
}
