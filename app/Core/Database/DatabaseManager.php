<?php

namespace Flute\Core\Database;

use Flute\Core\App;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager as SpiralDatabaseManager;
use Exception;

class DatabaseManager
{
    protected App $app;
    protected SpiralDatabaseManager $dbal;

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

        $dbalConfig = new DatabaseConfig($config); // Create a database configuration object

        $this->dbal = new SpiralDatabaseManager($dbalConfig); // Create the Spiral Database Manager
    }

    /**
     * Get the Spiral Database Manager instance.
     *
     * @return SpiralDatabaseManager
     */
    public function getDbal(): SpiralDatabaseManager
    {
        return $this->dbal;
    }

    /**
     * Get a specific database connection.
     *
     * @param string $name The name of the database connection.
     * @return DatabaseInterface
     */
    public function database(string $name = 'default'): DatabaseInterface
    {
        return $this->dbal->database($name);
    }

    /**
     * КОСТЫЛЬ ЕБУЧИЙ НА ТАЙМЗОНУ ФИКСИМ ПОТОМ НА ФРОНТЕ СЧРОЧНВЫТЬВТЬ!
     */
    protected function convertTimezones()
    {
        if (!is_installed())
            return;

        foreach (config('database.connections') as $key => $connection) {
            $db = $connection;

            $db['timezone'] = (string) config('app.timezone');

            $db['options'] = [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8mb4"'];

            config()->set("database.connections.$key", $db);
        }
    }
}
