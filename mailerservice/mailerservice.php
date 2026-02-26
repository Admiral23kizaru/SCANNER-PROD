<?php
// app/Services/MailerService.php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailerService
{
    protected $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com';  // or your SMTP server
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'spidermanyamete@gmail.com'; // your SMTP email
            $this->mail->Password   = 'rkkjmypynrgsaqcw';
            $this->mail->SMTPSecure = 'tls';
            $this->mail->Port       = 587;

            $this->mail->setFrom('spidermanyamete@gmail.com', 'QR Scanner');
        } catch (Exception $e) {
            dd("Mailer Error: " . $e->getMessage());
        }
    }

    public function sendEmail($to, $subject, $body)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = nl2br($body);
            $this->mail->send();
        } catch (Exception $e) {
            dd("Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}") ;
        }
    }
}