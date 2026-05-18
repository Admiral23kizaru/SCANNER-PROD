<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Ehris\EhrisDepartment;
use App\Models\Ehris\EhrisReportingManager;
use App\Models\Ehris\EhrisUser;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds read-only division monitoring data for the System Admin dashboard.
 *
 * The service keeps the System Admin feature separate from the school admin
 * controllers so existing principal dashboards keep strict school_id isolation.
 */
class SystemAdminDashboardService
{
    /**
     * Return division-wide non-personal totals for the top cards.
     */
    public function overview(): array
    {
        $today = now()->toDateString();
        $teacherRoleId = Role::where('name', 'Teacher')->value('id');
        $schoolCodes = $this->schoolDepartments()->pluck('department_id')->map(fn ($id) => (string) $id);
        $scanupSchoolIds = School::whereIn('deped_school_id', $schoolCodes)->pluck('id');
        $activeToday = Attendance::whereDate('scanned_at', $today)
            ->whereIn('school_id', $scanupSchoolIds)
            ->distinct('school_id')
            ->count('school_id');

        return [
            'total_schools' => $schoolCodes->count(),
            'scanup_schools' => $scanupSchoolIds->count(),
            'encoded_students' => Student::whereIn('school_id', $scanupSchoolIds)->count(),
            'synced_teachers' => $teacherRoleId
                ? User::where('role_id', $teacherRoleId)->whereIn('school_id', $scanupSchoolIds)->count()
                : 0,
            'scans_today' => Attendance::whereDate('scanned_at', $today)
                ->whereIn('school_id', $scanupSchoolIds)
                ->count(),
            'schools_with_scans_today' => $activeToday,
            'schools_without_scans_today' => max(0, $schoolCodes->count() - $activeToday),
        ];
    }

    /**
     * Return one row per school for the System Admin school selector/table.
     */
    public function schools(): array
    {
        $today = now()->toDateString();
        $teacherRoleId = Role::where('name', 'Teacher')->value('id');
        $departments = $this->schoolDepartments();
        $codes = $departments->pluck('department_id')->map(fn ($id) => (string) $id)->values();
        $schoolsByDeped = School::whereIn('deped_school_id', $codes)->get()->keyBy('deped_school_id');
        $schoolIds = $schoolsByDeped->pluck('id')->values();
        $studentCounts = Student::whereIn('school_id', $schoolIds)
            ->select('school_id', DB::raw('COUNT(*) as total'))
            ->groupBy('school_id')
            ->pluck('total', 'school_id');
        $teacherCounts = $teacherRoleId
            ? User::where('role_id', $teacherRoleId)
                ->whereIn('school_id', $schoolIds)
                ->select('school_id', DB::raw('COUNT(*) as total'))
                ->groupBy('school_id')
                ->pluck('total', 'school_id')
            : collect();
        $attendanceToday = Attendance::whereDate('scanned_at', $today)
            ->whereIn('school_id', $schoolIds)
            ->select('school_id', DB::raw('COUNT(DISTINCT student_id) as total'), DB::raw('MAX(scanned_at) as last_scan_at'))
            ->groupBy('school_id')
            ->get()
            ->keyBy('school_id');

        return $departments->map(function (EhrisDepartment $department) use (
            $schoolsByDeped,
            $studentCounts,
            $teacherCounts,
            $attendanceToday
        ) {
            $depedId = (string) $department->department_id;
            $assignment = $this->assignmentFor($depedId);
            $school = $schoolsByDeped->get($depedId);
            $schoolId = $school?->id;
            $attendance = $schoolId ? $attendanceToday->get($schoolId) : null;

            return [
                'school_id' => $schoolId,
                'deped_school_id' => $depedId,
                'school_name' => $school?->name ?: ($assignment['school_name'] ?: (string) $department->department_name),
                'school_head' => $this->schoolHeadFor($depedId),
                'assigned_admin' => $this->assignedAdminFor($depedId),
                'students' => $schoolId ? (int) ($studentCounts[$schoolId] ?? 0) : 0,
                'teachers' => $schoolId ? (int) ($teacherCounts[$schoolId] ?? 0) : 0,
                'attendance_today' => $attendance ? (int) $attendance->total : 0,
                'last_scan_at' => $attendance?->last_scan_at,
                'setup_status' => $schoolId ? 'ready' : 'not_created',
            ];
        })->values()->all();
    }

    /**
     * Return read-only details for one selected school.
     */
    public function schoolDetail(string $depedSchoolId): ?array
    {
        $department = $this->schoolDepartments()
            ->first(fn (EhrisDepartment $row) => (string) $row->department_id === (string) $depedSchoolId);

        if (!$department) {
            return null;
        }

        $school = School::where('deped_school_id', (string) $depedSchoolId)->first();
        $schoolId = $school?->id;
        $today = now()->toDateString();
        $teacherRoleId = Role::where('name', 'Teacher')->value('id');
        $assignment = $this->assignmentFor((string) $department->department_id);

        return [
            'school_id' => $schoolId,
            'deped_school_id' => (string) $department->department_id,
            'school_name' => $school?->name ?: ($assignment['school_name'] ?: (string) $department->department_name),
            'school_head' => $this->schoolHeadFor((string) $department->department_id),
            'assigned_admin' => $this->assignedAdminFor((string) $department->department_id),
            'setup_status' => $schoolId ? 'ready' : 'not_created',
            'stats' => [
                'students' => $schoolId ? Student::where('school_id', $schoolId)->count() : 0,
                'teachers' => ($schoolId && $teacherRoleId)
                    ? User::where('role_id', $teacherRoleId)->where('school_id', $schoolId)->count()
                    : 0,
                'attendance_today' => $schoolId
                    ? Attendance::where('school_id', $schoolId)->whereDate('scanned_at', $today)->distinct('student_id')->count('student_id')
                    : 0,
                'late_today' => $schoolId
                    ? Attendance::where('school_id', $schoolId)->whereDate('scanned_at', $today)->where('status', 'late')->count()
                    : 0,
            ],
        ];
    }

    /**
     * School departments only; excludes division office/admin departments.
     */
    private function schoolDepartments(): Collection
    {
        $assignmentCodes = array_keys(config('scanup_school_assignments', []));

        return EhrisDepartment::query()
            ->when($assignmentCodes !== [], function ($query) use ($assignmentCodes) {
                $query->whereIn('department_id', $assignmentCodes);
            }, function ($query) {
                $query->where(function ($inner) {
                    $inner->where('department_name', 'like', '%School%')
                        ->orWhere('department_name', 'like', '%Elementary%')
                        ->orWhere('department_name', 'like', '%National High%')
                        ->orWhere('department_name', 'like', '%Integrated%');
                });
            })
            ->orderBy('department_name')
            ->get();
    }

    /**
     * Resolve the school head/principal from EHRIS without writing to EHRIS.
     */
    private function schoolHeadFor(string $depedId): ?array
    {
        $assignment = $this->assignmentFor($depedId);
        if ($assignment['school_head'] !== '') {
            return [
                'name' => $assignment['school_head'],
                'email' => '',
                'role' => 'School Head',
                'job_title' => $assignment['school_head_position'],
            ];
        }

        $manager = EhrisReportingManager::where('department_id', $depedId)->first();
        $mapped = $manager
            ? EhrisUser::active()->where('userId', $manager->manager_name)->where('department_id', $depedId)->first()
            : null;

        $user = $mapped ?: EhrisUser::active()
            ->where('department_id', $depedId)
            ->where(function ($query) {
                $query->whereIn('role', ['Reporting Manager', 'Principal', 'School Principal'])
                    ->orWhere('job_title', 'like', '%Principal%')
                    ->orWhere('job_title', 'like', '%Head Teacher%');
            })
            ->first();

        return $user ? $this->ehrisPerson($user) : null;
    }

    /**
     * Resolve the assigned admin/AO-style account from EHRIS without writing to EHRIS.
     */
    private function assignedAdminFor(string $depedId): ?array
    {
        $assignment = $this->assignmentFor($depedId);
        if ($assignment['assigned_admin'] !== '') {
            return [
                'name' => $assignment['assigned_admin'],
                'email' => '',
                'role' => 'Assigned Admin',
                'job_title' => $assignment['assigned_admin_position'],
            ];
        }

        $user = EhrisUser::active()
            ->where('department_id', $depedId)
            ->where(function ($query) {
                $query->whereIn('role', ['AO Manager', 'Administrative Officer', 'Admin'])
                    ->orWhere('job_title', 'like', '%Administrative Officer%');
            })
            ->first();

        return $user ? $this->ehrisPerson($user) : null;
    }

    /**
     * Convert an EHRIS user into a safe dashboard payload.
     */
    private function ehrisPerson(EhrisUser $user): array
    {
        return [
            'name' => trim((string) $user->full_name) ?: (string) $user->userId,
            'email' => (string) ($user->email ?? ''),
            'role' => (string) ($user->role ?? ''),
            'job_title' => (string) ($user->job_title ?? ''),
        ];
    }

    /**
     * Read the latest trainer-provided school assignment list.
     */
    private function assignmentFor(string $depedId): array
    {
        $assignment = config("scanup_school_assignments.$depedId", []);

        return [
            'school_name' => trim((string) ($assignment['school_name'] ?? '')),
            'school_head' => trim((string) ($assignment['school_head'] ?? '')),
            'school_head_position' => trim((string) ($assignment['school_head_position'] ?? '')),
            'assigned_admin' => trim((string) ($assignment['assigned_admin'] ?? '')),
            'assigned_admin_position' => trim((string) ($assignment['assigned_admin_position'] ?? '')),
        ];
    }
}
