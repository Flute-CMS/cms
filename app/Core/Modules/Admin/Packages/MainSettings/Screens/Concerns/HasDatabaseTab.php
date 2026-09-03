<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use PDO;
use Throwable;

use function constant;
use function defined;

trait HasDatabaseTab
{
    public function addDatabase()
    {
        $data = request()->input();

        if (!$this->validateDatabaseInput($data) || !$this->testDriverConnection($data)) {
            return;
        }

        $databaseName = $data['databaseName'];

        $databases = config()->get('database.databases', []);
        if (isset($databases[$databaseName])) {
            $this->flashMessage(__('admin-main-settings.messages.database_exists'), 'error');

            return;
        }

        $connectionConfig = $this->buildDriverConfig($data);
        if ($connectionConfig === null) {
            return;
        }

        config()->set("database.databases.{$databaseName}", [
            'connection' => $databaseName,
            'prefix' => $data['prefix'] ?? '',
        ]);
        config()->set("database.connections.{$databaseName}", $connectionConfig);

        try {
            config()->save();
            $this->invalidateConfig('database');
            $this->flashMessage(__('admin-main-settings.messages.add_database_success'));
            $this->databaseConnections = $this->configService->initDatabases();

            $this->closeModal();
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-main-settings.messages.add_database_error') . $e->getMessage(), 'error');
        }
    }

    public function changeDatabase()
    {
        $data = request()->input();

        if (!$this->validateDatabaseInput($data) || !$this->testDriverConnection($data)) {
            return;
        }

        $databaseName = $data['databaseName'];

        $databases = config('database.databases');
        if (!isset($databases[$databaseName])) {
            $this->flashMessage(__('admin-main-settings.messages.database_not_found'), 'error');

            return;
        }

        $connectionName = $databases[$databaseName]['connection'] ?? $databaseName;

        $connectionConfig = $this->buildDriverConfig($data);
        if ($connectionConfig === null) {
            return;
        }

        config()->set("database.databases.{$databaseName}.prefix", $data['prefix'] ?? '');
        config()->set("database.connections.{$connectionName}", $connectionConfig);

        try {
            config()->save();
            $this->invalidateConfig('database');
            $this->flashMessage(__('admin-main-settings.messages.edit_database_success'));
            $this->closeModal();
            $this->databaseConnections = $this->configService->initDatabases();
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-main-settings.messages.edit_database_error') . $e->getMessage(), 'error');
        }
    }

    public function removeDatabase()
    {
        $data = request()->input();

        if (!$this->validate([
            'databaseId' => ['required', 'string', 'not-in:default'],
        ], request()->input())) {
            return;
        }

        $databaseId = $data['databaseId'];

        $databases = config()->get('database.databases', []);
        $connections = config()->get('database.connections', []);

        if (!isset($databases[$databaseId])) {
            $this->flashMessage(__('admin-main-settings.messages.database_not_found'), 'error');

            return;
        }

        $connectionName = $databases[$databaseId]['connection'] ?? $databaseId;

        unset($databases[$databaseId]);
        unset($connections[$connectionName]);

        config()->set('database.databases', $databases);
        config()->set('database.connections', $connections);

        try {
            config()->save();
            $this->invalidateConfig('database');
            $this->flashMessage(__('admin-main-settings.messages.remove_database_success'));
            $this->databaseConnections = $this->configService->initDatabases();
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-main-settings.messages.remove_database_error'), 'error');
        }
    }

    private function validateDatabaseInput(array $data): bool
    {
        return $this->validate([
            'driver' => ['required', 'string', 'in:mysql,postgres'],
            'databaseName' => ['required', 'string', 'not-in:default'],
            'host' => ['required', 'string'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'user' => ['required', 'string'],
            'database' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'persistent' => ['nullable'],
            'init_sql' => ['nullable', 'string'],
            'compression' => ['nullable'],
            'reconnect' => ['nullable'],
            'connect_timeout' => ['nullable', 'integer', 'min:0', 'max:300'],
            'read_timeout' => ['nullable', 'integer', 'min:0', 'max:300'],
            'write_timeout' => ['nullable', 'integer', 'min:0', 'max:300'],
            'prefix' => ['nullable', 'string'],
        ], $data);
    }

    private function testDriverConnection(array $data): bool
    {
        $connectionTest = $this->configService->testDatabaseConnection(
            $data['driver'],
            $data['host'],
            (int) $data['port'],
            $data['database'],
            $data['user'],
            $data['password'] ?? null,
        );

        if ($connectionTest !== true) {
            $this->flashMessage(
                __('admin-main-settings.messages.connection_test_failed') . ': ' . $connectionTest,
                'error',
            );

            return false;
        }

        return true;
    }

    private function buildDriverConfig(array $data): \Cycle\Database\Config\MySQLDriverConfig|\Cycle\Database\Config\PostgresDriverConfig|null
    {
        $driver = $data['driver'];
        $persistent = filter_var($data['persistent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $initSql = trim((string) ( $data['init_sql'] ?? '' ));
        $compression = filter_var($data['compression'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $reconnect = filter_var($data['reconnect'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $connectTimeout = isset($data['connect_timeout']) ? (int) $data['connect_timeout'] : null;
        $readTimeout = isset($data['read_timeout']) ? (int) $data['read_timeout'] : null;
        $writeTimeout = isset($data['write_timeout']) ? (int) $data['write_timeout'] : null;

        if ($driver === 'mysql') {
            $options = $this->buildMysqlOptions(
                $persistent,
                $initSql,
                $compression,
                $connectTimeout,
                $readTimeout,
                $writeTimeout,
            );

            return new \Cycle\Database\Config\MySQLDriverConfig(
                connection: new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                    options: $options,
                ),
                reconnect: $reconnect,
                timezone: 'Asia/Yekaterinburg',
                queryCache: true,
                readonlySchema: true,
            );
        }

        if ($driver === 'postgres') {
            $options = [];
            if ($persistent) {
                $options[PDO::ATTR_PERSISTENT] = true;
            }
            if ($connectTimeout !== null) {
                $options[PDO::ATTR_TIMEOUT] = $connectTimeout;
            }

            return new \Cycle\Database\Config\PostgresDriverConfig(
                connection: new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
                    database: $data['database'],
                    host: $data['host'],
                    port: $data['port'],
                    user: $data['user'],
                    password: $data['password'],
                    options: $options,
                ),
                reconnect: $reconnect,
                schema: 'public',
                queryCache: true,
                readonlySchema: true,
            );
        }

        $this->flashMessage(__('admin-main-settings.messages.unsupported_driver'), 'error');

        return null;
    }

    private function buildMysqlOptions(
        bool $persistent,
        string $initSql,
        bool $compression,
        ?int $connectTimeout,
        ?int $readTimeout,
        ?int $writeTimeout,
    ): array {
        $options = [];

        $mysqlInitKey = defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? constant('PDO::MYSQL_ATTR_INIT_COMMAND') : null;
        if ($mysqlInitKey !== null) {
            $options[$mysqlInitKey] = $initSql !== '' ? $initSql : 'SET NAMES utf8mb4';
        }
        if ($persistent) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }
        if ($compression) {
            $mysqlCompressKey = defined('PDO::MYSQL_ATTR_COMPRESS') ? constant('PDO::MYSQL_ATTR_COMPRESS') : null;
            if ($mysqlCompressKey !== null) {
                $options[$mysqlCompressKey] = true;
            }
        }
        if ($connectTimeout !== null) {
            $mysqlConnectKey = defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                ? constant('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                : null;
            if ($mysqlConnectKey !== null) {
                $options[$mysqlConnectKey] = $connectTimeout;
            }
            $options[PDO::ATTR_TIMEOUT] = $connectTimeout;
        }
        if ($readTimeout !== null) {
            $mysqlReadKey = defined('PDO::MYSQL_ATTR_READ_TIMEOUT') ? constant('PDO::MYSQL_ATTR_READ_TIMEOUT') : null;
            if ($mysqlReadKey !== null) {
                $options[$mysqlReadKey] = $readTimeout;
            }
        }
        if ($writeTimeout !== null) {
            $mysqlWriteKey = defined('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                ? constant('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                : null;
            if ($mysqlWriteKey !== null) {
                $options[$mysqlWriteKey] = $writeTimeout;
            }
        }

        return $options;
    }
}
