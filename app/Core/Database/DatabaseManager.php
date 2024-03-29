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
}
