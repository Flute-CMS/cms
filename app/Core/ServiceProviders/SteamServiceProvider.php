<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Cache\CacheManager;
use Flute\Core\Services\SteamService;
use Flute\Core\Support\AbstractServiceProvider;

class SteamServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder) : void
    {
        $containerBuilder->addDefinitions([
            SteamService::class => \DI\create(SteamService::class)->constructor(
                \DI\get('app.steam_api'),
                \DI\get(CacheManager::class)
            ),
            'steam' => \DI\get(SteamService::class),
        ]);
    }

    public function boot(\DI\Container $container) : void {}
}
