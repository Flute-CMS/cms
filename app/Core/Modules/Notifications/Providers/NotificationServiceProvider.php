<?php

namespace Flute\Core\Modules\Notifications\Providers;

use Flute\Core\Modules\Notifications\Services\NotificationService;
use Flute\Core\Support\AbstractServiceProvider;

class NotificationServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            NotificationService::class => \DI\autowire(),
            "notification" => \DI\get(NotificationService::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (is_installed()) {
            $container->get(NotificationService::class);

            $this->loadRoutesFrom(cms_path('Notifications/Routes/notifications.php'));
        }
    }
}
