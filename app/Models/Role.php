<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role model — defines access levels (Admin, Teacher, Guard).
 */
class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = ['name'];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    /** All users assigned to this role. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
