<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GuardController extends Controller
{
    /**
     * Public login for Guard Terminal launcher (Bat file): DepEd ID + shared admin password → Sanctum token + school payload.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'deped_school_id' => 'required|string',
            'password'        => 'required|string',
        ]);

        $school = School::where('deped_school_id', $request->deped_school_id)->first();

        if (!$school) {
            return response()->json([
                'error' => 'School ID not found. Please contact your administrator.',
            ], 404);
        }

        $user = User::where('school_id', $school->id)
            ->where('role_id', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'error' => 'No admin account found for this school.',
            ], 404);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Invalid password. Please try again.',
            ], 401);
        }

        $token = $user->createToken('guard-terminal')->plainTextToken;

        return response()->json([
            'school_id'   => $school->id,
            'school_name' => $school->name,
            'deped_id'    => $school->deped_school_id,
            'token'       => $token,
        ], 200);
    }
}
