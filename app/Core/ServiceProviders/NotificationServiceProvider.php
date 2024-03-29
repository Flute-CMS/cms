<?php 

namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\NotificationService;

use Flute\Core\Support\AbstractServiceProvider;

class NotificationServiceProvider extends AbstractServiceProvider
{
    public function register( \DI\ContainerBuilder $containerBuilder ): void
    {
        $containerBuilder->addDefinitions([
            NotificationService::class => \DI\autowire(),
            "notification" => \DI\get(NotificationService::class),
        ]);
    }

    public function boot( \DI\Container $container ): void
    {
        is_installed() && $container->get('notification');
    }
}
