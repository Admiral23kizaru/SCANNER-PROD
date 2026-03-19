<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\Attendance;
use App\Services\MailerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailNotification implements ShouldQueue
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
        if (!$this->student->guardian_email) {
            return;
        }

        try {
            $mailer = new MailerService();
            $subject = 'Student Attendance Notification - ' . $this->session;
            $body = "
                <p>Dear Parent/Guardian,</p>
                <p>This is to notify you that <strong>{$this->student->first_name} {$this->student->last_name}</strong> has successfully scanned their ID upon entering the campus.</p>
                <p><strong>Time:</strong> {$this->time}</p>
                <p><strong>Session:</strong> {$this->session}</p>
                <p>Thank you.</p>
            ";

            $mailer->sendEmail($this->student->guardian_email, $subject, $body);
        } catch (\Exception $e) {
            Log::error("Failed to send email notification to {$this->student->guardian_email}: " . $e->getMessage());
        }
    }
}
