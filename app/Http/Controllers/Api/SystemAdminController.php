<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SystemAdminDashboardService;
use Illuminate\Http\JsonResponse;

/**
 * Read-only API endpoints for the division-level System Admin workspace.
 */
class SystemAdminController extends Controller
{
    public function __construct(private SystemAdminDashboardService $dashboard)
    {
    }

    /**
     * Return division-level dashboard counters.
     */
    public function overview(): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->overview(),
        ]);
    }

    /**
     * Return all school rows for the division school selector.
     */
    public function schools(): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->schools(),
        ]);
    }

    /**
     * Return one school's read-only detail panel.
     */
    public function schoolDetail(string $depedSchoolId): JsonResponse
    {
        $detail = $this->dashboard->schoolDetail($depedSchoolId);

        if (!$detail) {
            return response()->json([
                'message' => 'School not found.',
            ], 404);
        }

        return response()->json([
            'data' => $detail,
        ]);
    }
}
