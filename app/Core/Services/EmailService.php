<?php

namespace Flute\Core\Services;

use Exception;
use Flute\Core\Modules\Auth\Events\PasswordResetRequestedEvent;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

/**
 * Class EmailService
 *
 * Provides functionality for sending emails and handling related events.
 */
class EmailService
{
    /**
     * @var MailerInterface|null
     */
    private ?MailerInterface $mailer = null;

    /**
     * @var array|null
     * Mail configuration cache.
     */
    private ?array $mailConfig = null;

    /**
     * Configures email parameters.
     * Initialization occurs only on the first call.
     */
    private function configureMail(): void
    {
        if ($this->mailer !== null) {
            return;
        }

        $this->mailConfig = config('mail');

        $transport = new EsmtpTransport(
            $this->mailConfig['host'],
            $this->mailConfig['port'],
            $this->mailConfig['secure'] === 'ssl' // SSL or TLS
        );

        if ($this->mailConfig['smtp']) {
            $transport->setUsername($this->mailConfig['username']);
            $transport->setPassword($this->mailConfig['password']);
        }

        $this->mailer = new Mailer($transport);
    }

    /**
     * Sends an email.
     *
     * @param string $to      The recipient's email address.
     * @param string $subject The email subject.
     * @param string $body    The email body.
     *
     * @throws Exception If the email sending fails.
     */
    public function send(string $to, string $subject, string $body): void
    {
        try {
            $this->configureMail();

            $fromEmail = $this->mailConfig['from'] ?? 'no-reply@' . config('app.name');

            $email = (new Email())
                ->from($fromEmail)
                ->to($to)
                ->subject($subject)
                ->html($body);

            $this->mailer->send($email);
        } catch (Exception $e) {
            logs()->error("Email send failed: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Handles the password reset event.
     *
     * @param PasswordResetRequestedEvent $event
     */
    public function handlePasswordReset(PasswordResetRequestedEvent $event): void
    {
        try {
            $user = $event->getUser();

            $template = template()->render('flute::emails.reset', [
                'url' => url('reset/' . $event->getToken()->token),
                'name' => $user->name,
            ]);

            $this->send($user->email, __('auth.reset.subject'), $template);
        } catch (Exception $e) {
            logs()->error("Email reset failed: {$e->getMessage()}");
        }
    }

    /**
     * Handles the user registration event.
     *
     * @param UserRegisteredEvent $event
     */
    public function handleRegistered(UserRegisteredEvent $event): void
    {
        if (!config('auth.registration.confirm_email')) {
            return;
        }

        try {
            $user = $event->getUser();

            $verificationToken = auth()->createVerificationToken($user)->token;

            $template = template()->render('flute::emails.confirmation', [
                'url' => url('confirm/' . $verificationToken),
                'name' => $user->name,
            ]);

            $this->send($user->email, __('auth.confirmation.subject'), $template);
        } catch (Exception $e) {
            logs()->error("Email registration failed: {$e->getMessage()}");
        }
    }
}
