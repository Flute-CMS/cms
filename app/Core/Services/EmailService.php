<?php

namespace Flute\Core\Services;

use Exception;
use Flute\Core\Modules\Auth\Events\PasswordResetRequestedEvent;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

/**
 * Class EmailService
 *
 * Provides functionality for sending emails and handling related events.
 */
class EmailService
{
    /**
     */
    private ?MailerInterface $mailer = null;

    /**
     * @var array|null
     * Mail configuration cache.
     */
    private ?array $mailConfig = null;

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

            $defaultDomain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $fromEmail = $this->mailConfig['from'] ?? ('no-reply@' . $defaultDomain);

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

    /**
     * Configures email parameters.
     * Initialization occurs only on the first call.
     */
    private function configureMail(): void
    {
        if ($this->mailer !== null) {
            return;
        }

        $this->mailConfig = (array) config('mail');

        if (empty($this->mailConfig['smtp'])) {
            throw new Exception('SMTP is disabled.');
        }

        $host = (string) ($this->mailConfig['host'] ?? '');
        if ($host === '') {
            throw new Exception('SMTP host is not configured.');
        }

        $port = (int) ($this->mailConfig['port'] ?? 0);
        if ($port < 1 || $port > 65535) {
            $port = 0;
        }

        $secure = strtolower((string) ($this->mailConfig['secure'] ?? 'tls'));
        $scheme = $secure === 'ssl' ? 'smtps' : 'smtp';

        $username = (string) ($this->mailConfig['username'] ?? '');
        $password = (string) ($this->mailConfig['password'] ?? '');

        $timeout = $this->mailConfig['timeout'] ?? 5;
        $timeout = is_numeric($timeout) ? (float) $timeout : 5.0;
        if ($timeout <= 0) {
            $timeout = 5.0;
        }

        $query = [
            'timeout' => $timeout,
        ];

        $authMode = (string) ($this->mailConfig['auth_mode'] ?? '');
        if ($authMode !== '') {
            $query['auth_mode'] = $authMode;
        }

        // STARTTLS: smtp://... ?encryption=tls
        if ($secure === 'tls') {
            $query['encryption'] = 'tls';
        }

        $dsn = $scheme . '://';
        if ($username !== '') {
            $dsn .= rawurlencode($username);
            if ($password !== '') {
                $dsn .= ':' . rawurlencode($password);
            }
            $dsn .= '@';
        }
        $dsn .= $host;
        if ($port > 0) {
            $dsn .= ':' . $port;
        }
        if ($query) {
            $dsn .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
    }
}
