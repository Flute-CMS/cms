<?php

namespace Flute\Core\Database\Concerns;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\Exception\MigrationException;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;

trait HandlesMigrations
{
    public function rollbackMigrations(string $directory)
    {
        $this->setupMigrations();

        $migrations = $this->migrator->getMigrations();

        foreach ($migrations as $migration) {
            $this->migrator->rollback();
        }
    }

    /**
     * @throws MigrationException
     */
    public function runMigrations(string $directory)
    {
        $this->setupMigrations();

        $migrations = $this->migrator->getMigrations();

        foreach ($migrations as $migration) {
            try {
                while ($this->migrator->run() !== null) {
                    continue;
                }
            } catch (MigrationException $e) {
                $this->migrator->rollback();

                throw $e;
            }
        }
    }

    protected function setupMigrations(): void
    {
        $config = new MigrationConfig([
            'directory' => path('storage/migrations'),
            'table' => 'migrations',
            'safe' => true,
        ]);

        $fileRepository = new FileRepository($config);
        $this->migrator = new Migrator($config, $this->getMigrationDbal(), $fileRepository);
        $this->migrator->configure();
    }

    private function getMigrationDbal(): DatabaseManager
    {
        if ($this->migrationDbal) {
            return $this->migrationDbal;
        }

        $config = config('database');
        if (!is_array($config)) {
            $this->migrationDbal = $this->dbal ?? $this->databaseManager->getDbal();

            return $this->migrationDbal;
        }

        $defaultDb = $config['default'] ?? 'default';
        $databases = $config['databases'] ?? [];
        $connections = $config['connections'] ?? $config['drivers'] ?? [];

        $defaultDatabaseConfig = $databases[$defaultDb] ?? null;
        if (!is_array($defaultDatabaseConfig)) {
            $this->migrationDbal = $this->dbal ?? $this->databaseManager->getDbal();

            return $this->migrationDbal;
        }

        $defaultConnectionName =
            $defaultDatabaseConfig['connection'] ?? $defaultDatabaseConfig['write'] ?? $defaultDatabaseConfig['driver']
                ?? $defaultDb;

        $filteredConnections = [];
        if (is_string($defaultConnectionName) && isset($connections[$defaultConnectionName])) {
            $filteredConnections[$defaultConnectionName] = $connections[$defaultConnectionName];
        }

        $filteredConfig = [
            'default' => $defaultDb,
            'aliases' => $config['aliases'] ?? [],
            'databases' => [$defaultDb => $defaultDatabaseConfig],
            'connections' => $filteredConnections,
        ];

        $this->migrationDbal = new DatabaseManager(new DatabaseConfig($filteredConfig));

        return $this->migrationDbal;
    }
}
