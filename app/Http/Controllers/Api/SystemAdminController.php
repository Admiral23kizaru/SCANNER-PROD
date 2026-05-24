<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SystemAdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Return all learning areas configured by schools.
     */
    public function subjects(): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->subjects(),
        ]);
    }

    public function classes(): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->classes(),
        ]);
    }

    public function attendance(): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->attendance(),
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

    /**
     * Return one school's read-only dashboard snapshot.
     */
    public function schoolDashboard(Request $request, string $depedSchoolId): JsonResponse
    {
        $dashboard = $this->dashboard->schoolDashboard($depedSchoolId, $request->only([
            'group_by',
            'grade',
            'section',
        ]));

        if (!$dashboard) {
            return response()->json([
                'message' => 'School not found.',
            ], 404);
        }

        return response()->json([
            'data' => $dashboard,
        ]);
    }

    /**
     * Export division school setup status as CSV.
     */
    public function exportSchools(): StreamedResponse
    {
        $rows = $this->dashboard->schools();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Department ID',
                'School Name',
                'School Type',
                'School Head',
                'Assigned Admin',
                'Students',
                'Teachers',
                'Scans Today',
                'Setup Status',
                'Health',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['deped_school_id'],
                    $row['school_name'],
                    $row['school_type'],
                    $row['school_head']['name'] ?? '',
                    $row['assigned_admin']['name'] ?? '',
                    $row['students'],
                    $row['teachers'],
                    $row['attendance_today'],
                    $row['setup_status'],
                    $row['health']['label'] ?? '',
                ]);
            }

            fclose($handle);
        }, 'scanup-division-school-status.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
