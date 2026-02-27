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
            ->get(['id', 'name', 'email', 'created_at']);

        $data = $teachers->map(function (User $u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'A teacher with this email already exists.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $teacherRole = Role::where('name', 'Teacher')->first();
        if (!$teacherRole) {
            return response()->json(['message' => 'Teacher role not found.'], 500);
        }

        $user = User::create([
            'role_id' => $teacherRole->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        return response()->json([
            'message' => 'Teacher account created.',
            'teacher' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $teacher->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'A teacher with this email already exists.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $teacher->name = $request->name;
        }
        if ($request->has('email')) {
            $teacher->email = $request->email;
        }
        if ($request->filled('password')) {
            $teacher->password = $request->password;
        }
        $teacher->save();

        return response()->json([
            'message' => 'Teacher updated.',
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'created_at' => $teacher->created_at?->toIso8601String(),
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
}
