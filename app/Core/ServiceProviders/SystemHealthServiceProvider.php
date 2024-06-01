<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Services\System\SystemService;

class SystemHealthServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            SystemService::class => \DI\create(),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        $container->get(SystemService::class)->run();
    }
}
