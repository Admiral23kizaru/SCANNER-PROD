<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ehris\EhrisReportingManager;
use App\Models\Ehris\EhrisUser;
use App\Services\SchoolResolver;
use Illuminate\Http\JsonResponse;

class SchoolController extends Controller
{
    public function __construct(private SchoolResolver $schools)
    {
    }

    /** BAT Step 1 — verify DepEd ID exists before POST /guard/login. */
    public function check(string $deped_id): JsonResponse
    {
        $resolved = $this->schools->findExistingOrEhrisDepartment($deped_id);

        if (!$resolved) {
            return response()->json([
                'exists'      => false,
                'school_name' => null,
                'error'       => 'School ID not found.',
            ], 404);
        }

        $principalName = null;
        $principalFound = false;

        $ehrisPrincipal = $this->resolvePrincipalCandidateByDepedId($resolved['deped_id']);

        if ($ehrisPrincipal) {
            $principalFound = true;
            $principalName = trim((string) ($ehrisPrincipal->full_name ?? ''));

            if ($principalName === '') {
                $principalName = (string) $ehrisPrincipal->userId;
            }
        }

        return response()->json([
            'exists'          => true,
            'school_name'     => $resolved['name'],
            'deped_id'        => $resolved['deped_id'],
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
    private function resolvePrincipalCandidateByDepedId(string $depedId): ?EhrisUser
    {
        $manager = EhrisReportingManager::where('department_id', $depedId)->first();

        if ($manager) {
            $mapped = EhrisUser::active()
                ->where('userId', $manager->manager_name)
                ->where('department_id', $depedId)
                ->first();

            if ($mapped) {
                return $mapped;
            }
        }

        return EhrisUser::active()
            ->where('department_id', $depedId)
            ->where(function ($q) {
                $q->whereIn('role', ['Reporting Manager', 'Principal', 'School Principal'])
                    ->orWhere('job_title', 'like', '%Principal%');
            })
            ->first();
    }

}
