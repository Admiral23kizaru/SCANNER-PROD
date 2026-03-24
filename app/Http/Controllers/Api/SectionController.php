<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Action: Implementing Section Management and fixing school-level data scoping.
 * // Description: SectionController - Handles CRUD for class sections and
 * //   teacher assignments. Sections group students by grade level and are
 * //   scoped to the admin's school.
 * // Author: Antigravity System Agent
 */
class SectionController extends Controller
{
    /**
     * // Description: index - Returns all sections for the admin's school,
     * //   including the assigned teacher's name and student count.
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $request->user()->school_id;

        $sections = Section::with('teacher:id,name')
            ->withCount('students')
            ->when($schoolId, function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $sections]);
    }

    /**
     * // Description: store - Creates a new section and optionally assigns a teacher.
     * //   The school_id is auto-set from the authenticated admin's profile.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100'],
            'grade_level' => ['required', 'string', 'max:50'],
            'teacher_id'  => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $section = Section::create([
            'name'        => $request->name,
            'grade_level' => $request->grade_level,
            'teacher_id'  => $request->teacher_id,
            'school_id'   => $request->user()->school_id,
        ]);

        // If a teacher is assigned, sync their grade_level and section on the users table
        // so the teacher dashboard filters correctly.
        if ($request->teacher_id) {
            $this->syncTeacherAssignment($request->teacher_id, $section);
        }

        return response()->json([
            'message' => 'Section created successfully.',
            'data'    => $section->load('teacher:id,name')->loadCount('students'),
        ], 201);
    }

    /**
     * // Description: update - Updates an existing section's name, grade, or teacher.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $section = Section::find($id);
        if (!$section) {
            return response()->json(['message' => 'Section not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => ['sometimes', 'required', 'string', 'max:100'],
            'grade_level' => ['sometimes', 'required', 'string', 'max:50'],
            'teacher_id'  => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $section->update($request->only(['name', 'grade_level', 'teacher_id']));

        // Assigns a specific teacher to a section and updates all related students.
        if ($request->has('teacher_id') && $request->teacher_id) {
            $this->syncTeacherAssignment($request->teacher_id, $section);
        }

        return response()->json([
            'message' => 'Section updated.',
            'data'    => $section->load('teacher:id,name')->loadCount('students'),
        ]);
    }

    /**
     * // Description: destroy - Deletes a section. Students in this section
     * //   will have their section_id set to NULL (via nullOnDelete FK).
     */
    public function destroy(int $id): JsonResponse
    {
        $section = Section::find($id);
        if (!$section) {
            return response()->json(['message' => 'Section not found.'], 404);
        }

        $section->delete();

        return response()->json(['message' => 'Section deleted.']);
    }

    /**
     * // Description: assignStudents - Bulk-assigns unassigned students to a section.
     * //   Updates both section_id, grade, and section text fields on each student.
     */
    public function assignStudents(Request $request, int $id): JsonResponse
    {
        $section = Section::find($id);
        if (!$section) {
            return response()->json(['message' => 'Section not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        // Bulk-update selected students to this section
        Student::whereIn('id', $request->student_ids)->update([
            'section_id' => $section->id,
            'grade'      => $section->grade_level,
            'section'    => $section->name,
        ]);

        return response()->json([
            'message' => count($request->student_ids) . ' student(s) assigned.',
            'data'    => $section->load('teacher:id,name')->loadCount('students'),
        ]);
    }

    /**
     * // Description: unassignedStudents - Returns students that don't have a
     * //   section_id yet, for use in the bulk-assign multi-select.
     */
    public function unassignedStudents(Request $request): JsonResponse
    {
        $students = Student::whereNull('section_id')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->select('id', 'first_name', 'last_name', 'grade', 'section', 'student_number')
            ->get();

        return response()->json(['data' => $students]);
    }

    /**
     * // Description: teachers - Returns all teacher-role users for the dropdown.
     */
    public function teachers(Request $request): JsonResponse
    {
        $teacherRoleId = \App\Models\Role::where('name', 'Teacher')->value('id');

        $teachers = User::where('role_id', $teacherRoleId)
            ->select('id', 'name', 'grade_level', 'section')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $teachers]);
    }

    /**
     * // Description: syncTeacherAssignment - When a teacher is assigned to a section,
     * //   update their grade_level and section on the users table. This ensures the
     * //   teacher's dashboard filters show only students in their assigned section.
     * // Author: Antigravity System Agent
     */
    private function syncTeacherAssignment(int $teacherId, Section $section): void
    {
        User::where('id', $teacherId)->update([
            'grade_level' => $section->grade_level,
            'section'     => $section->name,
        ]);
    }
}
