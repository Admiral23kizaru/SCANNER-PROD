<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * AdminController — top-level admin dashboard entry point.
 */
class AdminController extends Controller
{
    /** Return admin dashboard status confirmation. */
    public function dashboard(): JsonResponse
    {
        return response()->json(['message' => 'Admin dashboard']);
    }
}
