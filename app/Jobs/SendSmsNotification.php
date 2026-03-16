<?php

namespace App\Jobs;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $student;
    public $time;
    public $session;

    public function __construct(Student $student, string $time, string $session = 'Morning')
    {
        $this->student = $student;
        $this->time = $time;
        $this->session = $session;
    }

    public function handle(): void
    {
        $contact = $this->student->contact_number ?: $this->student->emergency_contact;
        
        if (!$contact) {
            Log::info("SMS Cancelled: No contact number for student {$this->student->id}");
            return;
        }

        // 1. Rapid-fire cooldown (1 minute) to prevent accidental double-taps
        $cooldownKey = "sms_cooldown_{$this->student->id}";
        if (Cache::has($cooldownKey)) {
            Log::info("SMS Blocked: Rapid-fire cooldown active for student {$this->student->id}");
            return;
        }

        // 2. Per-session lock (Morning/Afternoon)
        $sessionKey = "sms_sent_{$this->student->id}_{$this->session}_" . date('Y-m-d');
        if (Cache::has($sessionKey)) {
            Log::info("SMS Skipped: Already sent for {$this->student->id} session {$this->session} today.");
            return;
        }

        // 3. Format Contact Number for Semaphore (Standardize to 639XXXXXXXXX)
        $contact = preg_replace('/\D/', '', $contact);
        if (str_starts_with($contact, '09') && strlen($contact) === 11) {
            $contact = '639' . substr($contact, 2);
        } elseif (str_starts_with($contact, '9') && strlen($contact) === 10) {
            $contact = '639' . substr($contact, 1);
        }

        $apiKey = config('services.semaphore.key');
        $sender = config('services.semaphore.sender', 'SEMAPHORE');

        if (!$apiKey) {
            Log::error("SMS Failed: SEMAPHORE_API_KEY not set.");
            return;
        }

        $message = "ScanUp: {$this->student->first_name} {$this->student->last_name} has successfully entered the campus at {$this->time} ({$this->session}).";
        
        // Ensure message is within 160 character limit
        if (strlen($message) > 160) {
            $message = substr($message, 0, 157) . '...';
        }

        try {
            $response = Http::post('https://semaphore.co/api/v4/messages', [
                'apikey' => $apiKey,
                'number' => $contact,
                'message' => $message,
                'sendername' => $sender
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Semaphore usually returns an array of messages
                $msgData = is_array($data) ? ($data[0] ?? $data) : $data;
                $msgId = $msgData['message_id'] ?? 'N/A';
                $status = $msgData['status'] ?? 'Sent';

                Log::info("SMS Success: ID: {$msgId} | Status: {$status} | To: {$contact} | Student: {$this->student->id}");
                
                // Set locks
                Cache::put($cooldownKey, true, 60);
                Cache::put($sessionKey, true, now()->addHours(12));
            } else {
                Log::error("SMS Failed for student {$this->student->id}. Status: " . $response->status() . " Response: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("SMS Exception for student {$this->student->id}: " . $e->getMessage());
        }
    }
}
