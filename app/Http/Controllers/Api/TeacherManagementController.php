<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
                'employee_number' => $u->employee_number,
                'school_name'     => $u->school_name,
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
            'employee_number' => ['required', 'string', 'max:255', 'unique:users,employee_number'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'school_name'     => ['nullable', 'string', 'max:255'],
        ], [
            'employee_number.unique' => 'A teacher with this employee number already exists.',
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

        // Generate a placeholder email from employee_number to satisfy the unique constraint
        $email = strtolower(str_replace(' ', '', $request->employee_number)) . '@deped.local';

        $user = User::create([
            'role_id'         => $teacherRole->id,
            'name'            => $request->name,
            'email'           => $email,
            'password'        => $request->password,
            'employee_number' => $request->employee_number,
            'school_name'     => $request->input('school_name'),
        ]);

        return response()->json([
            'message' => 'Teacher account created.',
            'teacher' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'employee_number' => $user->employee_number,
                'school_name'     => $user->school_name,
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
            'employee_number' => ['sometimes', 'required', 'string', 'max:255', 'unique:users,employee_number,' . $teacher->id],
            'password'        => ['nullable', 'string', 'min:8', 'confirmed'],
            'school_name'     => ['sometimes', 'nullable', 'string', 'max:255'],
        ], [
            'employee_number.unique' => 'A teacher with this employee number already exists.',
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
        if ($request->has('employee_number')) {
            $teacher->employee_number = $request->employee_number;
        }
        if ($request->has('school_name')) {
            $teacher->school_name = $request->input('school_name');
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
                'employee_number' => $teacher->employee_number,
                'school_name'     => $teacher->school_name,
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
}
