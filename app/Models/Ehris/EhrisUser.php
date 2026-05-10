<?php

namespace App\Models\Ehris;

use Illuminate\Database\Eloquent\Model;

/**
 * EhrisUser — Read-Only Model
 *
 * PURPOSE: Reads teacher/staff login accounts
 * from EHRIS tbl_user table for authentication.
 *
 * TABLE: tbl_user in ehris2 database
 * CONNECTION: 'ehris' (read-only)
 * PRIMARY KEY: userId (not id)
 *
 * KEY FIELDS:
 * - userId: primary key
 * - hrId: links to tbl_emp_official_info.hrid
 * - email: login identifier
 * - password: bcrypt hash — use Hash::check()
 * - department_id: school DepEd ID
 * - active: 1=active must be enforced
 *
 * NEVER call create(), update(), save(),
 * or delete() on this model — ever.
 */
class EhrisUser extends Model
{
    protected $connection = 'ehris';

    protected $table = 'tbl_user';

    protected $primaryKey = 'userId';

    public $timestamps = false;

    protected $guarded = ['*'];

    /**
     * scopeActive
     * PURPOSE: Only return active EHRIS accounts.
     * WHY: active=0 means deactivated — must not login.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    /**
     * getFullNameAttribute
     * PURPOSE: Combines firstname + lastname
     * for storing in ScanUp users.name.
     * WHY: ScanUp expects a single display name field.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim(
            ($this->firstname ?? '') . ' ' .
            ($this->lastname ?? '')
        );
    }
}
