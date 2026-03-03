<?php

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailerService
{
    protected PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        $host = config('mail.mailers.smtp.host');
        $port = (int) (config('mail.mailers.smtp.port') ?? 587);
        $username = config('mail.mailers.smtp.username');
        $password = config('mail.mailers.smtp.password');
        $encryption = env('MAIL_ENCRYPTION', 'tls');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name') ?: 'QR Scanner';

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
    }

    public function sendEmail(string $to, string $subject, string $body): void
    {
        $this->mail->clearAddresses();
        $this->mail->addAddress($to);
        $this->mail->isHTML(true);
        $this->mail->Subject = $subject;
        $this->mail->Body = nl2br($body);
        $this->mail->AltBody = $body;

        try {
            $this->mail->send();
        } catch (Exception $e) {
            throw $e;
        }
    }
}

