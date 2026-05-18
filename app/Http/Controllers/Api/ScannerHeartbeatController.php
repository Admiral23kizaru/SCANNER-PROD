<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScannerHeartbeat;
use App\Services\SchoolResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives small "I am online" pings from scanner terminals.
 */
class ScannerHeartbeatController extends Controller
{
    public function __construct(private SchoolResolver $schools)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deped_id' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-]+$/'],
            'scanner_key' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'camera_status' => ['nullable', 'string', 'max:32'],
        ]);

        $school = $this->schools->resolveForScanUpWrite($validated['deped_id']);

        if (!$school) {
            return response()->json([
                'message' => 'School context could not be determined.',
            ], 403);
        }

        $scannerKey = $validated['scanner_key'] ?? 'main-terminal';

        ScannerHeartbeat::updateOrCreate(
            [
                'school_id' => $school->id,
                'scanner_key' => $scannerKey,
            ],
            [
                'deped_school_id' => (string) $school->deped_school_id,
                'camera_status' => $validated['camera_status'] ?? null,
                'last_seen_at' => now(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ]
        );

        return response()->json([
            'status' => 'ok',
            'last_seen_at' => now()->toIso8601String(),
        ]);
    }
}
