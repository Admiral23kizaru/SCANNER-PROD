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

/**
 * SetupController — handles first-time school registration via the bat file launcher.
 *
 * This controller is PUBLIC (no auth middleware).
 * It is called by the Windows bat launcher to register a new school,
 * create the school's admin account, default settings, and school year,
 * then return a Sanctum token and school_id for the Guard Terminal URL.
 *
 * Flow:
 *   Bat file → POST /api/setup/register-school
 *   → School created → Admin user created → Settings + SchoolYear seeded
 *   → Token generated → Response: { school_id, school_name, token, user }
 *   → Chrome opens: /qrid/?school_id=X&token=XXX&school_name=XXX
 */
class SetupController extends Controller
{
    /**
     * Register a new school and its admin account.
     *
     * Called by the Windows bat file launcher when setting up a new
     * Guard Terminal for a school.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerSchool(Request $request): JsonResponse
    {
        // ── Step 1: Validate ──────────────────────────────────────────────────
        try {
            $request->validate([
                'school_name'     => 'required|string|max:255',
                'deped_school_id' => 'required|string|max:50',
                'email'           => 'required|email|unique:tbl_scanup_users,email',
                'password'        => 'required|string|min:6',
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();

            // Custom message for duplicate email
            if (isset($errors['email'])) {
                return response()->json([
                    'error' => 'An admin account with this email already exists.',
                ], 422);
            }

            return response()->json([
                'error'  => 'Validation failed.',
                'errors' => $errors,
            ], 422);
        }

        // Prevent duplicate school names
        if (School::where('name', $request->school_name)->exists()) {
            return response()->json([
                'error' => 'This school is already registered in the system.',
            ], 422);
        }

        // ── Steps 2–6: Wrap everything in a transaction ───────────────────────
        try {
            $result = DB::transaction(function () use ($request) {

                // ── Step 2: Create the school ─────────────────────────────────
                $school = School::create([
                    'name'            => $request->school_name,
                    'deped_school_id' => $request->deped_school_id,
                ]);

                // ── Step 3: Create the admin user ─────────────────────────────
                $user = User::create([
                    'name'      => 'Admin',
                    'email'     => $request->email,
                    'password'  => Hash::make($request->password),
                    'role_id'   => 1,          // 1 = Admin
                    'school_id' => $school->id,
                    'status'    => 'active',
                ]);

                // ── Step 4: Create default school settings ────────────────────
                SchoolSetting::create([
                    'school_id'         => $school->id,
                    'late_threshold'    => '07:30:00',
                    'absence_threshold' => 3,
                ]);

                // ── Step 5: Create current school year ────────────────────────
                SchoolYear::create([
                    'school_id'  => $school->id,
                    'name'       => '2025-2026',
                    'start_date' => '2025-06-02',
                    'end_date'   => '2026-03-27',
                    'is_active'  => 1,
                ]);

                // ── Step 6: Generate Sanctum token ────────────────────────────
                $token = $user->createToken('bat-launcher-token')->plainTextToken;

                return compact('school', 'user', 'token');
            });

            // ── Step 7: Return the response ───────────────────────────────────
            return response()->json([
                'school_id'   => $result['school']->id,
                'school_name' => $result['school']->name,
                'token'       => $result['token'],
                'user'        => [
                    'id'        => $result['user']->id,
                    'email'     => $result['user']->email,
                    'role_id'   => $result['user']->role_id,
                    'school_id' => $result['user']->school_id,
                ],
            ], 201);

        } catch (\Throwable $e) {
            Log::error('School registration failed: ' . $e->getMessage(), [
                'school_name' => $request->school_name,
                'email'       => $request->email,
            ]);

            return response()->json([
                'error' => 'Registration failed. Please try again.',
            ], 500);
        }
    }
}
