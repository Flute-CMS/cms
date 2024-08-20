<?php

namespace Flute\Core\Database;

use Flute\Core\App;
use Cycle\Database\Config\DatabaseConfig as CycleDatabaseConfig;
use Cycle\Database\DatabaseManager as CycleDatabaseManager;
use Exception;
use Spiral\Database\Driver\MySQL\MySQLDriver;
use Spiral\Database\Driver\Postgres\PostgresDriver;
use Cycle\Database;
use Cycle\Database\Config;

class DatabaseManager
{
    protected App $app;
    protected CycleDatabaseManager $dbal;

    /**
     * @throws Exception
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->configure(); // Configure the database connection
    }

    /**
     * @throws Exception
     */
    protected function configure(): void
    {
        $this->convertTimezones();

        $config = config("database"); // Get the database configuration

        if (!$config) {
            throw new Exception('Database configuration not found.');
        }

        // Convert configuration if necessary
        $dbalConfig = $this->convertConfig($config);

        // Create the Cycle Database Manager
        $this->dbal = new CycleDatabaseManager($dbalConfig);
    }

    /**
     * Convert the configuration from Cycle ORM 1.x format to 2.x format if needed.
     *
     * @param array $config
     * @return CycleDatabaseConfig
     */
    protected function convertConfig(array $config): CycleDatabaseConfig
    {
        $connections = [];

        foreach ($config['connections'] as $name => $connection) {
            switch ($connection['driver']) {
                case MySQLDriver::class:
                    $connections[$name] = new Config\MySQLDriverConfig(
                        connection: new Config\MySQL\TcpConnectionConfig(
                            database: $this->extractDatabaseFromDsn($connection['connection']),
                            host: $connection['host'] ?? '127.0.0.1',
                            port: $connection['port'] ?? 3306,
                            user: $connection['username'] ?? 'root',
                            password: $connection['password'] ?? ''
                        ),
                        queryCache: $connection['queryCache'] ?? false
                    );
                    break;
                case PostgresDriver::class:
                    $connections[$name] = new Config\PostgresDriverConfig(
                        connection: new Config\Postgres\TcpConnectionConfig(
                            database: $connection['database'] ?? $this->extractDatabaseFromDsn($connection['connection']),
                            host: $connection['host'] ?? '127.0.0.1',
                            port: $connection['port'] ?? 5432,
                            user: $connection['username'] ?? 'postgres',
                            password: $connection['password'] ?? ''
                        ),
                        queryCache: $connection['queryCache'] ?? false
                    );
                    break;
            }
        }

        $config['connections'] = $connections;

        return new CycleDatabaseConfig($config);
    }

    private function extractDatabaseFromDsn(string $dsn): ?string
    {
        $parsed = parse_url($dsn);
        if (isset($parsed['path'])) {
            return ltrim($parsed['path'], '/');
        }
        return null;
    }

    /**
     * Convert specific driver options to match Cycle 2.x requirements.
     *
     * @param array $options
     * @return array
     */
    protected function convertDriverOptions(array $options): array
    {
        // Example: Update charset options if required by the new version
        if (isset($options[\PDO::MYSQL_ATTR_INIT_COMMAND])) {
            $options[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES "utf8mb4" COLLATE "utf8mb4_unicode_ci"';
        }

        return $options;
    }

    /**
     * Get the Cycle Database Manager instance.
     *
     * @return CycleDatabaseManager
     */
    public function getDbal(): CycleDatabaseManager
    {
        return $this->dbal;
    }

    /**
     * Get a specific database connection.
     *
     * @param string $name The name of the database connection.
     * @return \Cycle\Database\DatabaseInterface
     */
    public function database(string $name = 'default'): \Cycle\Database\DatabaseInterface
    {
        return $this->dbal->database($name);
    }

    /**
     * Temporary timezone fix, refactor later.
     */
    protected function convertTimezones(): void
    {
        if (!is_installed()) {
            return;
        }

        foreach (config('database.connections') as $key => $connection) {
            $db = $connection;

            $db['timezone'] = (string) config('app.timezone');

            $db['options'] = [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8mb4"'];

            config()->set("database.connections.$key", $db);
        }
    }
}
