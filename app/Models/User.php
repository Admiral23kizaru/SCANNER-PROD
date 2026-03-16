<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'designation',
        'profile_photo',
        'employee_id',
        'school_name',
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

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function getSchoolIdAttribute()
    {
        // 1. Try to find via Teacher profile if this user is a teacher
        $teacher = \App\Models\Teacher::where('email', $this->email)->first();
        if ($teacher && $teacher->school_id) {
            return $teacher->school_id;
        }

        // 2. Try to find via school_name field in users table
        if ($this->school_name) {
            $school = \App\Models\School::where('name', 'like', '%' . $this->school_name . '%')->first();
            if ($school) {
                return $school->id;
            }
        }

        // 3. Fallback to first school
        return \App\Models\School::first()?->id;
    }
}
