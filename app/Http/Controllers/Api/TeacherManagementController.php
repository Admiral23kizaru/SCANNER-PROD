<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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

        $teachers = User::where('role_id', 2)
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        return response()->json($teachers);
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
            'employee_id' => ['required', 'string', 'max:255', 'unique:teachers,employee_id'],
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

        $teacher = Teacher::create([
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'email'       => $email,
            'password'    => $request->password,
            'employee_id' => $request->employee_id,
            'school_name' => $schoolName,
            'school_id'   => $schoolId,
            'job_title'   => $request->input('job_title'),
        ]);

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
            'employee_id' => ['sometimes', 'required', 'string', 'max:255', 'unique:teachers,employee_id,' . $teacher->id],
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
