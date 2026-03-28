<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Rcon\RconService;
use Flute\Core\ServerQuery\ServerQueryService;
use Flute\Core\Services\FluteApiClient;
use Flute\Core\Support\AbstractServiceProvider;

/**
 * Explicit bindings for game query / RCON and Flute API client so compiled PHP-DI
 * graphs and lazy resolution always resolve these classes.
 */
class GameServerServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            ServerQueryService::class => \DI\autowire(),
            RconService::class => \DI\autowire(),
            FluteApiClient::class => \DI\autowire(),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
    }
}
