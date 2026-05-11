<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ehris\EhrisReportingManager;
use App\Models\Ehris\EhrisUser;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolController extends Controller
{
    /** BAT Step 1 — verify DepEd ID exists before POST /guard/login. */
    public function check(string $deped_id): JsonResponse
    {
        $school = School::where('deped_school_id', $deped_id)->first();

        if (!$school) {
            return response()->json([
                'exists'      => false,
                'school_name' => null,
                'error'       => 'School ID not found.',
            ], 404);
        }

        $principalName = null;
        $principalFound = false;

        $ehrisPrincipal = $this->resolvePrincipalCandidate($school);

        if ($ehrisPrincipal) {
            $principalFound = true;
            $principalName = trim((string) ($ehrisPrincipal->full_name ?? ''));

            if ($principalName === '') {
                $principalName = (string) $ehrisPrincipal->userId;
            }
        }

        return response()->json([
            'exists'          => true,
            'school_name'     => $school->name,
            'deped_id'        => $school->deped_school_id,
            'principal_found' => $principalFound,
            'principal_name'  => $principalName,
        ], 200);
    }

    /**
     * Resolve principal/RM candidate safely for a school.
     *
     * Priority:
     * 1) Exact tbl_reporting_manager mapping (legacy expected source)
     * 2) Fallback active users in same department with role/title principal hints
     */
    private function resolvePrincipalCandidate(School $school): ?EhrisUser
    {
        $manager = EhrisReportingManager::where('department_id', $school->deped_school_id)->first();

        if ($manager) {
            $mapped = EhrisUser::active()
                ->where('userId', $manager->manager_name)
                ->where('department_id', $school->deped_school_id)
                ->first();

            if ($mapped) {
                return $mapped;
            }
        }

        return EhrisUser::active()
            ->where('department_id', $school->deped_school_id)
            ->where(function ($q) {
                $q->whereIn('role', ['Reporting Manager', 'Principal', 'School Principal'])
                    ->orWhere('job_title', 'like', '%Principal%');
            })
            ->first();
    }

    /**
     * Create a new school and its admin account (Super Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        if (auth()->user()->school_id !== null) {
            return response()->json(['message' => 'Unauthorized. Super Admin only.'], 403);
        }

        $request->validate([
            'deped_school_id' => 'required|string|max:50|unique:schools,deped_school_id',
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|min:8|confirmed',
        ]);

        DB::transaction(function () use ($request) {
            $school = School::create([
                'name'            => $request->name,
                'deped_school_id' => $request->deped_school_id,
            ]);

            User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => $request->password,
                'role_id'   => 1,
                'school_id' => $school->id,
                'status'    => 'active',
            ]);

            SchoolSetting::create([
                'school_id'         => $school->id,
                'late_threshold'    => '07:30:00',
                'absence_threshold' => 3,
            ]);

            SchoolYear::create([
                'school_id'  => $school->id,
                'name'       => '2025-2026',
                'start_date' => '2025-06-02',
                'end_date'   => '2026-03-27',
                'is_active'  => 1,
            ]);
        });

        return response()->json([
            'message' => 'School account created successfully.',
        ], 201);
    }
}
