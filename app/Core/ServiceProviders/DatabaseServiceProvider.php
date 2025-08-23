<?php

namespace Flute\Core\ServiceProviders;

use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\DatabaseManager;
use Flute\Core\Support\AbstractServiceProvider;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            DatabaseManager::class => \DI\factory(function () {
                return DatabaseManager::getInstance();
            }),

            DatabaseConnection::class => \DI\factory(function (\DI\Container $c) {
                $manager = $c->get(DatabaseManager::class);

                return new DatabaseConnection($manager);
            }),

            "db.connection" => \DI\get(DatabaseConnection::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        try {
            $conn = $container->get(DatabaseConnection::class);

            if (is_installed()) {
                $conn->recompileIfNeeded();
            } else {
                $conn->recompileIfNeeded(true);
            }
        } catch (\Throwable $e) {
            logs('database')->warning('Early ORM init failed: ' . $e->getMessage());
        }
    }
}
