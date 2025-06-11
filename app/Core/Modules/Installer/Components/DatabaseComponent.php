<?php

namespace Flute\Core\Modules\Installer\Components;

use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Modules\Installer\Services\InstallerConfig;
use Flute\Core\Support\FluteComponent;
use Flute\Core\SystemHealth\Migrations\CheckPermissionsMigration;
use PDO;

class DatabaseComponent extends FluteComponent
{
    /**
     * @var array
     */
    public $drivers = [
        'mysql' => 'MySQL',
        'pgsql' => 'PostgreSQL',
        'sqlite' => 'SQLite',
    ];

    /**
     * @var string
     */
    public $driver = 'mysql';

    /**
     * @var string
     */
    public $host = 'localhost';

    /**
     * @var string
     */
    public $port = '3306';

    /**
     * @var string
     */
    public $database = '';

    /**
     * @var string
     */
    public $username = '';

    /**
     * @var string
     */
    public $password = '';

    /**
     * @var string
     */
    public $prefix = 'flute_';

    /**
     * @var string|null
     */
    public $errorMessage = null;

    /**
     * @var bool
     */
    public $isConnected = false;

    public function mount()
    {
        $connection = config('database.connections.default.connection');
        
        if ($connection->database) {
            $this->isConnected = true;
        }
    }

    /**
     * Test the database connection
     */
    public function testConnection()
    {
        try {
            $this->errorMessage = null;

            if (empty($this->host) && $this->driver !== 'sqlite') {
                $this->errorMessage = __('install.database.error_host_required');
                return;
            }

            if (empty($this->database)) {
                $this->errorMessage = __('install.database.error_database_required');
                return;
            }

            // For SQLite, we just need to make sure the directory exists
            if ($this->driver === 'sqlite') {
                $databaseDir = dirname(path('storage/database/'.$this->database));
                if (! is_dir($databaseDir) && ! mkdir($databaseDir, 0755, true)) {
                    $this->errorMessage = __('install.database.error_sqlite_dir');
                    return;
                }
                $this->isConnected = true;

                $installerConfig = app(InstallerConfig::class);
                $installerConfig->setParam('database', [
                    'driver' => $this->driver,
                    'host' => $this->host,
                    'port' => $this->port,
                    'database' => $this->database,
                    'username' => $this->username,
                    'password' => $this->password,
                    'prefix' => $this->prefix,
                ]);

                try {
                    $this->saveConfig();
                } catch (\Throwable $e) {
                    $this->errorMessage = $e->getMessage();
                    $this->isConnected = false;
                }

                return;
            }

            // Set up connection based on driver
            switch ($this->driver) {
                case 'mysql':
                    $dsn = "mysql:host={$this->host};port={$this->port};";
                    break;
                case 'pgsql':
                    $dsn = "pgsql:host={$this->host};port={$this->port};";
                    break;
                default:
                    $this->errorMessage = __('install.database.error_driver_not_supported');
                    return;
            }

            // Test connection to server without database first
            $pdo = new \PDO($dsn, $this->username, $this->password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);

            // Try to select the database
            try {
                $pdo = new \PDO($dsn."dbname={$this->database}", $this->username, $this->password, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ]);
            } catch (\PDOException $e) {
                // If database doesn't exist, create it (if we have permissions)
                if ($this->driver === 'mysql') {
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } elseif ($this->driver === 'pgsql') {
                    $pdo->exec("CREATE DATABASE \"{$this->database}\"");
                }
            }

            $this->isConnected = true;

            // Save configuration
            $installerConfig = app(InstallerConfig::class);
            $installerConfig->setParam('database', [
                'driver' => $this->driver,
                'host' => $this->host,
                'port' => $this->port,
                'database' => $this->database,
                'username' => $this->username,
                'password' => $this->password,
                'prefix' => $this->prefix,
            ]);

            $this->saveConfig();
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->isConnected = false;
        }
    }

    /**
     * Save the database configuration
     */
    protected function saveConfig()
    {
        $config = config('database');

        if ($this->driver === 'mysql') {
            $config['connections']['default'] =
                \Cycle\Database\Config\MySQLDriverConfig::__set_state([
                    'options' => [
                        'withDatetimeMicroseconds' => false,
                        'logInterpolatedQueries' => false,
                        'logQueryParameters' => false,
                    ],
                    'defaultOptions' => [
                        'withDatetimeMicroseconds' => false,
                        'logInterpolatedQueries' => false,
                        'logQueryParameters' => false,
                    ],
                    'connection' =>
                        \Cycle\Database\Config\MySQL\TcpConnectionConfig::__set_state([
                            'nonPrintableOptions' => [
                                0 => 'password',
                                1 => 'PWD',
                            ],
                            'user' => $this->username,
                            'password' => $this->password,
                            'options' => [
                                8 => 0,
                                3 => 2,
                                1002 => 'SET NAMES utf8mb4',
                                17 => false,
                            ],
                            'port' => (int) $this->port,
                            'database' => $this->database,
                            'host' => $this->host,
                            'charset' => 'utf8mb4',
                        ]),
                    'driver' => 'Cycle\\Database\\Driver\\MySQL\\MySQLDriver',
                    'reconnect' => true,
                    'timezone' => 'UTC',
                    'queryCache' => true,
                    'readonlySchema' => false,
                    'readonly' => false,
                ]);
        } elseif ($this->driver === 'pgsql') {
            $config['connections']['default'] =
                \Cycle\Database\Config\PostgresDriverConfig::__set_state([
                    'options' => [
                        'withDatetimeMicroseconds' => false,
                        'logInterpolatedQueries' => false,
                        'logQueryParameters' => false,
                    ],
                    'defaultOptions' => [
                        'withDatetimeMicroseconds' => false,
                        'logInterpolatedQueries' => false,
                        'logQueryParameters' => false,
                    ],
                    'connection' =>
                        \Cycle\Database\Config\Postgres\TcpConnectionConfig::__set_state([
                            'nonPrintableOptions' => [
                                0 => 'password',
                                1 => 'PWD',
                            ],
                            'user' => $this->username,
                            'password' => $this->password,
                            'options' => [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_CASE => PDO::CASE_NATURAL,
                            ],
                            'port' => (int) $this->port,
                            'database' => $this->database,
                            'host' => $this->host,
                            'schema' => 'public',
                        ]),
                    'driver' => 'Cycle\\Database\\Driver\\Postgres\\PostgresDriver',
                    'reconnect' => true,
                    'timezone' => 'UTC',
                    'queryCache' => true,
                    'readonlySchema' => false,
                    'readonly' => false,
                ]);
        } elseif ($this->driver === 'sqlite') {
            $config['connections']['default'] =
                \Cycle\Database\Config\SQLiteDriverConfig::__set_state([
                    'options' => [
                        'withDatetimeMicroseconds' => false,
                        'logInterpolatedQueries' => false,
                        'logQueryParameters' => false,
                    ],
                    'defaultOptions' => [
                        'withDatetimeMicroseconds' => false,
                        'logInterpolatedQueries' => false,
                        'logQueryParameters' => false,
                    ],
                    'connection' =>
                        \Cycle\Database\Config\SQLite\FileConnectionConfig::__set_state([
                            'nonPrintableOptions' => [
                                0 => 'password',
                                1 => 'PWD',
                            ],
                            'filename' => path('storage/database/'.$this->database),
                            'options' => [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            ],
                        ]),
                    'driver' => 'Cycle\\Database\\Driver\\SQLite\\SQLiteDriver',
                    'reconnect' => true,
                    'timezone' => 'UTC',
                    'queryCache' => true,
                    'readonlySchema' => false,
                    'readonly' => false,
                ]);
        }

        $config['databases']['default']['prefix'] = $this->prefix;

        config()->set('database', $config);
        config()->save();

        app(DatabaseConnection::class)->recompileOrmSchema(false);
        $this->createNecessaryThings();
    }

    protected function createNecessaryThings()
    {
        app(CheckPermissionsMigration::class)->run();

        if (! Role::findOne(['name' => 'admin']) && $permission = Permission::findOne(['name' => 'admin.boss'])) {
            $role = new Role;
            $role->name = 'admin';
            $role->priority = 2;
            $role->addPermission($permission);
            $role->save();
        }

        if (! Role::findOne(['name' => 'user'])) {
            $role = new Role;
            $role->name = 'user';
            $role->priority = 1;
            $role->save();
        }
    }

    /**
     * Render the component
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('installer::yoyo.database', [
            'drivers' => $this->drivers,
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'prefix' => $this->prefix,
            'errorMessage' => $this->errorMessage,
            'isConnected' => $this->isConnected,
        ]);
    }
}