<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Logging\LogRendererManager;
use Flute\Core\Support\AbstractServiceProvider;

class LoggingServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            LogRendererManager::class => \DI\autowire(),
            "flute.logging.renderer" => \DI\get(LogRendererManager::class),
        ]);
    }


    public function boot(\DI\Container $container) : void {}
}