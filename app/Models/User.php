<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model - represents Admin, Teacher, and Guard accounts.
 *
 * Teachers are stored here with role_id pointing to the "Teacher" role.
 * The virtual school_id accessor resolves the school through fallback layers.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'tbl_scanup_users';

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

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'created_by');
    }

    /**
     * Resolve the user's school_id through a cascade:
     * 1. Actual DB column value
     * 2. Teacher profile
     * 3. school_name field to schools lookup
     */
    public function getSchoolIdAttribute(): ?int
    {
        if (array_key_exists('school_id', $this->attributes) && $this->attributes['school_id'] !== null) {
            return (int) $this->attributes['school_id'];
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

        return null;
    }
}
