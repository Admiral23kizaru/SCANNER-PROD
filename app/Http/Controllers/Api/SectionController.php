<?php

namespace App\Http\Controllers\Api;

use App\Models\Section;
use App\Models\Student;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * Action: Implementing Section Management and fixing school-level data scoping.
 * // Description: SectionController - Handles CRUD for class sections and
 * //   teacher assignments. Sections group students by grade level and are
 * //   scoped to the admin's school.
 * // Author: Antigravity System Agent
 */
class SectionController extends BaseController
{
    /**
     * PURPOSE: Resolve a section only within the authenticated school scope.
     * FIX: Requires explicit school_id and removes nullable wildcard section lookups.
     * LIMITATION: Returns 404 for out-of-scope IDs to avoid disclosing existence.
     */
    private function findSectionOrFail(int $id, int $schoolId): Section
    {
        $section = Section::where('id', $id)
            ->where('school_id', $schoolId)
            ->first();

        if (!$section) {
            abort(404, 'Section not found.');
        }

        return $section;
    }

    /**
     * PURPOSE: Return all sections for the authenticated admin's school, with teacher and student count.
     * FIX: Uses getAuthSchoolId() for strict school scoping (no null wildcard behavior).
     * LIMITATION: Requires valid school assignment on authenticated account.
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $cacheKey = 'admin.sections.index.school.' . $schoolId;
        $data = Cache::remember($cacheKey, 120, function () use ($schoolId) {
            return Section::with('teacher:id,name')
                ->withCount('students')
                ->where('school_id', $schoolId)
                ->orderBy('grade_level')
                ->orderBy('name')
                ->get()
                ->toArray();
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Return school section options used by teacher learner forms.
     */
    public function formOptions(Request $request): JsonResponse
    {
        $schoolId = $request->user()?->school_id;
        if (! $schoolId) {
            return response()->json([
                'message' => 'Account is not assigned to a school.',
                'data' => [],
            ], 422);
        }

        $user = $request->user();
        $isTeacherRole = in_array($user?->role?->name, ['Teacher', 'Adviser', 'Subject Teacher'], true);

        $query = Section::query()
            ->where('school_id', $schoolId)
            ->when($isTeacherRole && $user->grade_level, fn ($q) => $q->where('grade_level', $user->grade_level))
            ->orderBy('grade_level')
            ->orderBy('name');

        $sections = $query->get(['id', 'name', 'grade_level']);

        if ($sections->isEmpty()) {
            $fallback = Student::query()
                ->where('school_id', $schoolId)
                ->whereNotNull('section')
                ->where('section', '!=', '')
                ->when($isTeacherRole && $user->grade_level, fn ($q) => $q->where('grade', $user->grade_level))
                ->select('grade', 'section')
                ->distinct()
                ->orderBy('grade')
                ->orderBy('section')
                ->get()
                ->map(fn (Student $student) => [
                    'id' => null,
                    'name' => (string) $student->section,
                    'grade_level' => $student->grade,
                ])
                ->values();

            return response()->json(['data' => $fallback]);
        }

        return response()->json(['data' => $sections]);
    }

    /**
     * PURPOSE: Create a section for the authenticated admin's school and optionally assign a teacher.
     * FIX: Enforces school ownership with getAuthSchoolId() before teacher validation and section creation.
     * LIMITATION: Teacher assignment still depends on user record school_id integrity.
     */
    public function store(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:100'],
            'grade_level' => ['required', 'string', 'max:50'],
            'teacher_id'  => ['nullable', 'integer', 'exists:tbl_scanup_users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('teacher_id')) {
            $teacherUser = $this->assignableTeacherQuery($schoolId)
                ->where('id', $request->teacher_id)
                ->first();

            if (!$teacherUser) {
                return response()->json([
                    'message' => 'Selected teacher does not belong to your school.'
                ], 422);
            }
        }

        if ($request->filled('teacher_id') && $this->teacherHasOtherSection($schoolId, (int) $request->teacher_id)) {
            return response()->json([
                'message' => 'Selected teacher is already assigned as adviser to another section.'
            ], 422);
        }

        $section = Section::create([
            'name'        => $request->name,
            'grade_level' => $request->grade_level,
            'teacher_id'  => $request->teacher_id,
            'school_id'   => $schoolId,
        ]);

        // If a teacher is assigned, sync their grade_level and section on the users table
        // so the teacher dashboard filters correctly.
        if ($request->teacher_id) {
            $this->syncTeacherAssignment($request->teacher_id, $section);
        }

        Cache::forget('admin.sections.index.school.' . $schoolId);

        return response()->json([
            'message' => 'Section created successfully.',
            'data'    => $section->load('teacher:id,name')->loadCount('students'),
        ], 201);
    }

    /**
     * PURPOSE: Update a section within the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() and strict section ownership checks.
     * LIMITATION: Returns 404 for out-of-scope section IDs.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $section = $this->findSectionOrFail($id, $schoolId);
        $previousTeacherId = $section->teacher_id ? (int) $section->teacher_id : null;

        $validator = Validator::make($request->all(), [
            'name'        => ['sometimes', 'required', 'string', 'max:100'],
            'grade_level' => ['sometimes', 'required', 'string', 'max:50'],
            'teacher_id'  => ['nullable', 'integer', 'exists:tbl_scanup_users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        if ($request->filled('teacher_id')) {
            $teacherUser = $this->assignableTeacherQuery($schoolId)
                ->where('id', $request->teacher_id)
                ->first();

            if (!$teacherUser) {
                return response()->json([
                    'message' => 'Selected teacher does not belong to your school.'
                ], 422);
            }
        }

        if ($request->filled('teacher_id') && $this->teacherHasOtherSection($schoolId, (int) $request->teacher_id, $section->id)) {
            return response()->json([
                'message' => 'Selected teacher is already assigned as adviser to another section.'
            ], 422);
        }

        $section->update($request->only(['name', 'grade_level', 'teacher_id']));

        if ($request->has('teacher_id') && $previousTeacherId && (int) $request->teacher_id !== $previousTeacherId) {
            $this->clearTeacherAssignment($previousTeacherId, $schoolId);
        }

        // Assigns a specific teacher to a section and updates all related students.
        if ($request->has('teacher_id') && $request->teacher_id) {
            $this->syncTeacherAssignment($request->teacher_id, $section);
        }

        Cache::forget('admin.sections.index.school.' . $schoolId);

        return response()->json([
            'message' => 'Section updated.',
            'data'    => $section->load('teacher:id,name')->loadCount('students'),
        ]);
    }

    /**
     * PURPOSE: Delete a section within the authenticated admin's school scope.
     * FIX: Uses getAuthSchoolId() for strict school ownership enforcement.
     * LIMITATION: Student unassignment behavior remains delegated to FK nullOnDelete.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $section = $this->findSectionOrFail($id, $schoolId);
        $teacherId = $section->teacher_id ? (int) $section->teacher_id : null;

        $section->delete();

        if ($teacherId) {
            $this->clearTeacherAssignment($teacherId, $schoolId);
        }

        Cache::forget('admin.sections.index.school.' . $schoolId);

        return response()->json(['message' => 'Section deleted.']);
    }

    /**
     * PURPOSE: Bulk-assign students to a section within the authenticated admin's school.
     * FIX: Adds strict pre-check that ALL submitted student IDs belong to the same school before update.
     * LIMITATION: Entire request is rejected when any student ID is out-of-scope.
     */
    public function assignStudents(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $section = $this->findSectionOrFail($id, $schoolId);

        $validator = Validator::make($request->all(), [
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:tbl_scanup_students,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $studentIds = $request->student_ids;
        $validCount = Student::whereIn('id', $studentIds)
            ->where('school_id', $schoolId)
            ->count();

        if ($validCount !== count($studentIds)) {
            return response()->json([
                'message' => 'One or more students do not belong to this school.'
            ], 422);
        }

        // Bulk-update selected students to this section (scoped by school)
        Student::whereIn('id', $studentIds)
            ->where('school_id', $schoolId)
            ->update([
            'section_id' => $section->id,
            'grade'      => $section->grade_level,
            'section'    => $section->name,
        ]);

        Cache::forget('admin.sections.index.school.' . $schoolId);

        return response()->json([
            'message' => count($studentIds) . ' student(s) assigned.',
            'data'    => $section->load('teacher:id,name')->loadCount('students'),
        ]);
    }

    /**
     * PURPOSE: Return students without section assignment in the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() to enforce non-null school scoping.
     * LIMITATION: Only unassigned students (`section_id` null) are returned.
     */
    public function unassignedStudents(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $students = Student::where('school_id', $schoolId)
            ->whereNull('section_id')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->select('id', 'first_name', 'last_name', 'grade', 'section', 'student_number')
            ->get();

        return response()->json(['data' => $students]);
    }

    /**
     * PURPOSE: Return students currently assigned to a section within the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() and section ownership check for strict scope.
     * LIMITATION: Out-of-scope sections return 404.
     */
    public function students(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $section = $this->findSectionOrFail($id, $schoolId);

        $students = Student::where('section_id', $section->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->select('id', 'first_name', 'last_name', 'grade', 'section', 'student_number')
            ->get();

        return response()->json(['data' => $students]);
    }

    /**
     * PURPOSE: Bulk-unassign selected students from a section within the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() to enforce strict school-scoped updates.
     * LIMITATION: Only students currently mapped to the section are updated.
     */
    public function unassignStudents(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $section = $this->findSectionOrFail($id, $schoolId);

        $validator = Validator::make($request->all(), [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:tbl_scanup_students,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        Student::where('section_id', $section->id)
            ->whereIn('id', $request->student_ids)
            ->where('school_id', $schoolId)
            ->update([
                'section_id' => null,
                'grade'      => null,
                'section'    => null,
            ]);

        Cache::forget('admin.sections.index.school.' . $schoolId);

        return response()->json([
            'message' => 'Students unassigned.',
            'data' => [
                'section_id' => $section->id,
            ],
        ]);
    }

    /**
     * PURPOSE: Return synced teacher login users for section assignment in the authenticated school.
     * WHY: EHRIS uses tbl_user.department_id for school assignment, but section assignment
     *      must use ScanUp login IDs from tbl_scanup_users. Fetch EHRIS / Sync All writes
     *      the school scope into tbl_scanup_users and tbl_scanup_teachers; this endpoint
     *      reads only those same-school records.
     * LIMITATION: Teachers must already be synced into ScanUp before they can be assigned.
     */
    public function teachers(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $currentSectionId = (int) $request->query('current_section_id', 0);

        if ($currentSectionId > 0) {
            $this->findSectionOrFail($currentSectionId, $schoolId);
        }

        $assignedTeacherIds = Section::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('teacher_id')
            ->when($currentSectionId > 0, fn ($query) => $query->where('id', '!=', $currentSectionId))
            ->pluck('teacher_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $teachers = $this->assignableTeacherQuery($schoolId)
            ->when($assignedTeacherIds !== [], fn ($query) => $query->whereNotIn('id', $assignedTeacherIds))
            ->select('id', 'name', 'email', 'employee_id', 'grade_level', 'section')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $teachers]);
    }

    /**
     * PURPOSE: Build the single source of truth for teachers assignable to sections.
     * WHY: Prevents the dropdown and submit validation from drifting apart.
     *
     * A teacher is assignable when:
     * - their ScanUp user role is Teacher;
     * - their ScanUp account is active;
     * - their user row belongs to the admin school, or an older synced teacher profile
     *   with the same email belongs to the admin school.
     */
    private function assignableTeacherQuery(int $schoolId)
    {
        $teacherRoleId = Role::where('name', 'Teacher')->value('id');

        if ($teacherRoleId === null) {
            return User::query()->whereRaw('1 = 0');
        }

        $teacherTable = (new Teacher())->getTable();
        $userTable = (new User())->getTable();

        return User::query()
            ->where('role_id', $teacherRoleId)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', 'active');
            })
            ->where(function ($query) use ($schoolId, $teacherTable, $userTable) {
                $query->where($userTable . '.school_id', $schoolId)
                    ->orWhereExists(function ($subquery) use ($schoolId, $teacherTable, $userTable) {
                        $subquery->selectRaw('1')
                            ->from($teacherTable)
                            ->whereColumn($teacherTable . '.email', $userTable . '.email')
                            ->where($teacherTable . '.school_id', $schoolId);
                    });
            });
    }

    /**
     * // Description: syncTeacherAssignment - When a teacher is assigned to a section,
     * //   update their grade_level and section on the users table. This ensures the
     * //   teacher's dashboard filters show only students in their assigned section.
     * // Author: Antigravity System Agent
     */
    private function syncTeacherAssignment(int $teacherId, Section $section): void
    {
        $schoolId = (int) $section->school_id;

        $this->assignableTeacherQuery($schoolId)
            ->where('id', $teacherId)
            ->update([
            'grade_level' => $section->grade_level,
            'section'     => $section->name,
        ]);
    }

    private function teacherHasOtherSection(int $schoolId, int $teacherId, ?int $exceptSectionId = null): bool
    {
        return Section::query()
            ->where('school_id', $schoolId)
            ->where('teacher_id', $teacherId)
            ->when($exceptSectionId, fn ($query) => $query->where('id', '!=', $exceptSectionId))
            ->exists();
    }

    private function clearTeacherAssignment(int $teacherId, int $schoolId): void
    {
        if ($this->teacherHasOtherSection($schoolId, $teacherId)) {
            return;
        }

        $this->assignableTeacherQuery($schoolId)
            ->where('id', $teacherId)
            ->update([
                'grade_level' => null,
                'section' => null,
            ]);
    }
}
