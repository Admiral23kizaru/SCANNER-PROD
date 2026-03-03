<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
                'grade' => $s->grade,
                'section' => $s->section,
                'middle_name' => $s->middle_name,
                'guardian' => $s->guardian,
                'parent_email' => $s->parent_email,
                'contact_number' => $s->contact_number,
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

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'student_number' => ['required', 'string', 'max:64', 'unique:students,student_number'],
            'grade_section' => ['nullable', 'string', 'max:64'],
            'grade' => ['nullable', 'string', 'max:32'],
            'section' => ['nullable', 'string', 'max:32'],
            'guardian' => ['nullable', 'string', 'max:255'],
            'parent_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:64'],
        ], [
            'student_number.unique' => 'LRN already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $gradeSection = $request->grade_section ?: ($request->grade && $request->section
            ? $request->grade . '-' . $request->section
            : ($request->grade ?? null));

        $student = Student::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name ?: null,
            'student_number' => $request->student_number,
            'grade_section' => $gradeSection,
            'grade' => $request->grade ?: null,
            'section' => $request->section ?: null,
            'guardian' => $request->guardian ?: null,
            'parent_email' => $request->parent_email ?: null,
            'contact_number' => $request->contact_number ?: null,
            // Admin-created master list entries: created_by/teacher_id can remain null in this build
        ]);

        return response()->json([
            'message' => 'Student created.',
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'middle_name' => $student->middle_name,
                'full_name' => trim($student->first_name . ' ' . $student->last_name),
                'grade_section' => $student->grade_section ?? '—',
                'grade' => $student->grade,
                'section' => $student->section,
                'guardian' => $student->guardian,
                'parent_email' => $student->parent_email,
                'contact_number' => $student->contact_number,
                'created_at' => $student->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'student_number' => ['sometimes', 'required', 'string', 'max:64', 'unique:students,student_number,' . $id],
            'grade_section' => ['nullable', 'string', 'max:64'],
            'grade' => ['nullable', 'string', 'max:32'],
            'section' => ['nullable', 'string', 'max:32'],
            'guardian' => ['nullable', 'string', 'max:255'],
            'parent_email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:64'],
        ], [
            'student_number.unique' => 'LRN already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only([
            'first_name',
            'last_name',
            'middle_name',
            'student_number',
            'grade_section',
            'grade',
            'section',
            'guardian',
            'parent_email',
            'contact_number',
        ]);

        $gradeSection = $request->grade_section ?: ($request->grade && $request->section
            ? $request->grade . '-' . $request->section
            : ($request->grade ?? null));
        if ($request->has('grade') || $request->has('section') || $request->has('grade_section')) {
            $data['grade_section'] = $gradeSection;
        }

        $student->fill($data);
        $student->save();

        return response()->json([
            'message' => 'Student updated.',
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'middle_name' => $student->middle_name,
                'full_name' => trim($student->first_name . ' ' . $student->last_name),
                'grade_section' => $student->grade_section ?? '—',
                'grade' => $student->grade,
                'section' => $student->section,
                'guardian' => $student->guardian,
                'parent_email' => $student->parent_email,
                'contact_number' => $student->contact_number,
                'created_at' => $student->created_at?->toIso8601String(),
            ],
        ], 200);
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
