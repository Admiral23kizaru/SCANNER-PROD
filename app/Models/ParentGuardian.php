<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentGuardian extends Model
{
    protected $table = 'tbl_scanup_parent_guardians';

    protected $fillable = [
        'student_id',
        'school_id',
        'name',
        'relationship',
        'contact_number',
        'email',
        'address',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
