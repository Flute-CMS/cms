<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\DatabaseManager;

use Flute\Core\Support\AbstractServiceProvider;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    public function register( \DI\ContainerBuilder $containerBuilder ): void
    {
        $containerBuilder->addDefinitions([
            DatabaseManager::class => \DI\autowire(DatabaseManager::class),
            DatabaseConnection::class => \DI\autowire(DatabaseConnection::class)->lazy(),
            "db.connection" => \DI\get(DatabaseConnection::class),
        ]);
    }

    public function boot( \DI\Container $container ): void
    {}
}
