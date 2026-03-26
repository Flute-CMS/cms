<?php

namespace Flute\Core\Modules\Notifications\Listeners;

use Flute\Core\Database\Entities\UserDevice;
use Flute\Core\Modules\Auth\Events\UserLoggedInEvent;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Flute\Core\Modules\Auth\Events\UserVerifiedEvent;
use Flute\Core\Modules\Payments\Events\PaymentSuccessEvent;
use Throwable;

/**
 * Listens to core events and sends notifications via templates.
 */
class CoreNotificationListener
{
    /**
     * Handle user registration — send welcome notification.
     */
    public static function onUserRegistered(UserRegisteredEvent $event): void
    {
        try {
            $user = $event->getUser();

            notify('core.welcome', $user, [
                'name' => $user->name,
            ]);
        } catch (Throwable $e) {
            logs()->error('Notification [core.welcome] failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle login — detect new device and notify.
     */
    public static function onUserLoggedIn(UserLoggedInEvent $event): void
    {
        try {
            $user = $event->getUser();
            $request = app(\Flute\Core\Support\FluteRequest::class);

            $ip = $request->getClientIp();
            $userAgent = $request->headers->get('User-Agent', '');
            $deviceName = self::parseDeviceName($userAgent);

            $existing = UserDevice::findOne([
                'user_id' => $user->id,
                'deviceDetails' => $userAgent,
                'ip' => $ip,
            ]);

            if ($existing === null) {
                notify('core.new_device_login', $user, [
                    'ip' => $ip,
                    'device' => $deviceName,
                    'time' => date('d.m.Y H:i'),
                ]);
            }
        } catch (Throwable $e) {
            logs()->error('Notification [core.new_device_login] failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful payment.
     */
    public static function onPaymentSuccess(PaymentSuccessEvent $event): void
    {
        try {
            $invoice = $event->getInvoice();
            $user = $event->getUser();

            notify('core.payment_success', $user, [
                'amount' => number_format($invoice->originalAmount, 2),
                'gateway' => $invoice->gateway,
                'transaction_id' => $invoice->transactionId,
            ]);
        } catch (Throwable $e) {
            logs()->error('Notification [core.payment_success] failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle email verified.
     */
    public static function onUserVerified(UserVerifiedEvent $event): void
    {
        try {
            notify('core.email_verified', $event->getUser());
        } catch (Throwable $e) {
            logs()->error('Notification [core.email_verified] failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse a readable device name from User-Agent string.
     */
    protected static function parseDeviceName(string $userAgent): string
    {
        $browser = 'Unknown';
        $os = 'Unknown';

        // Detect browser
        if (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Edg')) {
            $browser = 'Edge';
        } elseif (str_contains($userAgent, 'OPR') || str_contains($userAgent, 'Opera')) {
            $browser = 'Opera';
        } elseif (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        }

        // Detect OS
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac OS')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $os = 'iOS';
        }

        return "{$browser} / {$os}";
    }
}
