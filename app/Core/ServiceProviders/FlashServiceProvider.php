<?php

namespace Flute\Core\ServiceProviders;


use Flute\Core\Services\FlashService;
use Flute\Core\Support\AbstractServiceProvider;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FlashServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the services provided by the service provider.
     */
    public function register( \DI\ContainerBuilder $containerBuilder ): void
    {
        $containerBuilder->addDefinitions([
            FlashService::class => \DI\autowire(),
            FlashBagInterface::class => \DI\get(FlashService::class),
            "flash" => \DI\get(FlashService::class),
        ]);
    }

    public function boot(\DI\Container $container) : void
    {}
}
