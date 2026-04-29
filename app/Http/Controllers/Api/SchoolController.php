<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SchoolController extends Controller
{
    /**
     * Create a new school and its admin account.
     * Accessible only by Super Admin (role_id=1, school_id=null or handled by middleware).
     */
    public function store(Request $request): JsonResponse
    {
        // Enforce Super Admin only
        if (auth()->user()->school_id !== null) {
            return response()->json(['message' => 'Unauthorized. Super Admin only.'], 403);
        }

        try {
            $request->validate([
                'deped_school_id' => 'required|string|max:50',
                'name'            => 'required|string|max:255',
                'email'           => 'required|email|unique:users,email',
                'password'        => 'required|string|min:6',
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['email'])) {
                return response()->json(['error' => 'An admin account with this email already exists.'], 422);
            }
            return response()->json([
                'error'  => 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        // Prevent duplicate school names or IDs if necessary
        if (School::where('deped_school_id', $request->deped_school_id)->exists()) {
            return response()->json(['error' => 'This DepEd School ID is already registered.'], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Create the school
            $school = School::create([
                'name'            => $request->name,
                'deped_school_id' => $request->deped_school_id,
            ]);

            // 2. Create the admin user
            $user = User::create([
                'name'      => $request->name . ' Admin',
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role_id'   => 1, // Admin role
                'school_id' => $school->id,
                'status'    => 'active',
            ]);

            // 3. Create default settings
            SchoolSetting::create([
                'school_id'         => $school->id,
                'late_threshold'    => '07:30:00',
                'absence_threshold' => 3,
            ]);

            // 4. Create default school year
            SchoolYear::create([
                'school_id'  => $school->id,
                'name'       => '2025-2026',
                'start_date' => '2025-06-02',
                'end_date'   => '2026-03-27',
                'is_active'  => 1,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'School account created successfully.',
                'school'  => $school,
                'admin'   => [
                    'email' => $user->email,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('School creation failed via Admin panel: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create school account. Please try again.'], 500);
        }
    }
}
