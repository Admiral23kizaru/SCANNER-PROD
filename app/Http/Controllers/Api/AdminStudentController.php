<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Student::query()->orderBy('last_name')->orderBy('first_name');

        $search = $request->input('search');
        if ($search && is_string($search)) {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('student_number', 'like', $term)
                    ->orWhere('grade_section', 'like', $term);
            });
        }

        $perPage = max(5, min(100, (int) $request->input('per_page', 15)));
        $students = $query->paginate($perPage);

        $items = $students->getCollection()->map(function (Student $s) {
            return [
                'id' => $s->id,
                'student_number' => $s->student_number,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'full_name' => $s->first_name . ' ' . $s->last_name,
                'grade_section' => $s->grade_section ?? '—',
                'created_at' => $s->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
            'current_page' => $students->currentPage(),
            'last_page' => $students->lastPage(),
            'per_page' => $students->perPage(),
            'total' => $students->total(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $student->delete();

        return response()->json(['message' => 'Student and related attendance records deleted.'], 200);
    }
}
