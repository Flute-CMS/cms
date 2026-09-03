<?php

namespace Flute\Core\Modules\Installer\Controllers\Concerns;

use Flute\Core\Database\DatabaseCapabilities;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\FluteRequest;
use Flute\Core\SystemHealth\Migrations\CheckPermissionsMigration;
use PDO;
use PDOException;
use Throwable;

trait HandlesDatabaseStep
{
    #[Route('/install/2/driver', name: 'installer.step2.driver', methods: ['POST'])]
    public function changeDriver(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        $driver = $request->input('driver', 'mysql');
        $defaultPorts = ['mysql' => '3306', 'pgsql' => '5432', 'sqlite' => ''];

        return $this->renderStep(2, [
            'driver' => $driver,
            'host' => $request->input('host', 'localhost'),
            'port' => $defaultPorts[$driver] ?? '3306',
            'database' => $request->input('database', ''),
            'username' => $request->input('username', ''),
            'password' => $request->input('password', ''),
            'prefix' => $request->input('prefix', 'flute_'),
            'isConnected' => false,
            'errorMessage' => null,
        ]);
    }

    #[Route('/install/2/test', name: 'installer.step2.test', methods: ['POST'])]
    public function testDatabaseConnection(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        $driver = $request->input('driver', 'mysql');
        $host = $request->input('host', 'localhost');
        $port = $request->input('port', '3306');
        $database = $request->input('database', '');
        $username = $request->input('username', '');
        $password = $request->input('password', '');
        $prefix = $request->input('prefix', 'flute_');

        $errorMessage = null;
        $isConnected = false;
        $fieldErrors = [];
        $dbWarnings = [];

        try {
            if ($validation = $this->validateDatabaseInputs($request, $driver, $host, $database, $port, $fieldErrors)) {
                return $validation;
            }

            if ($driver === 'sqlite') {
                return $this->handleSqliteSetup(
                    $request,
                    $driver,
                    $host,
                    $port,
                    $database,
                    $username,
                    $password,
                    $prefix,
                );
            }

            $dsn = match ($driver) {
                'mysql' => "mysql:host={$host};port={$port};",
                'pgsql' => "pgsql:host={$host};port={$port};",
                default => null,
            };

            if ($dsn === null) {
                $errorMessage = __('install.database.error_driver_not_supported');

                return $this->renderDatabaseStep($request, $isConnected, $errorMessage);
            }

            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $capabilities = DatabaseCapabilities::fromPdo($pdo, $driver);

            if (!$capabilities->meetsMinimumVersion()) {
                $errorMessage = __('install.database.error_version_too_old', [
                    'server' => $capabilities->getServerLabel(),
                    'current' => $capabilities->getCleanVersion(),
                    'required' => $capabilities->getMinimumVersion(),
                ]);

                return $this->renderDatabaseStep($request, false, $errorMessage);
            }

            $dbWarnings = $capabilities->getWarnings();

            try {
                $pdo = new PDO($dsn . "dbname={$database}", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } catch (PDOException $e) {
                $safeDbName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $database);
                if ($safeDbName === '' || $safeDbName !== $database) {
                    throw new \InvalidArgumentException('Invalid database name');
                }

                if ($driver === 'mysql') {
                    $pdo->exec(
                        "CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
                    );
                } elseif ($driver === 'pgsql') {
                    $pdo->exec('CREATE DATABASE ' . $this->quotePostgresIdentifier($safeDbName));
                }
            }

            $this->installerConfig->setParam('database', [
                'driver' => $driver,
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'prefix' => $prefix,
            ]);

            try {
                $this->saveDatabaseConfig($driver, $host, $port, $database, $username, $password, $prefix);
                $isConnected = true;
            } catch (Throwable $e) {
                logs('installer')->warning('Installer database migration failed: ' . $e->getMessage());
                $errorMessage = __('install.database.error_migration');
                $isConnected = false;
            }
        } catch (Throwable $e) {
            logs('installer')->warning('Installer database step failed: ' . $e->getMessage());
            $errorMessage = __('install.database.error_migration');
            $isConnected = false;
        }

        return $this->renderDatabaseStep($request, $isConnected, $errorMessage, $fieldErrors, $dbWarnings);
    }

    private function validateDatabaseInputs(
        FluteRequest $request,
        string $driver,
        string $host,
        string $database,
        string $port,
        array &$fieldErrors,
    ): mixed {
        if (empty($host) && $driver !== 'sqlite') {
            $fieldErrors['host'] = __('install.database.error_host_required');

            return $this->renderDatabaseStep($request, false, null, $fieldErrors);
        }

        if (empty($database)) {
            $fieldErrors['database'] = __('install.database.error_database_required');

            return $this->renderDatabaseStep($request, false, null, $fieldErrors);
        }

        if ($driver !== 'sqlite' && !preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
            $fieldErrors['database'] = __('install.database.error_invalid_name');

            return $this->renderDatabaseStep($request, false, null, $fieldErrors);
        }

        if ($driver !== 'sqlite' && !preg_match('/^[a-zA-Z0-9._\-]+$/', $host)) {
            $fieldErrors['host'] = __('install.database.error_host_required');

            return $this->renderDatabaseStep($request, false, null, $fieldErrors);
        }

        if (!empty($port) && !ctype_digit((string) $port)) {
            $fieldErrors['port'] = __('install.database.error_host_required');

            return $this->renderDatabaseStep($request, false, null, $fieldErrors);
        }

        return null;
    }

    private function handleSqliteSetup(
        FluteRequest $request,
        string $driver,
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
        string $prefix,
    ): mixed {
        $databaseDir = dirname(path('storage/database/' . $database));
        if (!is_dir($databaseDir) && !mkdir($databaseDir, 0o755, true)) {
            return $this->renderDatabaseStep($request, false, __('install.database.error_sqlite_dir'));
        }
        $isConnected = true;
        $errorMessage = null;

        $this->installerConfig->setParam('database', [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'prefix' => $prefix,
        ]);

        try {
            $this->saveDatabaseConfig($driver, $host, $port, $database, $username, $password, $prefix);
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
            $isConnected = false;
        }

        return $this->renderDatabaseStep($request, $isConnected, $errorMessage);
    }

    #[Route('/install/2', name: 'installer.step2.save', methods: ['POST'])]
    public function saveDatabaseStep(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        $dbConfig = $this->installerConfig->getParams('database');
        if ($dbConfig) {
            $this->installerConfig->setCurrentStep(3);
            config()->save();

            return response()->redirect(route('installer.step', ['id' => 3]));
        }

        return response()->redirect(route('installer.step', ['id' => 2]));
    }

    protected function getDatabaseData(): array
    {
        $drivers = [
            'mysql' => 'MySQL',
            'pgsql' => 'PostgreSQL',
            'sqlite' => 'SQLite',
        ];

        $driver = 'mysql';
        $host = 'localhost';
        $port = '3306';
        $database = '';
        $username = '';
        $password = '';
        $prefix = 'flute_';
        $isConnected = false;

        try {
            $connection = config('database.connections.default.connection');

            if ($connection && isset($connection->database) && $connection->database) {
                $isConnected = true;

                $dbConfig = $this->installerConfig->getParams('database');
                if ($dbConfig) {
                    $driver = $dbConfig['driver'] ?? $driver;
                    $host = $dbConfig['host'] ?? $host;
                    $port = $dbConfig['port'] ?? $port;
                    $database = $dbConfig['database'] ?? $database;
                    $username = $dbConfig['username'] ?? $username;
                    $password = $dbConfig['password'] ?? $password;
                    $prefix = $dbConfig['prefix'] ?? $prefix;
                }
            }
        } catch (Throwable $e) {
            logs('installer')->debug('Installer database status check skipped before configuration is ready', [
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'drivers' => $drivers,
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'prefix' => $prefix,
            'errorMessage' => null,
            'isConnected' => $isConnected,
            'fieldErrors' => [],
        ];
    }

    protected function renderDatabaseStep(
        FluteRequest $request,
        bool $isConnected,
        ?string $errorMessage,
        array $fieldErrors = [],
        array $dbWarnings = [],
    ): mixed {
        return $this->renderStep(2, [
            'driver' => $request->input('driver', 'mysql'),
            'host' => $request->input('host', 'localhost'),
            'port' => $request->input('port', '3306'),
            'database' => $request->input('database', ''),
            'username' => $request->input('username', ''),
            'password' => $request->input('password', ''),
            'prefix' => $request->input('prefix', 'flute_'),
            'isConnected' => $isConnected,
            'errorMessage' => $errorMessage,
            'fieldErrors' => $fieldErrors,
            'dbWarnings' => $dbWarnings,
        ]);
    }

    protected function quotePostgresIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException('Invalid PostgreSQL database name');
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    protected function saveDatabaseConfig(
        string $driver,
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
        string $prefix,
    ): void {
        $config = config('database');

        if ($driver === 'mysql') {
            $config['connections']['default'] = \Cycle\Database\Config\MySQLDriverConfig::__set_state([
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
                'connection' => \Cycle\Database\Config\MySQL\TcpConnectionConfig::__set_state([
                    'nonPrintableOptions' => [
                        0 => 'password',
                        1 => 'PWD',
                    ],
                    'user' => $username,
                    'password' => $password,
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_CASE => PDO::CASE_NATURAL,
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_TIMEOUT => 5,
                    ],
                    'port' => (int) $port,
                    'database' => $database,
                    'host' => $host,
                    'charset' => 'utf8mb4',
                ]),
                'driver' => 'Cycle\\Database\\Driver\\MySQL\\MySQLDriver',
                'reconnect' => true,
                'timezone' => 'UTC',
                'queryCache' => true,
                'readonlySchema' => false,
                'readonly' => false,
            ]);
        } elseif ($driver === 'pgsql') {
            $config['connections']['default'] = \Cycle\Database\Config\PostgresDriverConfig::__set_state([
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
                'connection' => \Cycle\Database\Config\Postgres\TcpConnectionConfig::__set_state([
                    'nonPrintableOptions' => [
                        0 => 'password',
                        1 => 'PWD',
                    ],
                    'user' => $username,
                    'password' => $password,
                    'options' => [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_CASE => PDO::CASE_NATURAL,
                    ],
                    'port' => (int) $port,
                    'database' => $database,
                    'host' => $host,
                    'schema' => 'public',
                ]),
                'driver' => 'Cycle\\Database\\Driver\\Postgres\\PostgresDriver',
                'reconnect' => true,
                'timezone' => 'UTC',
                'queryCache' => true,
                'readonlySchema' => false,
                'readonly' => false,
            ]);
        } elseif ($driver === 'sqlite') {
            $config['connections']['default'] = \Cycle\Database\Config\SQLiteDriverConfig::__set_state([
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
                'connection' => \Cycle\Database\Config\SQLite\FileConnectionConfig::__set_state([
                    'nonPrintableOptions' => [
                        0 => 'password',
                        1 => 'PWD',
                    ],
                    'filename' => path('storage/database/' . $database),
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

        $config['databases']['default']['prefix'] = $prefix;

        config()->set('database', $config);
        config()->save();

        app(DatabaseConnection::class)->recompileOrmSchema(false);
        $this->createNecessaryRolesAndPermissions();
    }

    protected function createNecessaryRolesAndPermissions(): void
    {
        app(CheckPermissionsMigration::class)->run();

        if (!Role::findOne(['name' => 'admin']) && ( $permission = Permission::findOne(['name' => 'admin.boss']) )) {
            $role = new Role();
            $role->name = 'admin';
            $role->priority = 2;
            $role->addPermission($permission);
            $role->save();
        }

        if (!Role::findOne(['name' => 'user'])) {
            $role = new Role();
            $role->name = 'user';
            $role->priority = 1;
            $role->save();
        }
    }
}
