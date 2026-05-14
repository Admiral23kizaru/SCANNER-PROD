<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ehris\EhrisReportingManager;
use App\Models\Ehris\EhrisUser;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class GuardAuthController extends Controller
{
    public function __construct(private SchoolResolver $schools)
    {
    }

    /**
     * Authenticate the guard terminal for a school scanner session.
     *
     * PURPOSE: Opens the scanner for a school using either the legacy local
     * ScanUp scanner password or the EHRIS password of that school's Reporting
     * Manager/principal.
     *
     * WHY: Schools may not know the old local scanner password after moving
     * teacher/principal identity to EHRIS. This keeps the BAT payload unchanged
     * while letting the principal's EHRIS password authorize the school scanner.
     *
     * DATA ISOLATION: The EHRIS fallback only accepts a Reporting Manager whose
     * tbl_reporting_manager.department_id and tbl_user.department_id match the
     * submitted DepEd school ID. The issued ScanUp token belongs to a local
     * scanner user scoped to that exact school_id.
     *
     * @param \Illuminate\Http\Request $request Request body with deped_school_id and password.
     * @return \Illuminate\Http\JsonResponse Scanner token and school context.
     */
    public function login(Request $request)
    {
        $key = 'guard-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => 'Too many login attempts. Try again in ' . $seconds . ' seconds.'
            ], 429);
        }

        $validated = $request->validate([
            'deped_school_id' => 'required|string',
            'password'        => 'required|string',
        ]);

        $email = 'school' . $validated['deped_school_id'] . '@deped.ozamiz.edu.ph';
        $school = $this->schools->resolveForScanUpWrite($validated['deped_school_id']);

        if (!$school) {
            RateLimiter::hit($key, 60);
            return response()->json([
                'error' => 'School not found. Contact your administrator.',
            ], 404);
        }

        $user = User::where('email', $email)->first();

        if (!$user || ! Hash::check($validated['password'], $user->password)) {
            $user = $this->resolveScannerUserFromEhrisPrincipal(
                $school,
                $validated['password'],
                $email
            );
        }

        if (!$user) {
            RateLimiter::hit($key, 60);
            return response()->json([
                'error' => 'Invalid School ID or password.',
            ], 401);
        }

        RateLimiter::clear($key);

        $user->tokens()->delete();
        $token = $user->createToken('bat-scanner')->plainTextToken;

        return response()->json([
            'token'       => $token,
            'deped_id'    => $validated['deped_school_id'],
            'school_id'   => $school->id,
            'school_name' => $school->name,
            'message'     => 'Login successful',
        ], 200);
    }

    /**
     * Resolve a local ScanUp scanner user after verifying the EHRIS principal password.
     *
     * PURPOSE: Allows the school's Reporting Manager/principal to authorize
     * the guard terminal using their EHRIS password without making the BAT file
     * know anything about EHRIS.
     *
     * WHY: The scanner still needs a local ScanUp user for Sanctum tokens and
     * attendance.scanned_by foreign keys. The principal's EHRIS password is only
     * used for verification; the token is issued to a school-scoped scanner user.
     *
     * DATA ISOLATION: The Reporting Manager row must match the school's DepEd
     * ID, and the EHRIS user must be active and belong to the same department.
     *
     * @param \App\Models\School $school The ScanUp school resolved from deped_school_id.
     * @param string $password The password typed in the launcher.
     * @param string $scannerEmail The deterministic local scanner account email.
     * @return \App\Models\User|null Local scanner user on success, null on failed verification.
     */
    private function resolveScannerUserFromEhrisPrincipal(
        School $school,
        string $password,
        string $scannerEmail
    ): ?User {
        $candidates = collect();

        $manager = EhrisReportingManager::where('department_id', $school->deped_school_id)->first();
        if ($manager) {
            $mapped = EhrisUser::active()
                ->where('userId', $manager->manager_name)
                ->where('department_id', $school->deped_school_id)
                ->first();

            if ($mapped) {
                $candidates->push($mapped);
            }
        }

        $fallback = EhrisUser::active()
            ->where('department_id', $school->deped_school_id)
            ->where(function ($q) {
                $q->whereIn('role', ['Reporting Manager', 'Principal', 'School Principal'])
                    ->orWhere('job_title', 'like', '%Principal%');
            })
            ->limit(25)
            ->get();

        foreach ($fallback as $user) {
            if (!$candidates->contains(fn ($x) => (string) $x->userId === (string) $user->userId)) {
                $candidates->push($user);
            }
        }

        $matched = $candidates->first(function ($ehrisUser) use ($password) {
            return !empty($ehrisUser->password) && Hash::check($password, $ehrisUser->password);
        });

        if (!$matched) {
            return null;
        }

        $guardRole = Role::where('name', 'Guard')->first();

        if (!$guardRole) {
            return null;
        }

        return User::updateOrCreate(
            ['email' => $scannerEmail],
            [
                'name'      => $school->name . ' Scanner',
                'password'  => Hash::make(Str::random(64)),
                'role_id'   => $guardRole->id,
                'school_id' => $school->id,
                'status'    => 'active',
            ]
        );
    }
}
