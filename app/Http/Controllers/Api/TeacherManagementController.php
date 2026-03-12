<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherManagementController extends Controller
{
    public function index(): JsonResponse
    {
        $teacherRole = Role::where('name', 'Teacher')->first();
        if (!$teacherRole) {
            return response()->json(['data' => []]);
        }

        $teachers = User::where('role_id', $teacherRole->id)
            ->orderBy('name')
            ->get();

        $data = $teachers->map(function (User $u) {
            return [
                'id'              => $u->id,
                'name'            => $u->name,
                'employee_id' => $u->employee_id,
                'school_name'     => $u->school_name,
                'job_title'       => $u->job_title,
                'profile_photo'   => $u->profile_photo ? '/' . ltrim($u->profile_photo, '/') : null,
                'created_at'      => $u->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'max:255'],
            'employee_id' => ['required', 'string', 'max:255', 'unique:users,employee_id'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'school_name'     => ['nullable', 'string', 'max:255'],
            'job_title'       => ['nullable', 'string', 'max:50'],
        ], [
            'employee_id.unique' => 'A teacher with this employee number already exists.',
            'password.min'           => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $teacherRole = Role::where('name', 'Teacher')->first();
        if (!$teacherRole) {
            return response()->json(['message' => 'Teacher role not found.'], 500);
        }

        // Generate a placeholder email from employee_id to satisfy the unique constraint
        $email = strtolower(str_replace(' ', '', $request->employee_id)) . '@deped.local';

        $user = User::create([
            'role_id'         => $teacherRole->id,
            'name'            => $request->name,
            'email'           => $email,
            'password'        => $request->password,
            'employee_id' => $request->employee_id,
            'school_name'     => $request->input('school_name'),
            'job_title'       => $request->input('job_title'),
        ]);

        return response()->json([
            'message' => 'Teacher account created.',
            'teacher' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'employee_id' => $user->employee_id,
                'school_name'     => $user->school_name,
                'job_title'       => $user->job_title,
                'profile_photo'   => $user->profile_photo ? asset($user->profile_photo) : null,
                'created_at'      => $user->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $teacherRole = Role::where('name', 'Teacher')->first();
        if (!$teacherRole) {
            return response()->json(['message' => 'Teacher role not found.'], 500);
        }

        $teacher = User::where('id', $id)->where('role_id', $teacherRole->id)->first();
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'            => ['sometimes', 'required', 'string', 'max:255'],
            'employee_id' => ['sometimes', 'required', 'string', 'max:255', 'unique:users,employee_id,' . $teacher->id],
            'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
            'school_name'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_title'       => ['sometimes', 'nullable', 'string', 'max:50'],
        ], [
            'employee_id.unique' => 'A teacher with this employee number already exists.',
            'password.min'           => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $teacher->name = $request->name;
        }
        if ($request->has('employee_id')) {
            $teacher->employee_id = $request->employee_id;
        }
        if ($request->has('school_name')) {
            $teacher->school_name = $request->input('school_name');
        }
        if ($request->has('job_title')) {
            $teacher->job_title = $request->input('job_title');
        }
        if ($request->filled('password')) {
            $teacher->password = $request->password;
        }
        $teacher->save();

        return response()->json([
            'message' => 'Teacher updated.',
            'teacher' => [
                'id'              => $teacher->id,
                'name'            => $teacher->name,
                'employee_id' => $teacher->employee_id,
                'school_name'     => $teacher->school_name,
                'job_title'       => $teacher->job_title,
                'profile_photo'   => $teacher->profile_photo ? asset($teacher->profile_photo) : null,
                'created_at'      => $teacher->created_at?->toIso8601String(),
            ],
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $teacherRole = Role::where('name', 'Teacher')->first();
        if (!$teacherRole) {
            return response()->json(['message' => 'Teacher role not found.'], 500);
        }

        $teacher = User::where('id', $id)->where('role_id', $teacherRole->id)->first();
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        // Protect data integrity: students.created_by is RESTRICT on delete.
        $hasCreatedStudents = Student::where('created_by', $teacher->id)->exists();
        if ($hasCreatedStudents) {
            return response()->json([
                'message' => 'Cannot delete this teacher because they created student records. Reassign those students first.',
            ], 422);
        }

        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted.'], 200);
    }

    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $teacherRole = Role::where('name', 'Teacher')->first();
        if (!$teacherRole) {
            return response()->json(['message' => 'Teacher role not found.'], 500);
        }

        $teacher = User::where('id', $id)->where('role_id', $teacherRole->id)->first();
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $file = $validated['photo'];

        $path = $file->storeAs(
            'teachers',
            'teacher_' . $teacher->id . '.' . $file->getClientOriginalExtension(),
            'public'
        );

        $teacher->profile_photo = 'storage/' . $path;
        $teacher->save();

        return response()->json([
            'message'       => 'Profile photo updated.',
            'profile_photo' => '/' . ltrim($teacher->profile_photo, '/'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $teacherRole = Role::where('name', 'Teacher')->first();
        if (!$teacherRole) {
            abort(500, 'Teacher role not found.');
        }

        $response = new StreamedResponse(function () use ($teacherRole) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            $handle = fopen('php://output', 'w');
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['ID', 'Name', 'Employee ID', 'Job Title', 'School Name', 'Created At']);

            User::where('role_id', $teacherRole->id)
                ->orderBy('name')
                ->chunk(100, function ($teachers) use ($handle) {
                    foreach ($teachers as $teacher) {
                        fputcsv($handle, [
                            $teacher->id,
                            $teacher->name,
                            $teacher->employee_id,
                            $teacher->job_title,
                            $teacher->school_name,
                            $teacher->created_at?->toIso8601String(),
                        ]);
                    }
                });
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="teachers_export.csv"');

        return $response;
    }
}
