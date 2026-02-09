<?php

namespace Flute\Core\Modules\Notifications\Providers;

use Flute\Core\Modules\Auth\Events\UserLoggedInEvent;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Flute\Core\Modules\Auth\Events\UserVerifiedEvent;
use Flute\Core\Modules\Notifications\Listeners\CoreNotificationListener;
use Flute\Core\Modules\Notifications\Services\NotificationService;
use Flute\Core\Modules\Notifications\Services\NotificationTemplateService;
use Flute\Core\Modules\Payments\Events\PaymentSuccessEvent;
use Flute\Core\Support\AbstractServiceProvider;

class NotificationServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            NotificationService::class => \DI\autowire(),
            "notification" => \DI\get(NotificationService::class),

            NotificationTemplateService::class => \DI\autowire(),
            "notification_templates" => \DI\get(NotificationTemplateService::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (is_installed()) {
            $container->get(NotificationService::class);

            /** @var NotificationTemplateService $templateService */
            $templateService = $container->get(NotificationTemplateService::class);

            $coreProvider = new CoreNotificationProvider();
            $templateService->registerProvider($coreProvider);
            $templateService->registerFromProvider($coreProvider);

            $this->loadRoutesFrom(cms_path('Notifications/Routes/notifications.php'));

            $this->addNamespace('notifications', cms_path('Modules/Notifications/Resources/views'));

            events()->addDeferredListener(UserRegisteredEvent::NAME, [CoreNotificationListener::class, 'onUserRegistered']);
            events()->addDeferredListener(UserLoggedInEvent::NAME, [CoreNotificationListener::class, 'onUserLoggedIn']);
            events()->addDeferredListener(PaymentSuccessEvent::NAME, [CoreNotificationListener::class, 'onPaymentSuccess']);
            events()->addDeferredListener(UserVerifiedEvent::NAME, [CoreNotificationListener::class, 'onUserVerified']);
        }
    }
}
