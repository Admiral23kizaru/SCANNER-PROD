<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AssessmentLog;
use App\Models\Ehris\EhrisDepartment;
use App\Models\Ehris\EhrisReportingManager;
use App\Models\Ehris\EhrisUser;
use App\Models\LearningAssessmentFile;
use App\Models\ParentGuardian;
use App\Models\Role;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
            'districts' => $this->districtCount(),
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
            $studentTotal = $schoolId ? (int) ($studentCounts[$schoolId] ?? 0) : 0;
            $teacherTotal = $schoolId ? (int) ($teacherCounts[$schoolId] ?? 0) : 0;
            $scanTotal = $attendance ? (int) $attendance->total : 0;

            return [
                'school_id' => $schoolId,
                'deped_school_id' => $depedId,
                'district_code' => (string) ($department->business_id ?? ''),
                'district' => $this->districtNameFor((string) ($department->business_id ?? '')),
                'school_name' => $school?->name ?: ($assignment['school_name'] ?: (string) $department->department_name),
                'school_head' => $this->schoolHeadFor($depedId),
                'assigned_admin' => $this->assignedAdminFor($depedId),
                'students' => $studentTotal,
                'teachers' => $teacherTotal,
                'attendance_today' => $scanTotal,
                'last_scan_at' => $attendance?->last_scan_at,
                'setup_status' => $schoolId ? 'ready' : 'not_created',
                'school_type' => $this->schoolTypeFor($school?->name ?: ($assignment['school_name'] ?: (string) $department->department_name)),
                'health' => $this->healthFor($schoolId, $studentTotal, $teacherTotal, $scanTotal),
            ];
        })->values()->all();
    }

    /**
     * Return division-wide learner records from tbl_scanup_students.
     */
    public function learners(): array
    {
        $schoolIds = $this->scanupSchoolIds();

        return Student::query()
            ->from('tbl_scanup_students as students')
            ->leftJoin('tbl_scanup_schools as schools', 'students.school_id', '=', 'schools.id')
            ->leftJoin('tbl_scanup_users as advisers', 'students.teacher_id', '=', 'advisers.id')
            ->whereIn('students.school_id', $schoolIds)
            ->whereNull('students.deleted_at')
            ->select([
                'students.id',
                'students.student_number',
                'students.first_name',
                'students.middle_name',
                'students.last_name',
                'students.gender',
                'students.grade',
                'students.section',
                'students.school_id',
                'schools.name as school_name',
                'schools.deped_school_id',
                'advisers.name as adviser_name',
            ])
            ->orderBy('schools.name')
            ->orderBy('students.grade')
            ->orderBy('students.section')
            ->orderBy('students.last_name')
            ->limit(1000)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'school_id' => (int) $row->school_id,
                'deped_school_id' => (string) $row->deped_school_id,
                'school_name' => (string) $row->school_name,
                'student_number' => $row->student_number,
                'name' => trim(($row->last_name ?? '') . ', ' . ($row->first_name ?? '') . ' ' . ($row->middle_name ?? '')),
                'gender' => $row->gender,
                'grade' => $row->grade,
                'section' => $row->section,
                'adviser_name' => $row->adviser_name ?: 'Not assigned',
            ])
            ->values()
            ->all();
    }

    public function teachers(): array
    {
        $teacherRoleIds = Role::whereIn('name', ['Teacher', 'Adviser', 'Subject Teacher'])->pluck('id');
        $schoolRows = collect($this->schools());
        $schoolIds = $schoolRows->pluck('school_id')->filter()->values();

        $teachers = User::query()
            ->with('role:id,name')
            ->whereIn('school_id', $schoolIds)
            ->whereIn('role_id', $teacherRoleIds)
            ->orderBy('name')
            ->get(['id', 'role_id', 'name', 'email', 'employee_id', 'school_id', 'grade_level', 'section', 'ehris_user_id', 'job_title'])
            ->groupBy('school_id');

        $learnerCounts = Student::whereIn('school_id', $schoolIds)
            ->whereNull('deleted_at')
            ->select('teacher_id', DB::raw('COUNT(*) as total'))
            ->groupBy('teacher_id')
            ->pluck('total', 'teacher_id');

        $schoolSubjects = $this->schoolSubjectsBySchoolId($schoolIds);
        $subjectsByHrid = $this->teacherSubjectsByHrid();
        $ehrisTeachers = $this->ehrisTeachersByDepartment($schoolRows->pluck('deped_school_id')->map(fn ($id) => (string) $id)->values());

        return $schoolRows->map(function (array $school) use ($teachers, $learnerCounts, $subjectsByHrid, $schoolSubjects, $ehrisTeachers) {
            $schoolSubjectList = (string) ($schoolSubjects[$school['school_id']] ?? '');
            $schoolTeachers = collect($teachers->get($school['school_id'], collect()))
                ->map(function (User $teacher) use ($learnerCounts, $subjectsByHrid, $schoolSubjectList) {
                    $hrid = trim((string) ($teacher->employee_id ?: $teacher->ehris_user_id));
                    $teacherSubjects = $hrid !== '' ? (string) ($subjectsByHrid[$hrid] ?? '') : '';

                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                        'role' => $teacher->role?->name ?? 'Teacher',
                        'hrid' => $hrid,
                        'job_title' => $teacher->job_title,
                        'grade_level' => $teacher->grade_level,
                        'section' => $teacher->section,
                        'subjects' => $teacherSubjects,
                        'subjects_source' => $teacherSubjects !== '' ? 'teacher_assignment' : 'none',
                        'learner_count' => (int) ($learnerCounts[$teacher->id] ?? 0),
                        'source' => 'scanup',
                    ];
                })
                ->values();

            if ($schoolTeachers->isEmpty()) {
                $schoolTeachers = collect($ehrisTeachers->get((string) $school['deped_school_id'], collect()));
            }

            return array_merge($school, [
                'school_subjects' => $schoolSubjectList,
                'teacher_count' => $schoolTeachers->count(),
                'learner_count' => (int) ($school['students'] ?? 0),
                'teacher_rows' => $schoolTeachers->values()->all(),
            ]);
        })->values()->all();
    }

    private function ehrisTeachersByDepartment(Collection $departmentIds): Collection
    {
        try {
            $departmentIds = $departmentIds->map(fn ($id) => trim((string) $id))->filter()->unique()->values();
            if ($departmentIds->isEmpty()) {
                return collect();
            }

            $connection = (new EhrisUser())->getConnectionName() ?: config('database.default');
            $schema = Schema::connection($connection);

            if (
                !$schema->hasTable('tbl_user') ||
                !$schema->hasColumn('tbl_user', 'department_id') ||
                !$schema->hasColumn('tbl_user', 'role')
            ) {
                Log::warning('System Admin EHRIS teacher lookup unavailable.', [
                    'method' => __METHOD__,
                    'connection' => $connection,
                    'table' => 'tbl_user',
                ]);

                return collect();
            }

            $subjectsByHrid = $this->teacherSubjectsByHrid();

            return EhrisUser::active()
                ->whereIn('department_id', $departmentIds->all())
                ->where(function ($query) {
                    $query->where('role', 'Teacher')
                        ->orWhere('job_title', 'like', '%Teacher%');
                })
                ->orderBy('lastname')
                ->orderBy('firstname')
                ->get(['userId', 'hrId', 'email', 'lastname', 'firstname', 'middlename', 'job_title', 'role', 'department_id'])
                ->groupBy(fn (EhrisUser $teacher) => (string) $teacher->department_id)
                ->map(function (Collection $teachers) use ($subjectsByHrid) {
                    return $teachers->map(function (EhrisUser $teacher) use ($subjectsByHrid) {
                        $hrid = trim((string) ($teacher->hrId ?: $teacher->userId));
                        $name = trim(implode(' ', array_filter([
                            $teacher->firstname,
                            $teacher->middlename,
                            $teacher->lastname,
                        ]))) ?: (string) $teacher->userId;
                        $teacherSubjects = $hrid !== '' ? (string) ($subjectsByHrid[$hrid] ?? '') : '';

                        return [
                            'id' => 'ehris-' . $teacher->userId,
                            'name' => $name,
                            'email' => (string) ($teacher->email ?? ''),
                            'role' => (string) ($teacher->role ?? 'Teacher'),
                            'hrid' => $hrid,
                            'job_title' => (string) ($teacher->job_title ?? ''),
                            'grade_level' => null,
                            'section' => null,
                            'subjects' => $teacherSubjects,
                            'subjects_source' => $teacherSubjects !== '' ? 'teacher_assignment' : 'none',
                            'learner_count' => 0,
                            'source' => 'ehris',
                        ];
                    })->values();
                });
        } catch (Throwable $exception) {
            Log::error('System Admin EHRIS teacher lookup failed.', [
                'method' => __METHOD__,
                'table' => 'tbl_user',
                'error' => $exception->getMessage(),
            ]);

            return collect();
        }
    }

    private function schoolSubjectsBySchoolId(Collection $schoolIds): Collection
    {
        try {
            if ($schoolIds->isEmpty() || !Schema::hasTable('tbl_scanup_subjects')) {
                return collect();
            }

            return Subject::whereIn('school_id', $schoolIds)
                ->select('school_id', DB::raw("GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') as subjects"))
                ->groupBy('school_id')
                ->pluck('subjects', 'school_id');
        } catch (Throwable $exception) {
            Log::warning('System Admin school subject lookup failed.', [
                'method' => __METHOD__,
                'table' => 'tbl_scanup_subjects',
                'error' => $exception->getMessage(),
            ]);

            return collect();
        }
    }

    private function teacherSubjectsByHrid(): Collection
    {
        try {
            $connection = (new EhrisUser())->getConnectionName() ?: config('database.default');
            $schema = Schema::connection($connection);

            if (
                !$schema->hasTable('tbl_emp_official_subject_taught') ||
                !$schema->hasColumn('tbl_emp_official_subject_taught', 'hrid') ||
                !$schema->hasColumn('tbl_emp_official_subject_taught', 'subject_name')
            ) {
                Log::warning('System Admin teacher subject table/columns missing.', [
                    'method' => __METHOD__,
                    'connection' => $connection,
                    'database' => config("database.connections.$connection.database"),
                    'table' => 'tbl_emp_official_subject_taught',
                ]);

                return collect();
            }

            $orderColumn = $schema->hasColumn('tbl_emp_official_subject_taught', 'sort_order')
                ? 'sort_order'
                : 'subject_name';

            return DB::connection($connection)->table('tbl_emp_official_subject_taught')
                ->select('hrid', DB::raw("GROUP_CONCAT(subject_name ORDER BY {$orderColumn} SEPARATOR ', ') as subjects"))
                ->whereNotNull('hrid')
                ->whereRaw("TRIM(COALESCE(subject_name, '')) <> ''")
                ->groupBy('hrid')
                ->pluck('subjects', 'hrid');
        } catch (Throwable $exception) {
            Log::error('System Admin teacher subject lookup failed.', [
                'method' => __METHOD__,
                'table' => 'tbl_emp_official_subject_taught',
                'error' => $exception->getMessage(),
            ]);

            return collect();
        }
    }

    public function guardians(): array
    {
        $schoolIds = $this->scanupSchoolIds();

        return ParentGuardian::query()
            ->from('tbl_scanup_parent_guardians as guardians')
            ->leftJoin('tbl_scanup_schools as schools', 'guardians.school_id', '=', 'schools.id')
            ->leftJoin('tbl_scanup_students as students', 'guardians.student_id', '=', 'students.id')
            ->whereIn('guardians.school_id', $schoolIds)
            ->select([
                'guardians.id',
                'guardians.name',
                'guardians.relationship',
                'guardians.contact_number',
                'guardians.email',
                'guardians.is_primary',
                'schools.name as school_name',
                'schools.deped_school_id',
                'students.first_name',
                'students.last_name',
                'students.grade',
                'students.section',
            ])
            ->orderBy('schools.name')
            ->orderByDesc('guardians.is_primary')
            ->orderBy('guardians.name')
            ->limit(1000)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'school_name' => (string) $row->school_name,
                'deped_school_id' => (string) $row->deped_school_id,
                'name' => (string) $row->name,
                'relationship' => (string) $row->relationship,
                'contact_number' => $row->contact_number,
                'email' => $row->email,
                'is_primary' => (bool) $row->is_primary,
                'learner_name' => trim(($row->last_name ?? '') . ', ' . ($row->first_name ?? '')) ?: 'Unlinked',
                'grade' => $row->grade,
                'section' => $row->section,
            ])
            ->values()
            ->all();
    }

    public function assessmentLogs(): array
    {
        $schoolIds = $this->scanupSchoolIds();

        if (!Schema::hasTable('tbl_scanup_assessment_logs')) {
            Log::warning('System Admin assessment logs table missing.', [
                'method' => __METHOD__,
                'table' => 'tbl_scanup_assessment_logs',
            ]);

            return [];
        }

        try {
            $logs = AssessmentLog::query()
                ->from('tbl_scanup_assessment_logs as logs')
                ->leftJoin('tbl_scanup_schools as schools', 'logs.school_id', '=', 'schools.id')
                ->leftJoin('tbl_scanup_students as students', 'logs.student_id', '=', 'students.id')
                ->leftJoin('tbl_scanup_subjects as subjects', 'logs.subject_id', '=', 'subjects.id')
                ->whereIn('logs.school_id', $schoolIds)
                ->select([
                    'logs.id',
                    'logs.school_year',
                    'logs.grade_level',
                    'logs.section',
                    'logs.assessment_type',
                    'logs.score',
                    'logs.total_items',
                    'logs.least_mastered_skills',
                    'logs.remarks',
                    'logs.created_at',
                    'schools.name as school_name',
                    'schools.deped_school_id',
                    'students.first_name',
                    'students.last_name',
                    'subjects.name as subject_name',
                ])
                ->orderByDesc('logs.created_at')
                ->limit(1000)
                ->get();
        } catch (Throwable $exception) {
            Log::error('System Admin assessment logs failed.', [
                'method' => __METHOD__,
                'table' => 'tbl_scanup_assessment_logs',
                'error' => $exception->getMessage(),
            ]);

            return [];
        }

        return $logs->map(fn ($row) => [
            'id' => (int) $row->id,
            'school_name' => (string) $row->school_name,
            'deped_school_id' => (string) $row->deped_school_id,
            'learner_name' => trim(($row->last_name ?? '') . ', ' . ($row->first_name ?? '')) ?: 'Class summary',
            'subject_name' => $row->subject_name ?: 'Unspecified',
            'school_year' => $row->school_year,
            'grade_level' => $row->grade_level,
            'section' => $row->section,
            'assessment_type' => $row->assessment_type,
            'score' => (int) $row->score,
            'total_items' => (int) $row->total_items,
            'least_mastered_skills' => $row->least_mastered_skills,
            'remarks' => $row->remarks,
            'created_at' => $row->created_at,
        ])->values()->all();
    }

    public function leastMasteredSkills(array $filters = []): array
    {
        $schoolIds = $this->scanupSchoolIds();
        $skillCounts = [];
        $masteryCounts = [
            'Mastered' => 0,
            'Nearly Mastered' => 0,
            'Least Mastered' => 0,
        ];
        $difficultyCounts = [
            'Easy' => 0,
            'Average' => 0,
            'Difficult' => 0,
        ];

        $logs = AssessmentLog::query()
            ->whereIn('school_id', $schoolIds)
            ->when($filters['school_id'] ?? null, fn ($q, $value) => $q->where('school_id', $value))
            ->when($filters['subject_id'] ?? null, fn ($q, $value) => $q->where('subject_id', $value))
            ->when($filters['grade_level'] ?? null, fn ($q, $value) => $q->where('grade_level', $value))
            ->when($filters['section'] ?? null, fn ($q, $value) => $q->where('section', $value))
            ->when($filters['school_year'] ?? null, fn ($q, $value) => $q->where('school_year', $value))
            ->get(['least_mastered_skills']);

        foreach ($logs as $log) {
            foreach (($log->least_mastered_skills ?? []) as $skill) {
                $label = trim((string) $skill);
                if ($label !== '') {
                    $skillCounts[$label] = ($skillCounts[$label] ?? 0) + 1;
                }
            }
        }

        $files = LearningAssessmentFile::query()
            ->whereIn('school_id', $schoolIds)
            ->when($filters['school_id'] ?? null, fn ($q, $value) => $q->where('school_id', $value))
            ->when($filters['subject_id'] ?? null, fn ($q, $value) => $q->where('subject_id', $value))
            ->when($filters['grade_level'] ?? null, fn ($q, $value) => $q->where('grade_level', $value))
            ->when($filters['section'] ?? null, fn ($q, $value) => $q->where('section', $value))
            ->latest('analyzed_at')
            ->limit(200)
            ->get(['analysis_payload']);

        foreach ($files as $file) {
            foreach (($file->analysis_payload['item_stats'] ?? []) as $item) {
                $difficulty = (float) ($item['difficulty_pct'] ?? 100);
                $masteryLabel = $this->masteryLabelForDifficulty($difficulty);
                $difficultyLabel = $this->difficultyLabelForItem($item, $difficulty);
                $masteryCounts[$masteryLabel] = ($masteryCounts[$masteryLabel] ?? 0) + 1;
                $difficultyCounts[$difficultyLabel] = ($difficultyCounts[$difficultyLabel] ?? 0) + 1;

                if ($difficulty < 50) {
                    $label = 'Item ' . ($item['item'] ?? '?');
                    $skillCounts[$label] = ($skillCounts[$label] ?? 0) + 1;
                }
            }
        }

        arsort($skillCounts);

        if (array_sum($masteryCounts) === 0 && array_sum($skillCounts) > 0) {
            $masteryCounts['Least Mastered'] = array_sum($skillCounts);
            $difficultyCounts['Difficult'] = array_sum($skillCounts);
        }

        return [
            'filters' => [
                'schools' => School::whereIn('id', $schoolIds)->orderBy('name')->get(['id', 'name']),
                'school_years' => AssessmentLog::whereIn('school_id', $schoolIds)->whereNotNull('school_year')->distinct()->orderBy('school_year')->pluck('school_year')->values(),
                'subjects' => Subject::whereIn('school_id', $schoolIds)->orWhereNull('school_id')->orderBy('name')->get(['id', 'name']),
                'grades' => Student::whereIn('school_id', $schoolIds)->whereNotNull('grade')->distinct()->orderBy('grade')->pluck('grade')->values(),
                'sections' => Student::whereIn('school_id', $schoolIds)->whereNotNull('section')->distinct()->orderBy('section')->pluck('section')->values(),
            ],
            'data' => collect($skillCounts)->take(12)->map(fn ($count, $skill) => [
                'skill' => $skill,
                'count' => $count,
            ])->values()->all(),
            'quick_analysis' => [
                'mastery_levels' => collect($masteryCounts)->map(fn ($count, $label) => [
                    'label' => $label,
                    'count' => (int) $count,
                ])->values()->all(),
                'item_difficulty' => collect($difficultyCounts)->map(fn ($count, $label) => [
                    'label' => $label,
                    'count' => (int) $count,
                ])->values()->all(),
            ],
        ];
    }

    private function masteryLabelForDifficulty(float $difficulty): string
    {
        if ($difficulty >= 75) {
            return 'Mastered';
        }

        if ($difficulty >= 50) {
            return 'Nearly Mastered';
        }

        return 'Least Mastered';
    }

    private function difficultyLabelForItem(array $item, float $difficulty): string
    {
        $level = strtolower(trim((string) ($item['difficulty_level'] ?? '')));

        if (str_contains($level, 'easy')) {
            return 'Easy';
        }

        if (str_contains($level, 'difficult')) {
            return 'Difficult';
        }

        if (str_contains($level, 'average')) {
            return 'Average';
        }

        if ($difficulty >= 75) {
            return 'Easy';
        }

        if ($difficulty >= 50) {
            return 'Average';
        }

        return 'Difficult';
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
     * Return the selected school's read-only dashboard snapshot.
     */
    public function schoolDashboard(string $depedSchoolId, array $filters = []): ?array
    {
        $detail = $this->schoolDetail($depedSchoolId);

        if (!$detail) {
            return null;
        }

        $schoolId = $detail['school_id'];
        $today = now()->toDateString();

        if (!$schoolId) {
            return $detail + [
                'attendance_by_grade' => [],
                'attendance_trends' => [],
                'filter_options' => [
                    'grades' => [],
                    'sections' => [],
                ],
                'recent_activity' => [],
                'student_status_today' => [
                    'male' => 0,
                    'female' => 0,
                    'absent' => 0,
                ],
                'sections' => 0,
                'subjects' => 0,
                'health' => $this->healthFor(null, 0, 0, 0),
            ];
        }

        $attendanceByGrade = DB::table('tbl_scanup_attendance as attendance')
            ->join('tbl_scanup_students as students', 'attendance.student_id', '=', 'students.id')
            ->where('attendance.school_id', $schoolId)
            ->whereDate('attendance.scanned_at', $today)
            ->select('students.grade', DB::raw('COUNT(DISTINCT attendance.student_id) as count'))
            ->groupBy('students.grade')
            ->orderBy('students.grade')
            ->get();

        $attendanceTrends = $this->attendanceTrendsFor($schoolId, $filters);
        $filterOptions = $this->dashboardFilterOptionsFor($schoolId);

        $presentStudentIds = Attendance::where('school_id', $schoolId)
            ->whereDate('scanned_at', $today)
            ->select('student_id');
        $maleToday = Student::where('school_id', $schoolId)
            ->where('gender', 'Male')
            ->whereIn('id', $presentStudentIds)
            ->count();
        $presentStudentIds = Attendance::where('school_id', $schoolId)
            ->whereDate('scanned_at', $today)
            ->select('student_id');
        $femaleToday = Student::where('school_id', $schoolId)
            ->where('gender', 'Female')
            ->whereIn('id', $presentStudentIds)
            ->count();
        $absentToday = max(0, (int) $detail['stats']['students'] - (int) $detail['stats']['attendance_today']);

        $recentActivity = Attendance::with('student')
            ->where('school_id', $schoolId)
            ->orderByDesc('scanned_at')
            ->limit(8)
            ->get()
            ->map(fn (Attendance $attendance) => [
                'type' => 'attendance',
                'title' => 'Student scan recorded',
                'subtitle' => trim(
                    ($attendance->student?->first_name ?? '') . ' ' .
                    ($attendance->student?->last_name ?? '') . ' - ' .
                    ($attendance->student?->grade ?? 'No grade') . ' ' .
                    ($attendance->student?->section ?? '')
                ),
                'time' => $attendance->scanned_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $detail['sections'] = Section::where('school_id', $schoolId)->count();
        $detail['subjects'] = Subject::where('school_id', $schoolId)->count();
        $detail['attendance_by_grade'] = $attendanceByGrade;
        $detail['attendance_trends'] = $attendanceTrends;
        $detail['filter_options'] = $filterOptions;
        $detail['recent_activity'] = $recentActivity;
        $detail['student_status_today'] = [
            'male' => $maleToday,
            'female' => $femaleToday,
            'absent' => $absentToday,
        ];
        $detail['stats']['male_today'] = $maleToday;
        $detail['stats']['female_today'] = $femaleToday;
        $detail['stats']['absent_today'] = $absentToday;
        $detail['health'] = $this->healthFor(
            $schoolId,
            (int) $detail['stats']['students'],
            (int) $detail['stats']['teachers'],
            (int) $detail['stats']['attendance_today']
        );

        return $detail;
    }

    /**
     * Return all ScanUp learning areas across schools for read-only System Admin review.
     */
    public function subjects(): array
    {
        $schoolCodes = $this->schoolDepartments()
            ->pluck('department_id')
            ->map(fn ($id) => (string) $id)
            ->values();

        $schoolIds = School::whereIn('deped_school_id', $schoolCodes)->pluck('id');

        return Subject::query()
            ->from('tbl_scanup_subjects as subjects')
            ->leftJoin('tbl_scanup_schools as schools', 'subjects.school_id', '=', 'schools.id')
            ->leftJoin('tbl_scanup_student_subject as student_subject', 'subjects.id', '=', 'student_subject.subject_id')
            ->where(function ($query) use ($schoolIds) {
                $query->whereIn('subjects.school_id', $schoolIds)
                    ->orWhereNull('subjects.school_id');
            })
            ->select([
                'subjects.id',
                'subjects.name',
                'subjects.school_id',
                'subjects.created_at',
                'subjects.updated_at',
                'schools.name as school_name',
                'schools.deped_school_id',
                DB::raw('COUNT(DISTINCT student_subject.student_id) as enrolled_students'),
            ])
            ->groupBy([
                'subjects.id',
                'subjects.name',
                'subjects.school_id',
                'subjects.created_at',
                'subjects.updated_at',
                'schools.name',
                'schools.deped_school_id',
            ])
            ->orderBy('schools.name')
            ->orderBy('subjects.name')
            ->get()
            ->map(fn ($subject) => [
                'id' => (int) $subject->id,
                'name' => (string) $subject->name,
                'school_id' => $subject->school_id ? (int) $subject->school_id : null,
                'school_name' => $subject->school_name ?: 'Division-wide / Unassigned',
                'deped_school_id' => $subject->deped_school_id ? (string) $subject->deped_school_id : '',
                'enrolled_students' => (int) $subject->enrolled_students,
                'created_at' => $subject->created_at,
                'updated_at' => $subject->updated_at,
            ])
            ->values()
            ->all();
    }

    /**
     * Return division-wide classes/sections from tbl_scanup_sections.
     */
    public function classes(): array
    {
        $schoolCodes = $this->schoolDepartments()->pluck('department_id')->map(fn ($id) => (string) $id);
        $schoolIds = School::whereIn('deped_school_id', $schoolCodes)->pluck('id');

        return Section::query()
            ->from('tbl_scanup_sections as sections')
            ->leftJoin('tbl_scanup_schools as schools', 'sections.school_id', '=', 'schools.id')
            ->leftJoin('tbl_scanup_users as advisers', 'sections.teacher_id', '=', 'advisers.id')
            ->leftJoin('tbl_scanup_students as students', function ($join) {
                $join->on('sections.id', '=', 'students.section_id')
                    ->whereNull('students.deleted_at');
            })
            ->whereIn('sections.school_id', $schoolIds)
            ->select([
                'sections.id',
                'sections.name',
                'sections.grade_level',
                'sections.school_id',
                'sections.teacher_id',
                'schools.name as school_name',
                'schools.deped_school_id',
                'advisers.name as adviser_name',
                DB::raw('COUNT(students.id) as learner_count'),
            ])
            ->groupBy([
                'sections.id',
                'sections.name',
                'sections.grade_level',
                'sections.school_id',
                'sections.teacher_id',
                'schools.name',
                'schools.deped_school_id',
                'advisers.name',
            ])
            ->orderBy('schools.name')
            ->orderBy('sections.grade_level')
            ->orderBy('sections.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'school_id' => (int) $row->school_id,
                'deped_school_id' => (string) $row->deped_school_id,
                'school_name' => (string) $row->school_name,
                'grade_level' => (string) $row->grade_level,
                'section' => (string) $row->name,
                'teacher_id' => $row->teacher_id ? (int) $row->teacher_id : null,
                'adviser_name' => $row->adviser_name ?: 'Not assigned',
                'learner_count' => (int) $row->learner_count,
            ])
            ->values()
            ->all();
    }

    /**
     * Return division-wide attendance summaries and recent scan rows.
     */
    public function attendance(): array
    {
        $today = now()->toDateString();
        $schoolCodes = $this->schoolDepartments()->pluck('department_id')->map(fn ($id) => (string) $id);
        $schoolIds = School::whereIn('deped_school_id', $schoolCodes)->pluck('id');

        $summary = Attendance::query()
            ->from('tbl_scanup_attendance as attendance')
            ->leftJoin('tbl_scanup_schools as schools', 'attendance.school_id', '=', 'schools.id')
            ->whereIn('attendance.school_id', $schoolIds)
            ->whereDate('attendance.scanned_at', $today)
            ->select([
                'attendance.school_id',
                'schools.name as school_name',
                'schools.deped_school_id',
                DB::raw('COUNT(*) as scans_today'),
                DB::raw('COUNT(DISTINCT attendance.student_id) as learners_scanned'),
                DB::raw("SUM(CASE WHEN attendance.status = 'late' THEN 1 ELSE 0 END) as late_count"),
                DB::raw("SUM(CASE WHEN attendance.status <> 'late' OR attendance.status IS NULL THEN 1 ELSE 0 END) as on_time_count"),
                DB::raw('MAX(attendance.scanned_at) as last_scan_at'),
            ])
            ->groupBy([
                'attendance.school_id',
                'schools.name',
                'schools.deped_school_id',
            ])
            ->orderByDesc('scans_today')
            ->get()
            ->map(fn ($row) => [
                'school_id' => (int) $row->school_id,
                'deped_school_id' => (string) $row->deped_school_id,
                'school_name' => (string) $row->school_name,
                'scans_today' => (int) $row->scans_today,
                'learners_scanned' => (int) $row->learners_scanned,
                'on_time_count' => (int) $row->on_time_count,
                'late_count' => (int) $row->late_count,
                'last_scan_at' => $row->last_scan_at,
            ])
            ->values()
            ->all();

        $recent = Attendance::query()
            ->from('tbl_scanup_attendance as attendance')
            ->leftJoin('tbl_scanup_schools as schools', 'attendance.school_id', '=', 'schools.id')
            ->leftJoin('tbl_scanup_students as students', 'attendance.student_id', '=', 'students.id')
            ->whereIn('attendance.school_id', $schoolIds)
            ->select([
                'attendance.id',
                'attendance.school_id',
                'attendance.scanned_at',
                'attendance.session',
                'attendance.status',
                'schools.name as school_name',
                'schools.deped_school_id',
                'students.first_name',
                'students.last_name',
                'students.student_number',
                'students.grade',
                'students.section',
            ])
            ->orderByDesc('attendance.scanned_at')
            ->limit(100)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'school_id' => (int) $row->school_id,
                'deped_school_id' => (string) $row->deped_school_id,
                'school_name' => (string) $row->school_name,
                'learner_name' => trim(($row->last_name ?? '') . ', ' . ($row->first_name ?? '')) ?: 'Unknown learner',
                'student_number' => $row->student_number,
                'grade' => $row->grade,
                'section' => $row->section,
                'session' => $row->session,
                'status' => $row->status,
                'scanned_at' => $row->scanned_at,
            ])
            ->values()
            ->all();

        return [
            'summary' => $summary,
            'recent' => $recent,
        ];
    }

    private function scanupSchoolIds(): Collection
    {
        $schoolCodes = $this->schoolDepartments()->pluck('department_id')->map(fn ($id) => (string) $id);

        return School::whereIn('deped_school_id', $schoolCodes)->pluck('id');
    }

    /**
     * Build school-scoped trend data using the same filter idea as the admin dashboard.
     */
    private function attendanceTrendsFor(int $schoolId, array $filters): Collection
    {
        $requestedGroupBy = (string) ($filters['group_by'] ?? 'day');
        $groupBy = in_array($requestedGroupBy, ['day', 'week', 'month'], true)
            ? $requestedGroupBy
            : 'day';
        $grade = trim((string) ($filters['grade'] ?? ''));
        $section = trim((string) ($filters['section'] ?? ''));

        $query = Attendance::query()
            ->from('tbl_scanup_attendance', 'attendance')
            ->join('tbl_scanup_students as students', 'attendance.student_id', '=', 'students.id')
            ->where('attendance.school_id', $schoolId);

        if ($grade !== '') {
            $query->where('students.grade', $grade);
        }

        if ($section !== '') {
            $query->where('students.section', $section);
        }

        match ($groupBy) {
            'month' => $query
                ->select(DB::raw("DATE_FORMAT(attendance.scanned_at, '%Y-%m') as label"), DB::raw('COUNT(DISTINCT attendance.student_id) as count'))
                ->where('attendance.scanned_at', '>=', now()->subMonths(12)),
            'week' => $query
                ->select(DB::raw('YEARWEEK(attendance.scanned_at) as label'), DB::raw('COUNT(DISTINCT attendance.student_id) as count'))
                ->where('attendance.scanned_at', '>=', now()->subWeeks(12)),
            default => $query
                ->select(DB::raw('DATE(attendance.scanned_at) as label'), DB::raw('COUNT(DISTINCT attendance.student_id) as count'))
                ->where('attendance.scanned_at', '>=', now()->subDays(30)),
        };

        return $query->groupBy('label')->orderBy('label')->get();
    }

    /**
     * Return grade/section filter options from this school's encoded students.
     */
    private function dashboardFilterOptionsFor(int $schoolId): array
    {
        $grades = Student::where('school_id', $schoolId)
            ->whereNotNull('grade')
            ->where('grade', '<>', '')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade')
            ->values()
            ->all();

        $sections = Student::where('school_id', $schoolId)
            ->whereNotNull('section')
            ->where('section', '<>', '')
            ->select('grade', 'section')
            ->distinct()
            ->orderBy('grade')
            ->orderBy('section')
            ->get()
            ->map(fn (Student $student) => [
                'grade' => (string) ($student->grade ?? ''),
                'section' => (string) ($student->section ?? ''),
            ])
            ->values()
            ->all();

        return [
            'grades' => $grades,
            'sections' => $sections,
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
     * Resolve the district name from EHRIS tbl_district using tbl_depart.business_id.
     */
    private function districtNameFor(string $districtCode): string
    {
        static $districts = null;

        if ($districts === null) {
            try {
                $connection = (new EhrisDepartment())->getConnectionName() ?: config('database.default');
                $schema = Schema::connection($connection);

                if (
                    !$schema->hasTable('tbl_district') ||
                    !$schema->hasColumn('tbl_district', 'district_code') ||
                    !$schema->hasColumn('tbl_district', 'district_name')
                ) {
                    $districts = collect();
                } else {
                    $districts = DB::connection($connection)
                        ->table('tbl_district')
                        ->pluck('district_name', 'district_code');
                }
            } catch (Throwable $exception) {
                Log::warning('System Admin district lookup failed.', [
                    'method' => __METHOD__,
                    'table' => 'tbl_district',
                    'error' => $exception->getMessage(),
                ]);

                $districts = collect();
            }
        }

        $code = trim($districtCode);
        if ($code === '') {
            return 'Unassigned District';
        }

        return $this->normalizeDistrictName((string) ($districts[$code] ?? $this->districtNameFromCode($code)), $code);
    }

    private function districtNameFromCode(string $districtCode): string
    {
        if (preg_match('/^920(\d{2})$/', $districtCode, $matches)) {
            return 'District ' . (int) $matches[1];
        }

        if (preg_match('/(\d+)$/', $districtCode, $matches)) {
            return 'District ' . (int) $matches[1];
        }

        return 'District ' . $districtCode;
    }

    private function normalizeDistrictName(string $districtName, string $districtCode): string
    {
        $name = trim($districtName);
        if (preg_match('/^District\s+920(\d{2})$/i', $name, $matches)) {
            return 'District ' . (int) $matches[1];
        }

        if ($name !== '') {
            return $name;
        }

        return $this->districtNameFromCode($districtCode);
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
     * Classify school names for filter chips.
     */
    private function schoolTypeFor(string $name): string
    {
        $lower = strtolower($name);

        if (strpos($lower, 'integrated') !== false) {
            return 'Integrated';
        }

        if (strpos($lower, 'national high') !== false || strpos($lower, 'arts and trades') !== false) {
            return 'Secondary';
        }

        return 'Elementary';
    }

    /**
     * Produce a short setup health verdict for the System Admin table.
     */
    private function healthFor(?int $schoolId, int $students, int $teachers, int $attendanceToday): array
    {
        if (!$schoolId) {
            return [
                'status' => 'needs_setup',
                'label' => 'School not created',
                'severity' => 'danger',
            ];
        }

        if ($teachers === 0) {
            return [
                'status' => 'no_teachers',
                'label' => 'No teachers synced',
                'severity' => 'warning',
            ];
        }

        if ($students === 0) {
            return [
                'status' => 'no_students',
                'label' => 'No students encoded',
                'severity' => 'warning',
            ];
        }

        if ($attendanceToday === 0) {
            return [
                'status' => 'no_scans_today',
                'label' => 'No scans today',
                'severity' => 'notice',
            ];
        }

        return [
            'status' => 'healthy',
            'label' => 'Active today',
            'severity' => 'success',
        ];
    }

    /**
     * Count district-level PSDS accounts from EHRIS as the dashboard district basis.
     */
    private function districtCount(): int
    {
        return EhrisUser::active()
            ->where(function ($query) {
                $query->where('role', 'PSDS')
                    ->orWhere('job_title', 'like', '%District Supervisor%');
            })
            ->count();
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
