<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EhrisAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * AuthController — handles login, logout, and current-user resolution.
 */
class AuthController extends Controller
{
    /**
     * Authenticate a user with email and password.
     *
     * Revokes all existing tokens before issuing a new one,
     * ensuring single-session security.
     *
     * @throws ValidationException on bad credentials
     */
    public function login(Request $request): JsonResponse
    {
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => 'Too many login attempts. Try again in ' . $seconds . ' seconds.'
            ], 429);
        }

        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var EhrisAuthService $ehrisService */
        $ehrisService = app(EhrisAuthService::class);

        if ($ehrisService->isEhrisUser($request->email)) {

            /**
             * EHRIS AUTH PATH
             * PURPOSE: Verify teacher/RM credentials
             * against EHRIS tbl_user table.
             * On success, provision ScanUp user record.
             * On failure, hit rate limiter + throw.
             */

            $ehrisUser = $ehrisService->findEhrisUser(
                $request->email
            );

            if (!$ehrisUser) {
                RateLimiter::hit($key, 60);
                throw ValidationException::withMessages([
                    'email' => [
                        'The provided credentials ' .
                        'are incorrect.',
                    ],
                ]);
            }

            if (!$ehrisService->verifyPassword(
                $request->password,
                $ehrisUser->password
            )) {
                RateLimiter::hit($key, 60);
                throw ValidationException::withMessages([
                    'email' => [
                        'The provided credentials ' .
                        'are incorrect.',
                    ],
                ]);
            }

            $roleName = $ehrisService->getScanUpRoleName(
                $ehrisUser
            );

            try {
                $user = $ehrisService->provisionUser(
                    $ehrisUser,
                    $roleName
                );
            } catch (\RuntimeException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error'   => 'provisioning_failed',
                ], 422);
            }

        } else {

            /**
             * LOCAL AUTH PATH
             * PURPOSE: Admin and Guard accounts use
             * ScanUp's own users table for auth.
             * KEEP this block exactly as it currently is.
             * Do not change anything inside this else.
             */
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                RateLimiter::hit($key, 60);
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

        }

        RateLimiter::clear($key);

        // Revoke previous tokens to enforce single active session
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'role_id'   => $user->role_id,
                'school_id' => $user->school_id, // CRITICAL — launcher reads this
                'role'      => $user->load('role')->role,
            ]
        ]);
    }

    /** Revoke the current access token (logout). */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /** Return the currently authenticated user with their role. */
    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('role'));
    }
}
