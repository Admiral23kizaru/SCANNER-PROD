<?php

namespace App\Models\Sanctum;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * ScanUp Sanctum token storage — avoids clashing with EHRIS `personal_access_tokens` tbl name.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $table = 'tbl_scanup_personal_access_tokens';
}
