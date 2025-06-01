<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Modules\Auth\Events\PasswordResetRequestedEvent;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Flute\Core\Services\EmailService;
use Flute\Core\Support\AbstractServiceProvider;

class EmailServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            EmailService::class => \DI\create(),
            "email" => \DI\get(EmailService::class)
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if ($container->get('mail.smtp') && !is_cli()) {
            $events = $container->get('events');
            $email = $container->get('email');

            if ($container->get('auth.reset_password'))
                $events->addListener(PasswordResetRequestedEvent::NAME, [$email, 'handlePasswordReset']);

            if ($container->get('auth.registration.confirm_email'))
                $events->addListener(UserRegisteredEvent::NAME, [$email, 'handleRegistered']);
        }
    }
}