<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GuardAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'deped_school_id' => 'required|string',
            'password'        => 'required|string',
        ]);

        $email = 'school' . $validated['deped_school_id'] . '@deped.ozamiz.edu.ph';

        $user = User::where('email', $email)->first();

        if (!$user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'error' => 'Invalid School ID or password.',
            ], 401);
        }

        $school = School::where('deped_school_id', $validated['deped_school_id'])->first();

        if (!$school) {
            return response()->json([
                'error' => 'School not found. Contact your administrator.',
            ], 404);
        }

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
}
