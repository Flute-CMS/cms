<?php

namespace Flute\Core\Database;

use Flute\Core\App;
use Cycle\Database\Config\DatabaseConfig as CycleDatabaseConfig;
use Cycle\Database\DatabaseManager as CycleDatabaseManager;
use Exception;

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
        $this->configure();
    }

    /**
     * @throws Exception
     */
    protected function configure(): void
    {
        $config = new CycleDatabaseConfig(config("database"));

        if (!$config) {
            throw new Exception('Database configuration not found.');
        }

        $this->dbal = new CycleDatabaseManager($config);
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
}
