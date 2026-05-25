<?php

namespace App\Http\Controllers\Api;

use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherSubjectSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherSubjectSectionController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $this->getAuthSchoolId();

        return response()->json([
            'data' => TeacherSubjectSection::with(['subject:id,name', 'section:id,name,grade_level'])
                ->where('school_id', $schoolId)
                ->where('teacher_id', $user->id)
                ->orderBy('grade_level')
                ->orderBy(
                    Section::select('name')
                        ->whereColumn('tbl_scanup_sections.id', 'tbl_scanup_teacher_subject_sections.section_id')
                        ->limit(1)
                )
                ->get()
                ->map(fn (TeacherSubjectSection $handled) => $this->handledToArray($handled))
                ->values(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $this->getAuthSchoolId();
        $gradeLevel = trim((string) $user->grade_level);

        $sections = Section::query()
            ->where('school_id', $schoolId)
            ->when($gradeLevel !== '', fn ($q) => $q->where('grade_level', $gradeLevel))
            ->when($user->section, fn ($q) => $q->where('name', '!=', $user->section))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level', 'teacher_id']);

        $subjects = Subject::query()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'sections' => $sections,
            'subjects' => $subjects,
            'adviser_grade_level' => $gradeLevel,
            'adviser_section' => $user->section,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $schoolId = $this->getAuthSchoolId();
        $gradeLevel = trim((string) $user->grade_level);

        if ($gradeLevel === '') {
            return response()->json(['message' => 'Your account must have an adviser grade level before adding handled subjects.'], 422);
        }

        $subject = Subject::where('school_id', $schoolId)->find((int) $request->input('subject_id'));
        $section = Section::where('school_id', $schoolId)
            ->where('grade_level', $gradeLevel)
            ->find((int) $request->input('section_id'));

        if (! $subject || ! $section) {
            return response()->json(['message' => 'Select a valid subject and same-grade section.'], 422);
        }

        if ($user->section && (string) $section->name === (string) $user->section) {
            return response()->json(['message' => 'Choose another section. Your advisory class is already available in the workspace.'], 422);
        }

        $handled = TeacherSubjectSection::firstOrCreate(
            [
                'teacher_id' => $user->id,
                'subject_id' => $subject->id,
                'section_id' => $section->id,
            ],
            [
                'school_id' => $schoolId,
                'grade_level' => $section->grade_level,
            ]
        );

        $handled->load(['subject:id,name', 'section:id,name,grade_level']);

        return response()->json([
            'message' => 'Handled subject added.',
            'data' => $this->handledToArray($handled),
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $handled = TeacherSubjectSection::where('school_id', $this->getAuthSchoolId())
            ->where('teacher_id', $request->user()->id)
            ->find($id);

        if (! $handled) {
            return response()->json(['message' => 'Handled subject not found.'], 404);
        }

        $handled->delete();

        return response()->json(['message' => 'Handled subject removed.']);
    }

    private function handledToArray(TeacherSubjectSection $handled): array
    {
        return [
            'id' => $handled->id,
            'subject_id' => $handled->subject_id,
            'subject_name' => $handled->subject?->name,
            'section_id' => $handled->section_id,
            'section_name' => $handled->section?->name,
            'grade_level' => $handled->grade_level ?: $handled->section?->grade_level,
        ];
    }
}
