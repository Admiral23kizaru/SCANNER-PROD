<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke previous tokens to enforce single active session
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'       => $user->load('role'),
            'token'      => $token,
            'token_type' => 'Bearer',
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
