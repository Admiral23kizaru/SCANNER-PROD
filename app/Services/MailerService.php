<?php

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;

class MailerService
{
    protected PHPMailer $mail;

    public function __construct()
    {
        try {
            $this->mail = new PHPMailer(true);

            $host        = config('mail.mailers.smtp.host') ?: env('MAIL_HOST', 'smtp.gmail.com');
            $port        = (int) (config('mail.mailers.smtp.port') ?: env('MAIL_PORT', 587));
            $username    = config('mail.mailers.smtp.username') ?: env('MAIL_USERNAME');
            $password    = config('mail.mailers.smtp.password') ?: env('MAIL_PASSWORD');
            $encryption  = config('mail.mailers.smtp.encryption') ?: env('MAIL_ENCRYPTION', 'tls');
            $fromAddress = config('mail.from.address') ?: env('MAIL_FROM_ADDRESS');
            $fromName    = config('mail.from.name') ?: env('MAIL_FROM_NAME', 'QR Scanner');

            $this->mail->isSMTP();
            $this->mail->Host       = $host;
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $username;
            $this->mail->Password   = $password;
            $this->mail->SMTPSecure = $encryption === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $this->mail->Port       = $port;
            $this->mail->CharSet    = 'UTF-8';

            // If fromAddress is missing or a placeholder, fall back to the SMTP username
            if (!$fromAddress || str_contains((string) $fromAddress, 'your_email')) {
                $fromAddress = $username;
            }

            if ($fromAddress) {
                $this->mail->setFrom($fromAddress, $fromName);
            }
        } catch (\Exception $e) {
            Log::warning('MailerService: Could not initialize PHPMailer — ' . $e->getMessage());
            $this->mail = new PHPMailer(true); // bare instance, won't send but won't crash DI
        }
    }


    public function sendEmail(string $to, string $subject, string $body): bool
    {
        $this->mail->clearAddresses();
        $this->mail->addAddress($to);
        $this->mail->isHTML(true);
        $this->mail->Subject = $subject;
        $this->mail->Body = $body;
        $this->mail->AltBody = strip_tags($body);

        try {
            $this->mail->send();
            Log::info("MailerService: Email sent successfully to {$to}");
            return true;
        } catch (Exception $e) {
            Log::error("MailerService: Failed to send email to {$to}. Error: " . $e->getMessage());
            Log::error("MailerService: SMTP Debug - Host: {$this->mail->Host}, Port: {$this->mail->Port}, Username: {$this->mail->Username}");
            throw $e;
        }
    }
}
