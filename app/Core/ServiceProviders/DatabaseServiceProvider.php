<?php

namespace Flute\Core\ServiceProviders;

use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\DatabaseManager;
use Flute\Core\Support\AbstractServiceProvider;
use Throwable;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->addDefinitions([
            DatabaseManager::class => \DI\factory(static fn () => DatabaseManager::getInstance()),

            DatabaseConnection::class => \DI\factory(static function (\DI\Container $c) {
                $manager = $c->get(DatabaseManager::class);

                return new DatabaseConnection($manager);
            }),

            ORM::class => \DI\factory(static fn (\DI\Container $c) => $c->get(DatabaseConnection::class)->getOrm()),
            ORMInterface::class => \DI\get(ORM::class),

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
        } catch (Throwable $e) {
            logs('database')->warning('Early ORM init failed: ' . $e->getMessage());
        }
    }
}
