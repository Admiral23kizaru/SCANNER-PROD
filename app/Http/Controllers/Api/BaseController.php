<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * Base API controller for shared school-scoping guards.
 */
abstract class BaseController extends Controller
{
    /**
     * PURPOSE: Returns the authenticated user's school_id for school-scoped endpoints.
     * FIX: Enforces non-null school_id so queries can no longer silently fall back to all schools.
     * LIMITATION: Assumes all callers are authenticated via Sanctum and should be school-scoped.
     *
     * @return int
     */
    protected function getAuthSchoolId(): int
    {
        $schoolId = request()->user()?->school_id;

        if (!$schoolId) {
            abort(403, 'Account is not assigned to a school. Contact your administrator.');
        }

        return (int) $schoolId;
    }
}

