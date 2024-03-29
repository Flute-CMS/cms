<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Auth\Events\PasswordResetRequestedEvent;
use Flute\Core\Auth\Events\UserRegisteredEvent;
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
        if (!$container->get('auth.registration.confirm_email') || !$container->get('auth.reset_password') || !$container->get('installer.finished'))
            return;

        $events = $container->get('events');
        $email = $container->get('email');
        
        if (app('auth.reset_password'))
            $events->addListener(PasswordResetRequestedEvent::NAME, [$email, 'handlePasswordReset']);

        if (app('auth.registration.confirm_email'))
            $events->addListener(UserRegisteredEvent::NAME, [$email, 'handleRegistered']);
    }
}