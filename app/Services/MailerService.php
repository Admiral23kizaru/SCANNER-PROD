<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailerService
{
    protected PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        // Resolve settings from Laravel config / .env
        $host = config('mail.mailers.smtp.host');
        $port = (int) (config('mail.mailers.smtp.port') ?? 587);
        $username = config('mail.mailers.smtp.username');
        $password = config('mail.mailers.smtp.password');
        $encryption = env('MAIL_ENCRYPTION', 'tls');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name') ?: 'QR Scanner';

        try {
            $this->mail->isSMTP();
            $this->mail->Host = $host;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $username;
            $this->mail->Password = $password;
            $this->mail->SMTPSecure = $encryption;
            $this->mail->Port = $port;

            if ($fromAddress) {
                $this->mail->setFrom($fromAddress, $fromName);
            }
        } catch (Exception $e) {
            // If configuration fails, let the caller handle via exception.
            throw $e;
        }
    }

    public function sendEmail(string $to, string $subject, string $body): void
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = nl2br($body);
            $this->mail->AltBody = $body;
            $this->mail->send();
        } catch (Exception $e) {
            // Bubble up so controller can decide whether to ignore/log.
            throw $e;
        }
    }
}

