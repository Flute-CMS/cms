<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\SessionService;

use Flute\Core\Support\AbstractServiceProvider;

class SessionServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            SessionService::class => \DI\autowire(),
            "session" => \DI\get(SessionService::class),
        ]);
    }

    /**
     * Bootstrap the service provider.
     *
     * This method is called after all services are registered.
     * It is used to boot the SessionService and initialize the language settings.
     */
    public function boot( \DI\Container $container ): void
    {
        $container->get(SessionService::class)->start();
    }
}
