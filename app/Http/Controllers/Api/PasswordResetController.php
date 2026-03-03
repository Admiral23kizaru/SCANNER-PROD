<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use function now;
use function response;
use function view;

class PasswordResetController extends Controller
{
    protected string $cachePrefix = 'password_otp_';

    /**
     * Step 1: Request an OTP to be sent to the user's email.
     */
    public function requestOtp(Request $request, MailerService $mailer): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $validated['email'];
        $user = User::where('email', $email)->first();

        // Always respond with a generic message to avoid leaking which emails exist.
        $genericMessage = 'If that email is registered, a verification code has been sent.';

        if (!$user) {
            return response()->json(['message' => $genericMessage]);
        }

        $otp = random_int(100000, 999999); // 6-digit code
        $cacheKey = $this->cachePrefix . $user->email;

        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        $subject = config('app.name', 'Ozamiz Schools QR-ID System') . ' Password Reset Code';
        $body = view('emails.password-otp', [
            'user' => $user,
            'otp' => $otp,
            'schoolName' => config('app.name', 'Ozamiz Schools QR-ID System'),
            'code' => $otp,
        ])->render();

        $mailer->sendEmail($user->email, $subject, $body);

        return response()->json(['message' => $genericMessage]);
    }

    /**
     * Step 2: Verify the OTP without changing the password yet.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $email = $validated['email'];
        $otp = $validated['otp'];

        $cacheKey = $this->cachePrefix . $email;
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || (string) $storedOtp !== (string) $otp) {
            return response()->json([
                'message' => 'The verification code is invalid or has expired.',
            ], 422);
        }

        return response()->json([
            'message' => 'Code verified. You can now set a new password.',
        ]);
    }

    /**
     * Step 3: Reset the password using a valid OTP.
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $email = $validated['email'];
        $otp = $validated['otp'];

        $cacheKey = $this->cachePrefix . $email;
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || (string) $storedOtp !== (string) $otp) {
            return response()->json([
                'message' => 'The verification code is invalid or has expired.',
            ], 422);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'No user found for the provided email address.',
            ], 404);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        Cache::forget($cacheKey);

        return response()->json([
            'message' => 'Your password has been reset successfully.',
        ]);
    }
}

