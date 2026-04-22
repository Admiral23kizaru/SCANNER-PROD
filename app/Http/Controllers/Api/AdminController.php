<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;

/**
 * AdminController — top-level admin dashboard entry point.
 */
class AdminController extends Controller
{
    /** Return admin dashboard confirmation plus the current school's info. */
    public function dashboard(): JsonResponse
    {
        $schoolId = auth()->user()->school_id;
        $school   = $schoolId ? School::find($schoolId) : null;

        return response()->json([
            'message' => 'Admin dashboard',
            'school'  => $school,
        ]);
    }
}
