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

            \Cycle\ORM\ORMInterface::class => \DI\factory(function (\DI\Container $c) {
                if (!function_exists('is_installed') || !is_installed()) {
                    throw new \DI\NotFoundException('ORM is not available before installation');
                }

                /** @var DatabaseConnection $conn */
                $conn = $c->get(DatabaseConnection::class);

                return $conn->getOrm();
            }),

            \Cycle\ORM\ORM::class => \DI\get(\Cycle\ORM\ORMInterface::class),
        ]);
    }

    public function boot(\DI\Container $container): void
    {
        if (is_installed()) {
            try {
                $conn = $container->get(DatabaseConnection::class);
                $conn->recompileIfNeeded();
            } catch (\Throwable $e) {
                logs('database')->warning('Early ORM init failed: ' . $e->getMessage());
            }
        }
    }
}
