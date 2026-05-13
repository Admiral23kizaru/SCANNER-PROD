<?php

namespace App\Http\Controllers\Api;

use App\Models\Ehris\EhrisUser;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * TeacherManagementController — Admin CRUD for teacher accounts.
 *
 * Maintains synchronisation between the `teachers` table (profile data)
 * and the `users` table (login credentials).
 */
class TeacherManagementController extends BaseController
{
    /* ====================================================================== */
    /*  Read                                                                   */
    /* ====================================================================== */

    /**
     * PURPOSE: List teacher accounts scoped to the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() to prevent null-school wildcard listing across all schools.
     * LIMITATION: Requires a valid school assignment on the authenticated account.
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $school = School::find($schoolId);

        if (!$school) {
            return response()->json(['message' => 'School not found.'], 404);
        }

        $query = $this->schoolScopedTeacherQuery($school)
            ->orderBy('last_name')
            ->orderBy('first_name');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('employee_id', 'like', $term);
            });
        }

        $perPage = max(5, min(100, (int) $request->input('per_page', 50)));
        $page = max(1, (int) $request->input('page', 1));

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);
        $items = $paginated->getCollection()
            ->map(fn (Teacher $teacher) => $this->teacherToArray($teacher))
            ->values();

        return response()->json([
            'data' => $items,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    /**
     * Preview active EHRIS teachers for the authenticated admin's school.
     */
    public function ehris(Request $request): JsonResponse
    {
        $school = School::find($this->getAuthSchoolId());

        if (!$school || !$school->deped_school_id) {
            return response()->json([
                'message' => 'School is not linked to a DepEd School ID.',
                'data' => [],
            ], 422);
        }

        $search = trim((string) $request->query('search', ''));

        $query = $this->ehrisTeacherQueryForSchool($school);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', '%' . $search . '%')
                    ->orWhere('lastname', 'like', '%' . $search . '%')
                    ->orWhere('hrId', 'like', '%' . $search . '%')
                    ->orWhere('userId', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $rows = $query
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->limit(300)
            ->get();

        $employeeIds = [];
        foreach ($rows as $row) {
            $employeeIds[] = $this->resolveEhrisEmployeeId($row);
        }

        $employeeIds = array_values(array_unique(array_filter($employeeIds)));

        $existing = [];
        if (!empty($employeeIds)) {
            $existing = $this->schoolScopedTeacherQuery($school)
                ->whereIn('employee_id', $employeeIds)
                ->pluck('employee_id')
                ->all();
        }

        $existingMap = array_fill_keys($existing, true);

        $data = $rows->map(function (EhrisUser $row) use ($existingMap) {
            $employeeId = $this->resolveEhrisEmployeeId($row);
            return [
                'ehris_user_id' => (string) $row->userId,
                'employee_id' => $employeeId,
                'name' => $this->resolveEhrisFullName($row),
                'email' => (string) ($row->email ?? ''),
                'job_title' => $row->job_title ?? null,
                'department_id' => (string) ($row->department_id ?? ''),
                'is_synced' => isset($existingMap[$employeeId]),
            ];
        })->values();

        return response()->json([
            'message' => 'EHRIS teacher list loaded.',
            'deped_school_id' => (string) $school->deped_school_id,
            'data' => $data,
        ]);
    }

    /**
     * Sync active EHRIS teachers into local teachers + users for this school.
     */
    public function syncEhris(Request $request): JsonResponse
    {
        $school = School::find($this->getAuthSchoolId());

        if (!$school || !$school->deped_school_id) {
            return response()->json([
                'message' => 'School is not linked to a DepEd School ID.',
            ], 422);
        }

        $validated = $request->validate([
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['string', 'max:255'],
        ]);

        $targetIds = collect($validated['employee_ids'] ?? [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values();

        $query = $this->ehrisTeacherQueryForSchool($school);

        $rows = $query->get();

        if ($targetIds->isNotEmpty()) {
            $targetMap = array_fill_keys($targetIds->all(), true);
            $rows = $rows->filter(function (EhrisUser $row) use ($targetMap) {
                $employeeId = $this->resolveEhrisEmployeeId($row);
                return isset($targetMap[$employeeId]);
            })->values();
        }

        $teacherRole = Role::where('name', 'Teacher')->first();
        if (!$teacherRole) {
            return response()->json([
                'message' => 'Teacher role not found.',
            ], 500);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $usersCreated = 0;
        $usersUpdated = 0;

        foreach ($rows as $ehrisUser) {
            $employeeId = $this->resolveEhrisEmployeeId($ehrisUser);
            if ($employeeId === '') {
                $skipped++;

                continue;
            }

            $email = $this->resolveEhrisEmail($ehrisUser, $employeeId);
            [$firstName, $lastName] = $this->resolveEhrisNameParts($ehrisUser);
            $displayName = trim($firstName . ' ' . $lastName);

            $teacher = $this->schoolScopedTeacherQuery($school)
                ->where('employee_id', $employeeId)
                ->first();

            if (!$teacher) {
                $teacher = $this->schoolScopedTeacherQuery($school)
                    ->where('email', $email)
                    ->first();
            }

            if ($teacher) {
                $teacher->first_name = $firstName;
                $teacher->last_name = $lastName;
                $teacher->email = $email;
                $teacher->employee_id = $employeeId;
                if ($this->teacherTableHasColumn('school_name')) {
                    $teacher->school_name = $school->name;
                }
                if ($this->teacherTableHasColumn('school_id')) {
                    $teacher->school_id = $school->id;
                }
                $teacher->job_title = $ehrisUser->job_title ?? null;
                $teacher->designation = 'Teacher';
                $teacher->save();
                $updated++;
            } else {
                Teacher::create(array_merge([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => Str::random(64),
                    'designation' => 'Teacher',
                    'employee_id' => $employeeId,
                    'job_title' => $ehrisUser->job_title ?? null,
                ], $this->teacherSchoolAttributes($school)));
                $created++;
            }

            $existingUser = User::withTrashed()->where('email', $email)->first();
            if ($existingUser && $existingUser->trashed()) {
                $existingUser->restore();
            }

            $hadUser = User::withTrashed()->where('email', $email)->exists();

            $userPayload = [
                'role_id' => $teacherRole->id,
                'name' => $displayName,
                'email' => $email,
                'employee_id' => $employeeId,
                'school_id' => $school->id,
                'school_name' => $school->name,
                'job_title' => $ehrisUser->job_title ?? null,
                'status' => 'active',
            ];

            if (!$hadUser) {
                $userPayload['password'] = Str::random(64);
            }

            User::withTrashed()->updateOrCreate(
                ['email' => $email],
                $userPayload
            );

            if ($hadUser) {
                $usersUpdated++;
            } else {
                $usersCreated++;
            }
        }

        $synced = $created + $updated;

        return response()->json([
            'message' => 'EHRIS teacher sync completed.',
            'synced_count' => $synced,
            'created_count' => $created,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'users_created_count' => $usersCreated,
            'users_updated_count' => $usersUpdated,
        ]);
    }

    /* ====================================================================== */
    /*  Write                                                                  */
    /* ====================================================================== */

    /**
     * PURPOSE: Create a teacher account and linked user within the authenticated admin's school.
     * FIX: Enforces school scope with getAuthSchoolId() so creation can never occur without a school context.
     * LIMITATION: Placeholder email scheme remains unchanged and may still require downstream governance.
     */
    public function store(Request $request): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();

        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'max:255'],
            'employee_id' => ['required', 'string', 'max:255', 'unique:tbl_scanup_teachers,employee_id'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'job_title'   => ['nullable', 'string', 'max:50'],
            'grade_level' => ['nullable', 'string', 'max:20'],
            'section'     => ['nullable', 'string', 'max:50'],
        ], [
            'employee_id.unique' => 'A teacher with this employee number already exists.',
            'password.min'       => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        // Split full name into first/last
        [$firstName, $lastName] = array_pad(explode(' ', $request->name, 2), 2, '');

        // Generate internal placeholder email
        $email = strtolower(str_replace(' ', '', $request->employee_id)) . '@deped.local';
        
        // Auto-detect school name fallback from the authenticated admin user
        $schoolName = $request->input('school_name') ?: $request->user()->school_name;

        $teacherPayload = [
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'email'       => $email,
            'password'    => $request->password,
            'employee_id' => $request->employee_id,
            'job_title'   => $request->input('job_title'),
        ];

        if ($this->teacherTableHasColumn('school_name')) {
            $teacherPayload['school_name'] = $schoolName;
        }

        if ($this->teacherTableHasColumn('school_id')) {
            $teacherPayload['school_id'] = $schoolId;
        }

        $teacher = Teacher::create($teacherPayload);

        // Sync with the users table so the teacher can log in
        $teacherRole = Role::where('name', 'Teacher')->first();
        if ($teacherRole) {
            User::updateOrCreate(['email' => $email], [
                'role_id'     => $teacherRole->id,
                'name'        => $request->name,
                'password'    => $request->password,
                'employee_id' => $request->employee_id,
                'school_name' => $schoolName,
                'school_id'   => $schoolId,
                'job_title'   => $request->input('job_title'),
                'grade_level' => $request->input('grade_level'),
                'section'     => $request->input('section'),
            ]);
        }

        return response()->json(['message' => 'Teacher account created.', 'teacher' => $this->teacherToArray($teacher)], 201);
    }

    /**
     * PURPOSE: Update a teacher profile only when the target belongs to the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() for strict ownership checks before updates.
     * LIMITATION: Ownership is verified via linked user email mapping as currently implemented.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $teacher = Teacher::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        $linkedUser = \App\Models\User::where(
            'email', $teacher->email
        )->first();
        if (!$linkedUser || $linkedUser->school_id !== $schoolId) {
            return response()->json([
                'message' => 'Forbidden.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'employee_id' => ['sometimes', 'required', 'string', 'max:255', 'unique:tbl_scanup_teachers,employee_id,' . $teacher->id],
            'password'    => ['nullable', 'string', 'min:8', 'confirmed'],
            'school_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_title'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'grade_level' => ['sometimes', 'nullable', 'string', 'max:20'],
            'section'     => ['sometimes', 'nullable', 'string', 'max:50'],
        ], [
            'employee_id.unique' => 'A teacher with this employee number already exists.',
            'password.min'       => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            [$teacher->first_name, $teacher->last_name] = array_pad(explode(' ', $request->name, 2), 2, '');
        }
        if ($request->has('employee_id')) $teacher->employee_id = $request->employee_id;
        if ($request->has('school_name'))  $teacher->school_name = $request->input('school_name');
        if ($request->has('job_title'))    $teacher->job_title = $request->input('job_title');
        if ($request->filled('password'))  $teacher->password = $request->password;
        $teacher->save();

        // Propagate changes to the users table
        $user = User::where('email', $teacher->email)->first();
        if ($user) {
            if ($request->has('name'))        $user->name = $request->name;
            if ($request->has('employee_id')) $user->employee_id = $request->employee_id;
            if ($request->has('school_name')) $user->school_name = $request->input('school_name');
            if ($request->has('job_title'))   $user->job_title = $request->input('job_title');
            if ($request->has('grade_level')) $user->grade_level = $request->input('grade_level');
            if ($request->has('section'))     $user->section = $request->input('section');
            if ($request->filled('password')) $user->password = $request->password;
            $user->save();
        }

        return response()->json(['message' => 'Teacher updated.', 'teacher' => $this->teacherToArray($teacher)]);
    }

    /**
     * PURPOSE: Delete a teacher account scoped to the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() to block null-school wildcard deletes.
     * LIMITATION: Still prevents deletion when linked user has created student records.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $teacher = Teacher::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        $linkedUser = \App\Models\User::where(
            'email', $teacher->email
        )->first();
        if (!$linkedUser || $linkedUser->school_id !== $schoolId) {
            return response()->json([
                'message' => 'Forbidden.'
            ], 403);
        }

        $user = User::where('email', $teacher->email)->first();

        if ($user && Student::where('created_by', $user->id)->exists()) {
            return response()->json([
                'message' => 'Cannot delete this teacher because they created student records. Reassign those students first.',
            ], 422);
        }

        $user?->delete();
        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted.']);
    }

    /* ====================================================================== */
    /*  Photo upload                                                            */
    /* ====================================================================== */

    /**
     * PURPOSE: Upload a teacher profile photo only for teachers owned by the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() for strict school ownership enforcement.
     * LIMITATION: Uses linked user email to validate school ownership as in existing design.
     */
    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $teacher = Teacher::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        $linkedUser = \App\Models\User::where(
            'email', $teacher->email
        )->first();
        if (!$linkedUser || $linkedUser->school_id !== $schoolId) {
            return response()->json([
                'message' => 'Forbidden.'
            ], 403);
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $path = $this->storePublicStorageImage(
            $request->file('photo'),
            'teachers',
            $teacher->profile_photo
        );
        $teacher->update(['profile_photo' => $path]);

        // Sync photo to users table
        User::where('email', $teacher->email)->update(['profile_photo' => $path]);

        return response()->json(['message' => 'Profile photo updated.', 'profile_photo' => $path]);
    }

    /* ====================================================================== */
    /*  Export                                                                 */
    /* ====================================================================== */

    /**
     * PURPOSE: Export teachers as UTF-8 CSV scoped to the authenticated admin's school.
     * FIX: Uses getAuthSchoolId() to remove null-school wildcard export behavior.
     * LIMITATION: Export scope remains limited to teacher role and current school context.
     */
    public function export(Request $request): StreamedResponse
    {
        $schoolId = $this->getAuthSchoolId();
        $teacherRole = Role::where('name', 'Teacher')->firstOrFail();

        $response = new StreamedResponse(function () use ($teacherRole, $schoolId) {
            if (ob_get_length()) {
                ob_end_clean();
            }

            $handle = fopen('php://output', 'w');
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel

            fputcsv($handle, ['ID', 'Name', 'Employee ID', 'Job Title', 'School Name', 'Created At']);

            User::where('role_id', $teacherRole->id)
                ->where('school_id', $schoolId)
                ->orderBy('name')
                ->chunk(100, function ($users) use ($handle) {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->name,
                            $user->employee_id,
                            $user->job_title,
                            $user->school_name,
                            $user->created_at?->toIso8601String(),
                        ]);
                    }
                });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="teachers_export.csv"');

        return $response;
    }

    /* ====================================================================== */
    /*  Private helpers                                                        */
    /* ====================================================================== */

    /**
     * ehrisTeacherQueryForSchool
     * PURPOSE: Base EHRIS query for active Teacher-role accounts in one DepEd school.
     * WHY: Centralizes school isolation (department_id + role) for preview and sync.
     *
     * @param School $school Authenticated admin school (must have deped_school_id).
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function ehrisTeacherQueryForSchool(School $school)
    {
        $deped = trim((string) ($school->deped_school_id ?? ''));

        $query = EhrisUser::active()
            ->where('role', 'Teacher');

        if ($deped !== '') {
            $query->where(function ($inner) use ($deped) {
                $inner->where('department_id', $deped);
                if (ctype_digit($deped)) {
                    $inner->orWhere('department_id', (int) $deped);
                }
            });
        }

        return $query;
    }

    /**
     * Build a local teachers query scoped to the authenticated school.
     *
     * Some installed databases predate the `teachers.school_id` column and only
     * have `school_name`, so EHRIS preview/sync must support both shapes.
     */
    private function schoolScopedTeacherQuery(School $school)
    {
        $query = Teacher::query();

        if ($this->teacherTableHasColumn('school_id')) {
            return $query->where('school_id', $school->id);
        }

        if ($this->teacherTableHasColumn('school_name')) {
            return $query->where('school_name', $school->name);
        }

        return $query->whereRaw('1 = 0');
    }

    private function teacherSchoolAttributes(School $school): array
    {
        $attributes = [];

        if ($this->teacherTableHasColumn('school_id')) {
            $attributes['school_id'] = $school->id;
        }

        if ($this->teacherTableHasColumn('school_name')) {
            $attributes['school_name'] = $school->name;
        }

        return $attributes;
    }

    private function teacherTableHasColumn(string $column): bool
    {
        return Schema::hasColumn((new Teacher())->getTable(), $column);
    }

    private function resolveEhrisEmployeeId(EhrisUser $ehrisUser): string
    {
        return trim((string) ($ehrisUser->hrId ?: $ehrisUser->userId));
    }

    private function resolveEhrisFullName(EhrisUser $ehrisUser): string
    {
        $full = trim((string) $ehrisUser->full_name);
        if ($full !== '') {
            return $full;
        }

        $first = trim((string) ($ehrisUser->firstname ?? ''));
        $last = trim((string) ($ehrisUser->lastname ?? ''));
        $name = trim($first . ' ' . $last);
        return $name !== '' ? $name : (string) $ehrisUser->userId;
    }

    private function resolveEhrisNameParts(EhrisUser $ehrisUser): array
    {
        $first = trim((string) ($ehrisUser->firstname ?? ''));
        $last = trim((string) ($ehrisUser->lastname ?? ''));

        if ($first !== '' || $last !== '') {
            return [$first !== '' ? $first : 'Teacher', $last];
        }

        $full = $this->resolveEhrisFullName($ehrisUser);
        [$fallbackFirst, $fallbackLast] = array_pad(explode(' ', $full, 2), 2, '');
        return [trim($fallbackFirst) !== '' ? trim($fallbackFirst) : 'Teacher', trim($fallbackLast)];
    }

    private function resolveEhrisEmail(EhrisUser $ehrisUser, string $employeeId): string
    {
        $raw = trim((string) ($ehrisUser->email ?? ''));
        if ($raw !== '') {
            return strtolower($raw);
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $employeeId) ?: 'teacher');
        return $slug . '@deped.local';
    }

    /** Serialize a Teacher model into the standard API response shape. */
    /**
     * Action: Implementing Section-based Teacher Assignment and Gender-specific Dashboard Analytics.
     * Serialize a Teacher model into the standard API response shape.
     */
    private function teacherToArray($teacher): array
    {
        // Also fetch grade_level/section from the linked users record
        $user = User::where('email', $teacher->email)->first();

        $name = trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? ''));
        if (empty($name) && isset($teacher->name)) {
            $name = $teacher->name;
        }

        return [
            'id'            => $teacher->id,
            'user_id'       => $user?->id,
            'name'          => $name,
            'employee_id'   => $teacher->employee_id,
            'school_name'   => $teacher->school_name,
            'job_title'     => $teacher->job_title,
            'grade_level'   => $user?->grade_level,
            'section'       => $user?->section,
            'profile_photo' => $teacher->profile_photo
                ? ltrim(str_replace('storage/', '', $teacher->profile_photo), '/')
                : null,
            'created_at'    => $teacher->created_at?->toIso8601String(),
        ];
    }

    /**
     * Store an uploaded image directly under public/storage/<dir> and return a relative path (<dir>/<filename>).
     */
    private function storePublicStorageImage(\Illuminate\Http\UploadedFile $file, string $dir, ?string $previousRelativePath = null): string
    {
        $base = public_path('storage' . DIRECTORY_SEPARATOR . $dir);
        if (!File::exists($base)) {
            File::makeDirectory($base, 0755, true);
        }

        if ($previousRelativePath) {
            $prevClean = ltrim(preg_replace('#^(public/|storage/|/storage/)#', '', $previousRelativePath) ?? $previousRelativePath, '/');
            $prevAbs = public_path('storage' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $prevClean));
            if (File::exists($prevAbs)) {
                @File::delete($prevAbs);
            }
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid()->toString() . '.' . $ext;
        $file->move($base, $filename);

        return $dir . '/' . $filename;
    }
}
