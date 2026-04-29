<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AdminController — top-level admin dashboard entry point.
 */
class AdminController extends Controller
{
    /** Return admin dashboard confirmation plus the current school's info. */
    public function dashboard(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user     = $request->user();
        $schoolId = $user->school_id;
        $school   = $schoolId ? School::find($schoolId) : null;

        return response()->json([
            'message' => 'Admin dashboard',
            'school'  => $school,
        ]);
    }
}

