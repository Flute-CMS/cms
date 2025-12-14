<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Services\ThrottlerService;
use Flute\Core\Support\AbstractServiceProvider;

class ThrottlerServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            ThrottlerService::class => \DI\create(),
            "throttler" => \DI\get(ThrottlerService::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
    }
}
