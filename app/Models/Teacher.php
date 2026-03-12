<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Teacher extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'teachers';

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

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
