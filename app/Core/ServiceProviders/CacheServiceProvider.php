<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use DI\ContainerBuilder;
use Flute\Core\Cache\CacheManager;
use Flute\Core\Cache\Contracts\CacheInterface;
use Flute\Core\Support\AbstractServiceProvider;
use Symfony\Contracts\Cache\CacheInterface as SymfonyCacheInterface;

class CacheServiceProvider extends AbstractServiceProvider
{
    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            CacheManager::class => \DI\create(CacheManager::class)->constructor(
                \DI\get('logger')
            )->method(
                'create',
                \DI\get('cache')
            ),
            CacheInterface::class => \DI\factory(function (Container $container) {
                $cacheManager = $container->get(CacheManager::class);

                return $cacheManager->getAdapter();
            }),
            SymfonyCacheInterface::class => \DI\get(CacheInterface::class),
        ]);
    }

    public function boot(Container $container): void
    {
    }
}
