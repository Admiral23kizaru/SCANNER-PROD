<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ehris\EhrisReportingManager;
use App\Models\Ehris\EhrisUser;
use App\Models\School;
use Illuminate\Http\JsonResponse;

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

}
