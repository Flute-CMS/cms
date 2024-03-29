<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\EventNotifications;
use Flute\Core\Support\AbstractServiceProvider;

class EventNotificationsServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the services provided by the service provider.
     */
    public function register( \DI\ContainerBuilder $containerBuilder ): void
    {
    }

    public function boot(\DI\Container $container) : void
    {
        is_installed() && $container->get(EventNotifications::class)->listen();
    }
}
