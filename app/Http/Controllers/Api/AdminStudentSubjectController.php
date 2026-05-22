<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminStudentSubjectController extends Controller
{
    private function schoolScope(Request $request): ?int
    {
        return $request->user()?->school_id;
    }

    public function show(Request $request, int $studentId): JsonResponse
    {
        $student = $this->findAccessibleStudent($request, $studentId);

        if (! $student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $student->load('subjects:id,name');

        return response()->json([
            'data' => $student->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
        ]);
    }

    public function sync(Request $request, int $studentId): JsonResponse
    {
        if ($request->user()?->role?->name === 'Subject Teacher') {
            return response()->json(['message' => 'Subject Teachers can view learner subjects but cannot edit them.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'subject_ids' => ['required', 'array'],
            'subject_ids.*' => ['integer'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $student = $this->findAccessibleStudent($request, $studentId);

        if (! $student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $schoolId = $this->schoolScope($request);
        $subjectIds = array_values(array_unique(array_map('intval', $request->input('subject_ids', []))));

        // Only allow subjects within the admin's school scope (or global if super-admin).
        $validSubjectIds = Subject::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('id', $subjectIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $student->subjects()->sync($validSubjectIds);

        return response()->json(['message' => 'Student subjects updated.', 'data' => $validSubjectIds]);
    }

    private function findAccessibleStudent(Request $request, int $studentId): ?Student
    {
        $schoolId = $this->schoolScope($request);
        $query = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        $user = $request->user();
        if (in_array($user?->role?->name, ['Teacher', 'Adviser', 'Subject Teacher'], true)) {
            if ($user->grade_level && $user->section) {
                $query->where('grade', $user->grade_level)->where('section', $user->section);
            } elseif ($user->role?->name !== 'Subject Teacher') {
                $query->where(function ($q) use ($user) {
                    $q->where('teacher_id', $user->id)->orWhere('created_by', $user->id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->find($studentId);
    }
}
