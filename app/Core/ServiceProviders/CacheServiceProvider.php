<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Cache\CacheManager;

use Flute\Core\Support\AbstractServiceProvider;

class CacheServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            CacheManager::class => \DI\create(CacheManager::class)->method(
                'create', \DI\get('cache')
            ),
        ]);
    }

    public function boot(Container $container): void
    {
    }
}
