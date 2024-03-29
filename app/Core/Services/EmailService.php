<?php

namespace Flute\Core\Services;

use Exception;
use Flute\Core\Auth\Events\PasswordResetRequestedEvent;
use Flute\Core\Auth\Events\UserRegisteredEvent;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    private PHPMailer $mail;

    /**
     * EmailService constructor.
     * Set up PHPMailer with config details.
     */
    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configureMail();
    }

    /**
     * Configure mail parameters.
     * Set SMTP, Host, Auth, Username, Password, Security and Port based on config.
     */
    private function configureMail() : void
    {
        $mailConfig = config('mail');
        
        if ($mailConfig['smtp']) {
            $this->mail->isSMTP();
        }

        $this->mail->CharSet    = "utf-8";
        $this->mail->Host       = $mailConfig['host'];
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $mailConfig['username'];
        $this->mail->Password   = $mailConfig['password'];
        $this->mail->SMTPSecure = $mailConfig['secure'];
        $this->mail->Port       = $mailConfig['port'];
    }

    /**
     * Get PHPMailer instance.
     * 
     * @return PHPMailer
     */
    public function mailer() : PHPMailer
    {
        return $this->mail;
    }

    /**
     * Send an email.
     * 
     * @param string $to      Recipient's email address.
     * @param string $subject Email subject.
     * @param string $body    Email body.
     * 
     * @throws Exception If email sending fails.
     */
    public function send(string $to, string $subject, string $body) : void
    {
        try {
            $this->mail->setFrom(config('mail.from'));
            $this->mail->addAddress($to);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            $this->mail->send();
        } catch (Exception $e) {
            throw new Exception($this->mail->ErrorInfo);
        }
    }

    public function handlePasswordReset(PasswordResetRequestedEvent $event)
    {
        try {
            $user = $event->getUser();

            $template = template()->render('other/reset_email', [
                'url' => url('reset/' . $event->getToken()->token),
                'name' => $user->name,
            ]);

            $this->send($user->email, __('auth.reset.subject'), $template);
        } catch (Exception $e) {
            logs()->error("Email reset failed: {$e->getMessage()}");
        }
    }

    public function handleRegistered(UserRegisteredEvent $event): void
    {
        try {
            $user = $event->getUser();

            $template = template()->render('other/confirm_email', [
                'url' => url('confirm/' . auth()->createVerificationToken($user)->token),
                'name' => $user->name,
            ]);

            $this->send($user->email, __('auth.confirmation.subject'), $template);
        } catch (Exception $e) {
            logs()->error("Email registered failed: {$e->getMessage()}");
        }
    }
}
