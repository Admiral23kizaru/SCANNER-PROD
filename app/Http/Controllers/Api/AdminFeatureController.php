<?php

namespace App\Http\Controllers\Api;

use App\Models\AdminCalendarEvent;
use App\Models\AssessmentLog;
use App\Models\Attendance;
use App\Models\ParentGuardian;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AdminFeatureController extends BaseController
{
    public function schoolOverview(): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $sections = Section::with('teacher:id,name')
            ->withCount('students')
            ->where('school_id', $schoolId)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get()
            ->map(fn (Section $section) => [
                'id' => $section->id,
                'grade_level' => $section->grade_level,
                'section' => $section->name,
                'learners_count' => $section->students_count,
                'adviser' => $section->teacher?->name ?? 'Unassigned',
            ]);

        return response()->json(['data' => $sections]);
    }

    public function guardians(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $guardians = ParentGuardian::with('student:id,first_name,last_name,grade,section')
            ->where('school_id', $schoolId)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $guardians]);
    }

    public function storeGuardian(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $validator = Validator::make($request->all(), [
            'student_id' => ['nullable', 'integer', 'exists:tbl_scanup_students,id'],
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:80'],
            'contact_number' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_primary' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('student_id')) {
            $belongsToSchool = Student::where('school_id', $schoolId)->where('id', $request->integer('student_id'))->exists();
            if (! $belongsToSchool) {
                return response()->json(['message' => 'Selected learner does not belong to your school.'], 422);
            }
        }

        $guardian = ParentGuardian::create(array_merge($validator->validated(), [
            'school_id' => $schoolId,
            'is_primary' => $request->boolean('is_primary'),
        ]));

        return response()->json(['message' => 'Guardian saved.', 'data' => $guardian->load('student:id,first_name,last_name,grade,section')], 201);
    }

    public function assessmentLogs(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        try {
            $logs = AssessmentLog::with('student:id,first_name,last_name,grade,section', 'subject:id,name')
                ->where('school_id', $schoolId)
                ->latest()
                ->limit(100)
                ->get();
        } catch (Throwable $exception) {
            Log::error('Admin assessment logs failed.', [
                'method' => __METHOD__,
                'school_id' => $schoolId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['data' => [], 'message' => 'Unable to read assessment logs. Check laravel.log for Admin assessment logs failed.']);
        }

        return response()->json(['data' => $logs]);
    }

    public function storeAssessmentLog(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $validator = Validator::make($request->all(), [
            'student_id' => ['nullable', 'integer', 'exists:tbl_scanup_students,id'],
            'subject_id' => ['nullable', 'integer', 'exists:tbl_scanup_subjects,id'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'grade_level' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:100'],
            'assessment_type' => ['nullable', 'string', 'max:100'],
            'score' => ['nullable', 'integer', 'min:0', 'max:500'],
            'total_items' => ['nullable', 'integer', 'min:0', 'max:500'],
            'least_mastered_skills' => ['nullable', 'array'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $log = AssessmentLog::create(array_merge($validator->validated(), [
            'school_id' => $schoolId,
            'created_by' => $request->user()?->id,
        ]));

        return response()->json(['message' => 'Assessment log saved.', 'data' => $log], 201);
    }

    public function leastMasteredSkills(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $subjectId = $request->input('subject_id');
        $grade = $request->input('grade_level');
        $section = $request->input('section');
        $schoolYear = $request->input('school_year');

        try {
            $logs = AssessmentLog::query()
                ->where('school_id', $schoolId)
                ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
                ->when($grade, fn ($q) => $q->where('grade_level', $grade))
                ->when($section, fn ($q) => $q->where('section', $section))
                ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
                ->get(['least_mastered_skills']);
        } catch (Throwable $exception) {
            Log::error('Admin least mastered skills failed.', [
                'method' => __METHOD__,
                'school_id' => $schoolId,
                'table' => 'tbl_scanup_assessment_logs',
                'error' => $exception->getMessage(),
            ]);

            $logs = collect();
        }

        $items = [];
        foreach ($logs as $log) {
            foreach (($log->least_mastered_skills ?? []) as $skill) {
                $label = trim((string) $skill);
                if ($label !== '') {
                    $items[$label] = ($items[$label] ?? 0) + 1;
                }
            }
        }

        arsort($items);

        $filters = [
            'school_years' => AssessmentLog::where('school_id', $schoolId)->whereNotNull('school_year')->distinct()->orderBy('school_year')->pluck('school_year')->values(),
            'subjects' => Subject::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'grades' => Student::where('school_id', $schoolId)->whereNotNull('grade')->distinct()->orderBy('grade')->pluck('grade')->values(),
            'sections' => Student::where('school_id', $schoolId)->whereNotNull('section')->distinct()->orderBy('section')->pluck('section')->values(),
        ];

        return response()->json([
            'filters' => $filters,
            'data' => collect($items)->take(12)->map(fn ($count, $skill) => ['skill' => $skill, 'count' => $count])->values(),
        ]);
    }

    public function attendanceToday(): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $rows = Attendance::with('student:id,first_name,last_name,grade,section')
            ->where('school_id', $schoolId)
            ->whereDate('scanned_at', now()->toDateString())
            ->latest('scanned_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $month = $request->input('month', now()->format('Y-m'));

        $events = AdminCalendarEvent::where('school_id', $schoolId)
            ->where('event_date', 'like', $month . '%')
            ->orderBy('event_date')
            ->get();

        return response()->json(['data' => $events]);
    }

    public function storeCalendarEvent(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $event = AdminCalendarEvent::create(array_merge($validator->validated(), [
            'school_id' => $schoolId,
            'created_by' => $request->user()?->id,
            'color' => $request->input('color', '#14b8a6'),
        ]));

        return response()->json(['message' => 'Event saved.', 'data' => $event], 201);
    }

    public function deleteCalendarEvent(int $id): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        AdminCalendarEvent::where('school_id', $schoolId)->where('id', $id)->delete();

        return response()->json(['message' => 'Event deleted.']);
    }
}
