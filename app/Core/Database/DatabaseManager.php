<?php

namespace Flute\Core\Database;

use Cycle\Database\Config\DatabaseConfig as CycleDatabaseConfig;
use Cycle\Database\DatabaseManager as CycleDatabaseManager;
use Exception;
use Flute\Core\App;

class DatabaseManager
{
    protected App $app;
    protected CycleDatabaseManager $dbal;

    // Static instance for singleton pattern
    protected static ?self $instance = null;

    /**
     * Get the singleton instance of DatabaseManager
     *
     * @param App|null $app
     * @return self
     * @throws Exception
     */
    public static function getInstance(?App $app = null): self
    {
        if (self::$instance === null) {
            if ($app === null) {
                $app = app();
            }
            self::$instance = new self($app);
        }

        return self::$instance;
    }

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
