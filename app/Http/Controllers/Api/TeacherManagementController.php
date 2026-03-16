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
        $teachers = \App\Models\Teacher::orderBy('first_name')->get();

        $data = $teachers->map(function (\App\Models\Teacher $t) {
            return [
                'id'              => $t->id,
                'name'            => trim(($t->first_name ?? '') . ' ' . ($t->last_name ?? '')),
                'employee_id'     => $t->employee_id,
                'school_name'     => $t->school_name,
                'job_title'       => $t->job_title,
                'profile_photo'   => $t->profile_photo ? ltrim(str_replace('storage/', '', $t->profile_photo), '/') : null,
                'created_at'      => $t->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'max:255'],
            'employee_id'     => ['required', 'string', 'max:255', 'unique:teachers,employee_id'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'school_name'     => ['nullable', 'string', 'max:255'],
            'job_title'       => ['nullable', 'string', 'max:50'],
        ], [
            'employee_id.unique' => 'A teacher with this employee number already exists.',
            'password.min'       => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Split name into first and last
        $parts = explode(' ', $request->name, 2);
        $firstName = $parts[0];
        $lastName = $parts[1] ?? '';

        // Generate a placeholder email
        $email = strtolower(str_replace(' ', '', $request->employee_id)) . '@deped.local';

        // 1. Create Teacher record
        $teacher = \App\Models\Teacher::create([
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'email'       => $email,
            'password'    => $request->password,
            'employee_id' => $request->employee_id,
            'school_name' => $request->input('school_name'),
            'job_title'   => $request->input('job_title'),
        ]);

        // 2. Ensure a User record exists for login
        $teacherRole = Role::where('name', 'Teacher')->first();
        if ($teacherRole) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'role_id'     => $teacherRole->id,
                    'name'        => $request->name,
                    'password'    => $request->password,
                    'employee_id' => $request->employee_id,
                    'school_name' => $request->input('school_name'),
                    'job_title'   => $request->input('job_title'),
                ]
            );
        }

        return response()->json([
            'message' => 'Teacher account created.',
            'teacher' => [
                'id'              => $teacher->id,
                'name'            => $request->name,
                'employee_id'     => $teacher->employee_id,
                'school_name'     => $teacher->school_name,
                'job_title'       => $teacher->job_title,
                'profile_photo'   => $teacher->profile_photo ? ltrim(str_replace('storage/', '', $teacher->profile_photo), '/') : null,
                'created_at'      => $teacher->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = \App\Models\Teacher::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'            => ['sometimes', 'required', 'string', 'max:255'],
            'employee_id'     => ['sometimes', 'required', 'string', 'max:255', 'unique:teachers,employee_id,' . $teacher->id],
            'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
            'school_name'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_title'       => ['sometimes', 'nullable', 'string', 'max:50'],
        ], [
            'employee_id.unique' => 'A teacher with this employee number already exists.',
            'password.min'       => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $parts = explode(' ', $request->name, 2);
            $teacher->first_name = $parts[0];
            $teacher->last_name = $parts[1] ?? '';
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

        // Sync with User table
        $user = User::where('email', $teacher->email)->first();
        if ($user) {
            if ($request->has('name')) $user->name = $request->name;
            if ($request->has('employee_id')) $user->employee_id = $request->employee_id;
            if ($request->has('school_name')) $user->school_name = $request->input('school_name');
            if ($request->has('job_title')) $user->job_title = $request->input('job_title');
            if ($request->filled('password')) $user->password = $request->password;
            $user->save();
        }

        return response()->json([
            'message' => 'Teacher updated.',
            'teacher' => [
                'id'              => $teacher->id,
                'name'            => trim($teacher->first_name . ' ' . $teacher->last_name),
                'employee_id'     => $teacher->employee_id,
                'school_name'     => $teacher->school_name,
                'job_title'       => $teacher->job_title,
                'profile_photo'   => $teacher->profile_photo ? ltrim(str_replace('storage/', '', $teacher->profile_photo), '/') : null,
                'created_at'      => $teacher->created_at?->toIso8601String(),
            ],
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $teacher = \App\Models\Teacher::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        // Protect data integrity: students.created_by might use the User ID
        // We need to find the User first
        $user = User::where('email', $teacher->email)->first();
        if ($user) {
            $hasCreatedStudents = Student::where('created_by', $user->id)->exists();
            if ($hasCreatedStudents) {
                return response()->json([
                    'message' => 'Cannot delete this teacher because they created student records. Reassign those students first.',
                ], 422);
            }
            $user->delete();
        }

        $teacher->delete();

        return response()->json(['message' => 'Teacher deleted.'], 200);
    }

    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $teacher = \App\Models\Teacher::find($id);
        if (!$teacher) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $file = $request->file('photo');
        
        // Use Storage::disk('public')->put as explicitly requested
        // This will save the file to storage/app/public/teachers and return 'teachers/filename.ext'
        $path = \Illuminate\Support\Facades\Storage::disk('public')->put('teachers', $file);

        // Store relative path (e.g. 'teachers/filename.png')
        $teacher->profile_photo = $path;
        $teacher->save();

        // Sync with User table
        $user = User::where('email', $teacher->email)->first();
        if ($user) {
            $user->profile_photo = $path;
            $user->save();
        }

        return response()->json([
            'message'       => 'Profile photo updated.',
            'profile_photo' => $path,
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
