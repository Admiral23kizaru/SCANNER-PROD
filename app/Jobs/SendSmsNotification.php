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
 * === Notification Preference Values ===
 *   0 = No SMS   — This job should never be dispatched for pref 0.
 *   1 = Regular  — 1 SMS per day. Controller marks last_sms_sent_date BEFORE dispatching.
 *                  This job only applies a rapid-fire cooldown (1 min) as extra safety.
 *   2 = VIP      — SMS on every scan. Only rapid-fire cooldown applies.
 *
 * === SMS Message Format ===
 * "[FirstName] [LastName] has [checked IN/OUT] at [SchoolShortName] - [HH:MM AM/PM]."
 * Hard limit: ≤ 160 characters (1 Semaphore credit).
 *
 * === .env Requirements ===
 *   SEMAPHORE_API_KEY=your_api_key_here
 *   SEMAPHORE_SENDER_NAME=FINGERLINGS    ← Registered sender name with Semaphore
 *   SCHOOL_SHORT_NAME=Ozamiz School      ← Max 30 chars
 */
class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  Student     $student         The scanned student.
     * @param  string      $time            Scan time in "hh:mm AM/PM" format.
     * @param  string      $session         Session label (e.g. "Morning", "Afternoon").
     * @param  string|null $formattedNumber Pre-formatted PH number (63XXXXXXXXXX).
     * @param  int         $preference      Notification preference: 1=Regular, 2=VIP.
     */
    public function __construct(
        public readonly Student $student,
        public readonly string $time,
        public readonly string $session         = 'Morning',
        public readonly ?string $formattedNumber = null,
        public readonly int $preference          = 1,
    ) {}

    /**
     * Execute the SMS notification job.
     *
     * Validation layers (must all pass before sending):
     *  1. Contact number exists.
     *  2. Rapid-fire cooldown (1 minute) — prevents double-send on rapid re-scans.
     *     Note: the per-day lock for preference=1 is handled BEFORE dispatch in the controller.
     */
    public function handle(): void
    {
        // ── 1. Resolve the contact number ────────────────────────────────────
        $contact = $this->formattedNumber ?: ($this->student->contact_number ?: $this->student->emergency_contact);

        if (!$contact) {
            Log::info("SMS Cancelled: No contact number for student {$this->student->id}");
            return;
        }

        // ── 2. Normalise phone number to Semaphore format (639XXXXXXXXX) ─────
        $contact = preg_replace('/\D/', '', $contact);
        $contact = preg_replace('/^0/', '63', $contact);
        if (str_starts_with($contact, '9') && strlen($contact) === 10) {
            $contact = '63' . $contact;
        }

        // ── 3. Rapid-fire cooldown (1 minute) ────────────────────────────────
        // Prevents double-firing when a student accidentally scans twice in a row.
        // For VIP SMS (pref=2) this is the only gate. For Regular SMS (pref=1) the
        // per-day gate was already applied in AttendanceController before dispatch.
        $bypassDuplicateChecks = app()->environment('local');
        $cooldownKey = "sms_cooldown_{$this->student->id}_{$this->preference}";
        if (!$bypassDuplicateChecks && Cache::has($cooldownKey)) {
            Log::info("SMS Skipped: Rapid-fire cooldown active for student {$this->student->id} (pref={$this->preference})");
            return;
        }

        // ── 4. Load Semaphore credentials ─────────────────────────────────────
        $apiKey = config('services.semaphore.key');
        $sender = config('services.semaphore.sender', 'SEMAPHORE');

        if (!$apiKey) {
            Log::error('SMS Failed: SEMAPHORE_API_KEY is not set in .env');
            return;
        }

        // ── 5. Build compact message (≤ 160 chars for 1 Semaphore credit) ─────
        // Format: "[FirstName] [LastName] has [checked IN/checked OUT] at [SchoolShortName] - [HH:MM AM/PM]."
        $firstName = $this->student->first_name;
        $lastName  = $this->student->last_name;

        // Determine IN/OUT direction from session name
        $sessionLower = strtolower($this->session);
        $direction = str_contains($sessionLower, 'out') ? 'checked OUT' : 'checked IN';

        // School short name — max 30 chars enforced
        $schoolShort = substr(
            config('services.school.short_name', 'School'),
            0, 30
        );

        // Compose message
        $message = "{$firstName} {$lastName} has {$direction} at {$schoolShort} - {$this->time}.";

        // Server-side length guard (spec: hard limit 160 chars, 1 credit)
        if (strlen($message) > 160) {
            Log::error("SMS Aborted: Message exceeds 160 chars for student {$this->student->id}. Length=" . strlen($message));
            return;
        }

        // ── 6. Send via Semaphore API ─────────────────────────────────────────
        try {
            Log::info("SMS Triggered: Sending to {$contact} for student {$this->student->id} (pref={$this->preference}). Msg: \"{$message}\"");

            $response = Http::timeout(5)->post('https://semaphore.co/api/v4/messages', [
                'apikey'     => $apiKey,
                'number'     => $contact,
                'message'    => $message,
                'sendername' => $sender,
            ]);

            if ($response->successful()) {
                $msgData = is_array($response->json()) ? ($response->json()[0] ?? $response->json()) : $response->json();
                $msgId   = $msgData['message_id'] ?? 'N/A';
                $status  = $msgData['status']     ?? 'Sent';

                Log::info("SMS Success: ID={$msgId} | Status={$status} | To={$contact} | Student={$this->student->id}");

                // Set rapid-fire cooldown AFTER successful send
                Cache::put($cooldownKey, true, 60); // 1 minute
            } else {
                Log::error("SMS Failed for student {$this->student->id}. HTTP {$response->status()}: {$response->body()}");
            }
        } catch (\Exception $e) {
            Log::error("SMS Exception for student {$this->student->id}: {$e->getMessage()}");
        }
    }
}
