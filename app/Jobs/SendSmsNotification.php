<?php

namespace App\Jobs;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SendSmsNotification — queued job that notifies a student's guardian via Semaphore SMS.
 *
 * === SMS Flow ===
 * 1. AttendanceController::scanPublic() detects a new student QR scan.
 * 2. If the student has a contact_number or emergency_contact, it dispatches this job.
 * 3. The job is picked up by the Laravel queue worker (php artisan queue:work).
 * 4. The job applies two layers of cooldown to conserve Semaphore tokens:
 *      a) Rapid-fire cooldown (1 min) — prevents double-firing on accidental re-scans.
 *      b) Per-session lock (12 hrs) — ensures only one SMS per session per day.
 * 5. The contact number is normalised to Semaphore format (639XXXXXXXXX).
 * 6. An HTTP POST is made to https://semaphore.co/api/v4/messages.
 * 7. Success/failure is logged to storage/logs/laravel.log.
 *
 * === .env Requirements ===
 * SEMAPHORE_API_KEY=your_api_key_here
 * SEMAPHORE_SENDER_NAME=FINGERLINGS  ← Registered sender name with Semaphore
 */
class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly string $time,
        public readonly string $session = 'Morning'
    ) {}

    /**
     * Execute the SMS notification job.
     *
     * This method is called automatically by the queue worker.
     * It performs three validations before sending:
     *   1. Contact number exists.
     *   2. Rapid-fire cooldown (1 minute) — prevents accidental double-send.
     *   3. Session lock (12 hours) — one SMS per morning, one per afternoon.
     */
    public function handle(): void
    {
        // ── 1. Resolve the contact number ────────────────────────────────────
        $contact = $this->student->contact_number ?: $this->student->emergency_contact;

        if (!$contact) {
            Log::info("SMS Cancelled: No contact number for student {$this->student->id}");
            return;
        }

        // ── 2. Rapid-fire cooldown (1 minute) ────────────────────────────────
        // Prevents duplicate SMS when a student passes the scanner twice quickly
        // or when a teacher accidentally re-scans. Cache key is per student ID.
        $cooldownKey = "sms_cooldown_{$this->student->id}";
        if (Cache::has($cooldownKey)) {
            Log::info("SMS Blocked: Rapid-fire cooldown active for student {$this->student->id}");
            return;
        }

        // ── 3. Per-session lock (once per morning / once per afternoon) ───────
        // Saves Semaphore tokens: even if a student is scanned 10 times in one
        // session, only the very first scan dispatches an SMS to the guardian.
        $sessionKey = "sms_sent_{$this->student->id}_{$this->session}_" . date('Y-m-d');
        if (Cache::has($sessionKey)) {
            Log::info("SMS Skipped: Already sent for student {$this->student->id} in {$this->session} session today.");
            return;
        }

        // ── 4. Normalise phone number to Semaphore format (639XXXXXXXXX) ─────
        // Semaphore requires 12-digit numbers starting with 63 (country code PH).
        // Input formats handled:
        //   - 09XXXXXXXXX (11-digit local format) → 639XXXXXXXXX
        //   - 9XXXXXXXXX  (10-digit without zero)  → 639XXXXXXXXX
        //   - 639XXXXXXXXX (already correct)        → unchanged
        $contact = preg_replace('/\D/', '', $contact); // strip everything non-numeric
        if (str_starts_with($contact, '09') && strlen($contact) === 11) {
            $contact = '639' . substr($contact, 2);    // 09XX → 639XX
        } elseif (str_starts_with($contact, '9') && strlen($contact) === 10) {
            $contact = '639' . substr($contact, 1);    // 9XX → 639XX
        }

        // ── 5. Load Semaphore credentials from config ─────────────────────────
        $apiKey = config('services.semaphore.key');
        $sender = config('services.semaphore.sender', 'SEMAPHORE');

        if (!$apiKey) {
            Log::error('SMS Failed: SEMAPHORE_API_KEY is not set in .env');
            return;
        }

        // ── 6. Build message (max 160 characters for a single SMS credit) ─────
        $message = "ScanUp: {$this->student->first_name} {$this->student->last_name} "
                 . "has successfully entered the campus at {$this->time} ({$this->session}).";

        if (strlen($message) > 160) {
            $message = substr($message, 0, 157) . '...';
        }

        // ── 7. Send via Semaphore API ─────────────────────────────────────────
        try {
            $response = Http::post('https://semaphore.co/api/v4/messages', [
                'apikey'     => $apiKey,
                'number'     => $contact,
                'message'    => $message,
                'sendername' => $sender,
            ]);

            if ($response->successful()) {
                // Semaphore returns an array of message objects
                $msgData = is_array($response->json()) ? ($response->json()[0] ?? $response->json()) : $response->json();
                $msgId   = $msgData['message_id'] ?? 'N/A';
                $status  = $msgData['status'] ?? 'Sent';

                Log::info("SMS Success: ID={$msgId} | Status={$status} | To={$contact} | Student={$this->student->id}");

                // Set locks AFTER successful send — if send fails, we can retry
                Cache::put($cooldownKey, true, 60);                   // 1-minute rapid-fire cooldown
                Cache::put($sessionKey, true, now()->addHours(12));   // 12-hour session lock
            } else {
                Log::error("SMS Failed for student {$this->student->id}. HTTP {$response->status()}: {$response->body()}");
            }
        } catch (\Exception $e) {
            Log::error("SMS Exception for student {$this->student->id}: {$e->getMessage()}");
        }
    }
}
