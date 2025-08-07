<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\NavbarService;
use Flute\Core\Support\AbstractServiceProvider;

class NavbarServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            NavbarService::class => \DI\autowire(),
            "navbar" => \DI\get(NavbarService::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
    }
}
