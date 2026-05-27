<?php

namespace App\Http\Controllers\Api;

use App\Exports\LearningAssessmentTemplateExport;
use App\Exports\LearningAssessmentAnalyzedExport;
use App\Models\LearningAssessmentFile;
use App\Models\LearningAssessmentScore;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectSection;
use App\Services\LearningAssessmentExcelAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

class LearningAssessmentController extends BaseController
{
    /**
     * Return the authenticated school_id for Semestral Assessment operations.
     */
    private function schoolScope(?Request $request = null): int
    {
        $request ??= request();
        $user = $request->user();

        if ($user?->role?->name === 'System Admin') {
            $schoolId = (int) $request->input('school_id');
            if (! $schoolId || ! School::whereKey($schoolId)->exists()) {
                abort(422, 'Select a valid school before using Semestral Assessment.');
            }

            return $schoolId;
        }

        return $this->getAuthSchoolId();
    }

    /**
     * Build a student query constrained to the authenticated school and teacher visibility rules.
     */
    private function studentScopeQuery(Request $request)
    {
        $user = $request->user();
        $schoolId = $this->schoolScope($request);

        $query = Student::query()->where('school_id', $schoolId);

        if (in_array($user->role?->name, ['Teacher', 'Adviser', 'Subject Teacher'], true)) {
            if ($handled = $this->handledAssignment($request)) {
                $sectionName = $handled->section?->name;
                $query->where('grade', $handled->grade_level)
                    ->where(function ($q) use ($handled, $sectionName) {
                        $q->where('section_id', $handled->section_id)
                            ->orWhere('section', $sectionName);
                    });
            } elseif ($user->grade_level && $user->section) {
                $query->where('grade', $user->grade_level)->where('section', $user->section);
            } elseif ($user->role?->name !== 'Subject Teacher') {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)->orWhere('created_by', $user->id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    private function handledAssignment(Request $request): ?TeacherSubjectSection
    {
        if (! $request->filled('handled_section_id')) {
            return null;
        }

        $query = TeacherSubjectSection::query()
            ->with('section:id,name,grade_level')
            ->where('school_id', $this->schoolScope($request))
            ->where('teacher_id', $request->user()->id)
            ->where('section_id', (int) $request->input('handled_section_id'));

        if ($request->filled('subject_id')) {
            $query->where('subject_id', (int) $request->input('subject_id'));
        }

        return $query->first();
    }

    private function parseWrongItems(string $raw, int $totalItems): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', $raw);
        $items = [];
        foreach ($parts as $p) {
            if ($p === '') {
                continue;
            }
            if (! preg_match('/^\d+$/', $p)) {
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

        $sectionQuery = (clone $query)->whereNotNull('section');
        if ($request->filled('grade_level')) {
            $sectionQuery->where('grade', $request->input('grade_level'));
        }
        $sections = $sectionQuery->distinct()->orderBy('section')->pluck('section')->values();
        $schoolId = $this->schoolScope($request);
        $subjects = $this->subjectOptions($request, $schoolId);

        $handled = $this->handledAssignment($request);

        return response()->json([
            'grades' => $grades,
            'sections' => $sections,
            'subjects' => $subjects,
            'default_grade_level' => $handled?->grade_level ?? $request->user()->grade_level,
            'default_section' => $handled?->section?->name ?? $request->user()->section,
            'default_subject_id' => $handled?->subject_id,
        ]);
    }

    public function students(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grade_level' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $q = $this->studentScopeQuery($request);
        if ($request->filled('grade_level')) {
            $q->where('grade', $request->input('grade_level'));
        }
        if ($request->filled('section')) {
            $q->where('section', $request->input('section'));
        }
        if ($request->filled('search')) {
            $term = '%' . trim((string) $request->input('search')) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('student_number', 'like', $term);
            });
        }

        $students = $q->orderBy('grade')->orderBy('section')->orderBy('last_name')->orderBy('first_name')->limit(200)->get([
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
        $entries = LearningAssessmentScore::with('student:id,first_name,last_name,grade,section', 'subject:id,name')
            ->whereIn('student_id', $studentIds)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (LearningAssessmentScore $e) {
                $s = $e->student;
                $sub = $e->subject;
                $studentName = $s ? trim(($s->last_name ?? '') . ', ' . ($s->first_name ?? '')) : 'Student';

                return [
                    'id' => $e->id,
                    'section' => $e->section,
                    'grade_level' => $e->grade_level,
                    'subject' => $sub?->name,
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
            'subject_id' => ['required', 'integer'],
            'wrong_items' => ['nullable', 'string', 'max:500'],
            'total_items' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $totalItems = (int) ($request->input('total_items') ?? 50);

        $student = $this->studentScopeQuery($request)->find((int) $request->input('student_id'));
        if (! $student) {
            return response()->json(['message' => 'Student not found or not accessible.'], 404);
        }

        $schoolId = $this->schoolScope($request);
        $subject = $this->subjectForScore($request, (int) $request->input('subject_id'), $schoolId);

        if (! $subject) {
            return response()->json(['message' => 'Subject not found.'], 404);
        }

        try {
            $wrongItemsArr = $this->parseWrongItems((string) ($request->input('wrong_items') ?? ''), $totalItems);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $score = max(0, $totalItems - count($wrongItemsArr));

        $entry = LearningAssessmentScore::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
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
                'subject' => $subject->name,
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
            'subject_id' => ['nullable', 'integer'],
            'total_items' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $q = $this->studentScopeQuery($request);
        if ($request->filled('grade_level')) {
            $q->where('grade', $request->input('grade_level'));
        }
        if ($request->filled('section')) {
            $q->where('section', $request->input('section'));
        }

        $students = $q->orderBy('grade')->orderBy('section')->orderBy('last_name')->orderBy('first_name')->get();
        $totalItems = (int) ($request->input('total_items') ?? 50);

        $schoolId = $this->schoolScope($request);
        $sheetTitle = 'Semestral Assessment';
        if ($request->filled('subject_id')) {
            $subjectName = $this->subjectName($request, (int) $request->input('subject_id'), $schoolId);
            if (trim($subjectName) !== '') {
                $sheetTitle = $subjectName;
            }
        }

        $gradeLabel = $request->input('grade_level') ? ('G' . preg_replace('/\D+/', '', (string) $request->input('grade_level'))) : 'AllGrades';
        $sectionLabel = $request->input('section') ? preg_replace('/\s+/', '', (string) $request->input('section')) : 'AllSections';
        $filename = "Semestral_Assessment_Template_{$gradeLabel}_{$sectionLabel}.xlsx";

        return Excel::download(new LearningAssessmentTemplateExport($students, $totalItems, $sheetTitle), $filename);
    }

    public function importAnalyze(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:15360'],
        ]);

        $path = $request->file('file')?->getRealPath();
        if (! $path || ! is_readable($path)) {
            return response()->json(['message' => 'Could not read the uploaded file.'], 422);
        }

        try {
            $data = app(LearningAssessmentExcelAnalyzer::class)->analyze($path);

            return response()->json($data);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Could not analyze that Excel file. Use the roster template: row 1 headers, row 2 answer key, students from row 3.',
            ], 422);
        }
    }

    public function importAnalyzeExport(Request $request)
    {
        $result = $this->validatedAnalyzePayload($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        [$validated, $sheetTitle] = $result;
        $filename = 'Semestral_Assessment_Analyzed_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new LearningAssessmentAnalyzedExport($validated, $sheetTitle), $filename);
    }

    public function files(Request $request): JsonResponse
    {
        $schoolId = $this->schoolScope($request);

        $files = LearningAssessmentFile::query()
            ->where('school_id', $schoolId)
            ->when($this->handledAssignment($request), function ($q, TeacherSubjectSection $handled) {
                $q->where('subject_id', $handled->subject_id)
                    ->where('grade_level', $handled->grade_level)
                    ->where('section', $handled->section?->name);
            })
            ->latest('analyzed_at')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (LearningAssessmentFile $file) => $this->fileToArray($file));

        return response()->json(['data' => $files]);
    }

    public function saveAnalyzedFile(Request $request): JsonResponse
    {
        $metaValidator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'analyzed_at' => ['required', 'date'],
            'subject_id' => ['nullable', 'integer'],
            'grade_level' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:100'],
        ]);

        if ($metaValidator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $metaValidator->errors()], 422);
        }

        $result = $this->validatedAnalyzePayload($request);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        [$validated, $sheetTitle] = $result;
        $schoolId = $this->schoolScope($request);
        $handled = $this->handledAssignment($request);
        $title = trim((string) $request->input('title'));
        $safeTitle = preg_replace('/[^A-Za-z0-9_-]+/', '_', $title) ?: 'Semestral_Assessment';
        $filename = $safeTitle . '_Analyzed_' . now()->format('Y-m-d_His') . '.xlsx';
        $path = 'learning-assessment/analyzed/' . $schoolId . '/' . $filename;

        $contents = Excel::raw(
            new LearningAssessmentAnalyzedExport($validated, $sheetTitle),
            ExcelWriter::XLSX
        );
        Storage::disk('local')->put($path, $contents);

        $file = LearningAssessmentFile::create([
            'school_id' => $schoolId,
            'created_by' => $request->user()?->id,
            'subject_id' => $handled?->subject_id ?? $request->input('subject_id'),
            'title' => $title,
            'analyzed_at' => $request->date('analyzed_at')?->toDateString() ?? now()->toDateString(),
            'sheet_title' => $sheetTitle,
            'grade_level' => $handled?->grade_level ?? $request->input('grade_level'),
            'section' => $handled?->section?->name ?? $request->input('section'),
            'student_count' => count($validated['students']),
            'item_count' => count($validated['item_numbers']),
            'filename' => $filename,
            'file_path' => $path,
            'analysis_payload' => $validated,
        ]);

        return response()->json([
            'message' => 'Analyzed Excel file saved.',
            'file' => $this->fileToArray($file),
        ], 201);
    }

    public function downloadAnalyzedFile(Request $request, int $id)
    {
        $file = $this->fileQuery($request)->find($id);
        if (! $file) {
            return response()->json(['message' => 'Analyzed file not found.'], 404);
        }

        if (! Storage::disk('local')->exists($file->file_path)) {
            return response()->json(['message' => 'Saved Excel file is missing from storage.'], 404);
        }

        return Storage::disk('local')->download($file->file_path, $file->filename);
    }

    public function previewAnalyzedFile(Request $request, int $id): JsonResponse
    {
        $file = $this->fileQuery($request)->find($id);
        if (! $file) {
            return response()->json(['message' => 'Analyzed file not found.'], 404);
        }

        $payload = $file->analysis_payload;
        if (! is_array($payload) || empty($payload['item_numbers']) || empty($payload['item_stats'])) {
            return response()->json(['message' => 'Saved analysis preview is unavailable.'], 404);
        }

        $payload['answer_key'] = is_array($payload['answer_key'] ?? null) ? $payload['answer_key'] : [];
        $payload['students'] = is_array($payload['students'] ?? null) ? $payload['students'] : [];
        $payload['item_stats'] = collect($payload['item_stats'])->map(function ($item) {
            $totalCorrect = (int) ($item['total_correct'] ?? 0);
            $examinees = max(0, (int) ($item['examinees'] ?? 0));
            $difficultyPct = $item['difficulty_pct'] ?? ($examinees > 0 ? round(($totalCorrect / $examinees) * 100, 2) : null);
            $pValue = $item['p_value'] ?? ($difficultyPct !== null ? round(((float) $difficultyPct) / 100, 4) : null);

            return [
                ...$item,
                'total_correct' => $totalCorrect,
                'examinees' => $examinees,
                'difficulty_pct' => $difficultyPct,
                'p_value' => $pValue,
                'difficulty_level' => $item['difficulty_level'] ?? 'Unspecified',
                'interpretation' => $item['interpretation'] ?? ($item['difficulty_level'] ?? 'Unspecified'),
            ];
        })->values()->all();

        $payload['total_keyed_items'] = count(array_filter($payload['answer_key'], fn ($v) => trim((string) $v) !== ''));
        if ($payload['total_keyed_items'] === 0) {
            $payload['total_keyed_items'] = count($payload['item_numbers']);
        }
        $payload['student_count'] = count($payload['students']);

        return response()->json([
            'file' => $this->fileToArray($file),
            'analysis' => $payload,
        ]);
    }

    public function deleteAnalyzedFile(Request $request, int $id): JsonResponse
    {
        $file = $this->fileQuery($request)->find($id);
        if (! $file) {
            return response()->json(['message' => 'Analyzed file not found.'], 404);
        }

        Storage::disk('local')->delete($file->file_path);
        $file->delete();

        return response()->json(['message' => 'Analyzed file deleted.']);
    }

    private function validatedAnalyzePayload(Request $request): array|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sheet_title' => ['nullable', 'string', 'max:100'],
            'item_numbers' => ['required', 'array', 'min:1', 'max:200'],
            'item_numbers.*' => ['integer', 'min:1', 'max:500'],
            'answer_key' => ['required', 'array'],
            'students' => ['required', 'array', 'min:1', 'max:600'],
            'students.*.name' => ['required', 'string', 'max:500'],
            'students.*.answers' => ['required', 'array'],
            'students.*.score' => ['required', 'integer', 'min:0'],
            'students.*.percentage' => ['required', 'numeric'],
            'item_stats' => ['required', 'array'],
            'item_stats.*.item' => ['required', 'integer'],
            'item_stats.*.total_correct' => ['required', 'integer', 'min:0'],
            'item_stats.*.examinees' => ['required', 'integer', 'min:0'],
            'item_stats.*.p_value' => ['nullable', 'numeric'],
            'item_stats.*.difficulty_pct' => ['nullable', 'numeric'],
            'item_stats.*.difficulty_level' => ['required', 'string', 'max:100'],
            'item_stats.*.interpretation' => ['required', 'string', 'max:500'],
            'item_stats.*.what_it_means' => ['nullable', 'string', 'max:500'],
            'item_stats.*.recommended_action' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $sheetTitle = (string) ($validated['sheet_title'] ?? 'Semestral Assessment');
        unset($validated['sheet_title']);

        $n = count($validated['item_numbers']);
        if (count($validated['answer_key']) !== $n) {
            return response()->json(['message' => 'answer_key must have the same length as item_numbers.'], 422);
        }
        if (count($validated['item_stats']) !== $n) {
            return response()->json(['message' => 'item_stats must have the same length as item_numbers.'], 422);
        }
        foreach ($validated['students'] as $i => $stu) {
            if (count($stu['answers']) !== $n) {
                return response()->json([
                    'message' => 'Each student answers array must match item_numbers length (index ' . $i . ').',
                ], 422);
            }
        }

        return [$validated, $sheetTitle];
    }

    private function fileQuery(Request $request)
    {
        return LearningAssessmentFile::query()
            ->where('school_id', $this->schoolScope($request))
            ->when($this->handledAssignment($request), function ($q, TeacherSubjectSection $handled) {
                $q->where('subject_id', $handled->subject_id)
                    ->where('grade_level', $handled->grade_level)
                    ->where('section', $handled->section?->name);
            });
    }

    private function fileToArray(LearningAssessmentFile $file): array
    {
        return [
            'id' => $file->id,
            'title' => $file->title,
            'analyzed_at' => $file->analyzed_at?->toDateString(),
            'sheet_title' => $file->sheet_title,
            'grade_level' => $file->grade_level,
            'section' => $file->section,
            'student_count' => $file->student_count,
            'item_count' => $file->item_count,
            'filename' => $file->filename,
            'created_at' => $file->created_at?->toIso8601String(),
        ];
    }

    private function subjectOptions(Request $request, int $schoolId)
    {
        if ($handled = $this->handledAssignment($request)) {
            return Subject::query()
                ->where('school_id', $schoolId)
                ->whereKey($handled->subject_id)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return Subject::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function subjectName(Request $request, int $subjectId, int $schoolId): string
    {
        return (string) Subject::query()
            ->where('school_id', $schoolId)
            ->whereKey($subjectId)
            ->value('name');
    }

    private function subjectForScore(Request $request, int $subjectId, int $schoolId): ?Subject
    {
        return Subject::query()
            ->where('school_id', $schoolId)
            ->find($subjectId);
    }
}
