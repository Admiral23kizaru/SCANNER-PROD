<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use function now;
use function response;
use function view;

/**
 * PasswordResetController — three-step password reset via email OTP.
 *
 * Step 1: requestOtp — send a 6-digit code to the user's email (10-min TTL).
 * Step 2: verifyOtp  — confirm the code without changing the password.
 * Step 3: reset      — verify the code again and apply the new password.
 */
class PasswordResetController extends Controller
{
    protected string $cachePrefix = 'password_otp_';

    /* ====================================================================== */
    /*  Public endpoints                                                       */
    /* ====================================================================== */

    /**
     * Send a one-time password to the user's registered email address.
     *
     * Always returns the same generic message regardless of whether the email
     * exists, to prevent user enumeration attacks.
     */
    public function requestOtp(Request $request, MailerService $mailer): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email          = $validated['email'];
        $genericMessage = 'If that email is registered, a verification code has been sent.';

        // Rate limit: max 3 requests per email per 60 seconds
        $rateLimitKey = 'otp-request:' . $email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => "Please wait {$seconds} seconds before requesting another code."
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => $genericMessage]);
        }

        $otp      = random_int(100_000, 999_999);
        $cacheKey = $this->cachePrefix . $user->email;

        Cache::put($cacheKey, $otp, now()->addMinutes(10));

        $subject = config('app.name', 'Ozamiz Schools QR-ID System') . ' Password Reset Code';
        $body    = view('emails.password-otp', [
            'user'       => $user,
            'otp'        => $otp,
            'schoolName' => config('app.name', 'Ozamiz Schools QR-ID System'),
            'code'       => $otp,
        ])->render();

        try {
            $mailer->sendEmail($user->email, $subject, $body);
        } catch (\Throwable $e) {
            Log::error('Password OTP email failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to send verification email. Please try again later.',
                'debug'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        return response()->json(['message' => $genericMessage]);
    }

    /**
     * Verify the submitted OTP without modifying the password.
     *
     * Used as an intermediate step to confirm the user controls the email
     * before the new-password form is displayed.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'digits:6'],
        ]);

        $key = 'otp-verify:' . $validated['email'];
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json(['message' => "Too many attempts. Try again in {$seconds} seconds."], 429);
        }

        $cacheKey  = $this->cachePrefix . $validated['email'];
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || (string) $storedOtp !== (string) $validated['otp']) {
            RateLimiter::hit($key, 60);
            return response()->json(['message' => 'The verification code is invalid or has expired.'], 422);
        }

        RateLimiter::clear($key);
        return response()->json(['message' => 'Code verified. You can now set a new password.']);
    }

    /**
     * Apply a new password after re-verifying the OTP.
     *
     * On success, the OTP is consumed (deleted) so it cannot be reused.
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'otp'      => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $key = 'otp-reset:' . $validated['email'];
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json(['message' => "Too many attempts. Try again in {$seconds} seconds."], 429);
        }

        $cacheKey  = $this->cachePrefix . $validated['email'];
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || (string) $storedOtp !== (string) $validated['otp']) {
            RateLimiter::hit($key, 60);
            return response()->json(['message' => 'The verification code is invalid or has expired.'], 422);
        }

        RateLimiter::clear($key);
        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'No user found for the provided email address.'], 404);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        Cache::forget($cacheKey);

        return response()->json(['message' => 'Your password has been reset successfully.']);
    }
}
