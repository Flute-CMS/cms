<?php

namespace Flute\Core\Services;

use Exception;
use Flute\Core\Auth\Events\PasswordResetRequestedEvent;
use Flute\Core\Auth\Events\UserRegisteredEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;

    /**
     * EmailService constructor.
     */
    public function __construct()
    {
        $this->configureMail();
    }

    /**
     * Configure mail parameters programmatically.
     */
    private function configureMail(): void
    {
        $mailConfig = config('mail');

        $transport = new EsmtpTransport(
            $mailConfig['host'],
            $mailConfig['port'],
            $mailConfig['secure'] === 'ssl' // SSL or TLS
        );

        if ($mailConfig['smtp']) {
            $transport->setUsername($mailConfig['username']);
            $transport->setPassword($mailConfig['password']);
        }

        $this->mailer = new Mailer($transport);
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
    public function send(string $to, string $subject, string $body): void
    {
        try {
            $email = (new Email())
                ->from(config('mail.from'))
                ->to($to)
                ->subject($subject)
                ->html($body);

            $this->mailer->send($email);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
