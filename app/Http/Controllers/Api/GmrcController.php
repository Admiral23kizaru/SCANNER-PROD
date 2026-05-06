<?php

namespace App\Http\Controllers\Api;

use App\Exports\GmrcTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\GmrcScore;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class GmrcController extends Controller
{
    private function schoolScope(): ?int
    {
        return auth()->user()->school_id;
    }

    private function studentScopeQuery(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolScope();

        $query = Student::query()->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        // Mirror StudentController visibility rules for teachers.
        if ($user->role?->name === 'Teacher') {
            if ($user->grade_level && $user->section) {
                $query->where('grade', $user->grade_level)->where('section', $user->section);
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)->orWhere('created_by', $user->id);
                });
            }
        }

        return $query;
    }

    private function parseWrongItems(string $raw, int $totalItems): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];

        $parts = preg_split('/\s*,\s*/', $raw);
        $items = [];
        foreach ($parts as $p) {
            if ($p === '') continue;
            if (!preg_match('/^\d+$/', $p)) {
                throw new \InvalidArgumentException('Wrong items must contain only numbers and commas.');
            }
            $n = (int) $p;
            if ($n < 1 || $n > $totalItems) {
                throw new \InvalidArgumentException("Wrong items must be between 1 and {$totalItems}.");
            }
            $items[] = $n;
        }
        $items = array_values(array_unique($items));
        sort($items);
        return $items;
    }

    public function meta(Request $request): JsonResponse
    {
        $query = $this->studentScopeQuery($request);

        $grades = (clone $query)->whereNotNull('grade')->distinct()->orderBy('grade')->pluck('grade')->values();
        $sections = (clone $query)->whereNotNull('section')->distinct()->orderBy('section')->pluck('section')->values();

        return response()->json([
            'grades' => $grades,
            'sections' => $sections,
            'default_grade_level' => $request->user()->grade_level,
            'default_section' => $request->user()->section,
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grade_level' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $q = $this->studentScopeQuery($request);
        if ($request->filled('grade_level')) $q->where('grade', $request->input('grade_level'));
        if ($request->filled('section')) $q->where('section', $request->input('section'));
        if ($request->filled('search')) {
            $term = '%' . trim((string) $request->input('search')) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('student_number', 'like', $term);
            });
        }

        $students = $q->orderBy('last_name')->orderBy('first_name')->limit(200)->get([
            'id', 'first_name', 'last_name', 'middle_name', 'student_number', 'grade', 'section',
        ])->map(function (Student $s) {
            return [
                'id' => $s->id,
                'name' => trim(($s->last_name ?? '') . ', ' . ($s->first_name ?? '')),
                'student_number' => $s->student_number,
                'grade' => $s->grade,
                'section' => $s->section,
            ];
        });

        return response()->json(['data' => $students]);
    }

    public function recent(Request $request): JsonResponse
    {
        $studentIds = $this->studentScopeQuery($request)->pluck('id');
        $entries = GmrcScore::with('student:id,first_name,last_name,grade,section')
            ->whereIn('student_id', $studentIds)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (GmrcScore $e) {
                $s = $e->student;
                $studentName = $s ? trim(($s->last_name ?? '') . ', ' . ($s->first_name ?? '')) : 'Student';
                return [
                    'id' => $e->id,
                    'section' => $e->section,
                    'grade_level' => $e->grade_level,
                    'student' => $studentName,
                    'wrong_items' => $e->wrong_items ?? [],
                    'score' => $e->score,
                    'total_items' => $e->total_items,
                    'created_at' => optional($e->created_at)->toIso8601String(),
                ];
            });

        return response()->json(['data' => $entries]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer'],
            'wrong_items' => ['nullable', 'string', 'max:500'],
            'total_items' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $totalItems = (int) ($request->input('total_items') ?? 50);

        $student = $this->studentScopeQuery($request)->find((int) $request->input('student_id'));
        if (!$student) {
            return response()->json(['message' => 'Student not found or not accessible.'], 404);
        }

        try {
            $wrongItemsArr = $this->parseWrongItems((string) ($request->input('wrong_items') ?? ''), $totalItems);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $score = max(0, $totalItems - count($wrongItemsArr));

        $entry = GmrcScore::create([
            'student_id' => $student->id,
            'section' => (string) ($student->section ?? ''),
            'grade_level' => (string) ($student->grade ?? ''),
            'wrong_items' => $wrongItemsArr,
            'total_items' => $totalItems,
            'score' => $score,
        ]);

        $studentName = trim(($student->last_name ?? '') . ', ' . ($student->first_name ?? ''));

        return response()->json([
            'message' => 'Saved.',
            'entry' => [
                'id' => $entry->id,
                'student' => $studentName,
                'section' => $entry->section,
                'grade_level' => $entry->grade_level,
                'wrong_items' => $entry->wrong_items ?? [],
                'score' => $entry->score,
                'total_items' => $entry->total_items,
                'created_at' => optional($entry->created_at)->toIso8601String(),
            ],
        ], 201);
    }

    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grade_level' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:100'],
            'total_items' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $q = $this->studentScopeQuery($request);
        if ($request->filled('grade_level')) $q->where('grade', $request->input('grade_level'));
        if ($request->filled('section')) $q->where('section', $request->input('section'));

        $students = $q->orderBy('last_name')->orderBy('first_name')->get();
        $totalItems = (int) ($request->input('total_items') ?? 50);

        $gradeLabel = $request->input('grade_level') ? ('G' . preg_replace('/\D+/', '', (string) $request->input('grade_level'))) : 'AllGrades';
        $sectionLabel = $request->input('section') ? preg_replace('/\s+/', '', (string) $request->input('section')) : 'AllSections';
        $filename = "GMRC_Template_{$gradeLabel}_{$sectionLabel}.xlsx";

        return Excel::download(new GmrcTemplateExport($students, $totalItems), $filename);
    }
}

