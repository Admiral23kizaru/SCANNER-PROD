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
        $schoolId = $this->schoolScope($request);
        $student = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('subjects:id,name')
            ->find($studentId);

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        return response()->json([
            'data' => $student->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
        ]);
    }

    public function sync(Request $request, int $studentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_ids' => ['required', 'array'],
            'subject_ids.*' => ['integer'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $schoolId = $this->schoolScope($request);
        $student = Student::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->find($studentId);

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

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
}

