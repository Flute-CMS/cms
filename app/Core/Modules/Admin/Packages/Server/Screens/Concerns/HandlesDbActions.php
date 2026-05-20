<?php

namespace Flute\Admin\Packages\Server\Screens\Concerns;

use Flute\Admin\Packages\MainSettings\Services\MainSettingsPackageService;
use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Core\Services\DatabaseService;
use PDO;
use Throwable;

trait HandlesDbActions
{
    public function addDbConnection()
    {
        if (!$this->server) {
            $this->flashMessage(__('admin-server.messages.save_server_first'), 'error');

            return;
        }

        $data = request()->input();

        foreach (['custom_mod', 'dbname'] as $selectField) {
            if (isset($data[$selectField]) && is_array($data[$selectField])) {
                $data[$selectField] = $data[$selectField][0] ?? null;
            }
        }

        $validation = $this->validate([
            'custom_mod' => ['required', 'string', 'max-str-len:255'],
            'dbname' => ['required', 'string', 'max-str-len:255'],
        ], $data);

        if (!$validation) {
            return;
        }

        $data['mod'] = $data['custom_mod'];

        $additional = [];

        if ($data['mod'] === 'custom') {
            $customValidation = $this->validate([
                'custom_settings__name' => ['required', 'string', 'max-str-len:255'],
                'custom_settings__json' => ['nullable', 'string'],
            ], $data);

            if (!$customValidation) {
                return;
            }

            $driverName = $data['custom_settings__name'];

            if (!empty($data['custom_settings__json'])) {
                try {
                    $additional = json_decode($data['custom_settings__json'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->flashMessage(__('admin-server.messages.invalid_json'), 'error');

                        return;
                    }
                } catch (Throwable) {
                    $this->flashMessage(__('admin-server.messages.invalid_json'), 'error');

                    return;
                }
            }
        } else {
            $driverName = $data['mod'];

            $driverParams = $this->getDriverParams($driverName);

            foreach ($driverParams as $key => $param) {
                if (isset($data[$key])) {
                    $additional[$key] = $data[$key];
                }
            }
        }

        try {
            $connection = new DatabaseConnection();
            $connection->mod = $driverName;
            $connection->dbname = $data['dbname'];
            $connection->additional = json_encode($additional);
            $connection->server = $this->server;
            $connection->save();
            DatabaseService::flushModesCache();

            $this->dbConnections = DatabaseConnection::query()->where('server_id', $this->serverId)->fetchAll();
            $this->flashMessage(__('admin-server.messages.connection_add_success'), 'success');
            $this->closeModal();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function addDatabase()
    {
        if (!user()->can('admin.boss')) {
            $this->flashMessage(__('def.permission_denied'), 'error');

            return;
        }

        $data = request()->input();

        if (!$this->validate([
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
        ], $data)) {
            return;
        }

        $connectionTest = app(MainSettingsPackageService::class)->testDatabaseConnection(
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

            return;
        }

        $databaseName = $data['databaseName'];
        $driver = $data['driver'];
        $persistent = filter_var($data['persistent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $initSql = trim((string) ( $data['init_sql'] ?? '' ));
        $compression = filter_var($data['compression'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $reconnect = filter_var($data['reconnect'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $connectTimeout = isset($data['connect_timeout']) ? (int) $data['connect_timeout'] : null;
        $readTimeout = isset($data['read_timeout']) ? (int) $data['read_timeout'] : null;
        $writeTimeout = isset($data['write_timeout']) ? (int) $data['write_timeout'] : null;

        $databases = config()->get('database.databases', []);
        if (isset($databases[$databaseName])) {
            $this->flashMessage(__('admin-main-settings.messages.database_exists'), 'error');

            return;
        }

        config()->set("database.databases.{$databaseName}", [
            'connection' => $databaseName,
            'prefix' => $data['prefix'] ?? '',
        ]);

        if ($driver === 'mysql') {
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
                $mysqlReadKey = defined('PDO::MYSQL_ATTR_READ_TIMEOUT')
                    ? constant('PDO::MYSQL_ATTR_READ_TIMEOUT')
                    : null;
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
            $connectionConfig = new \Cycle\Database\Config\MySQLDriverConfig(
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
        } elseif ($driver === 'postgres') {
            $options = [];
            if ($persistent) {
                $options[PDO::ATTR_PERSISTENT] = true;
            }
            if ($connectTimeout !== null) {
                $options[PDO::ATTR_TIMEOUT] = $connectTimeout;
            }
            $connectionConfig = new \Cycle\Database\Config\PostgresDriverConfig(
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
        } else {
            $this->flashMessage(__('admin-main-settings.messages.unsupported_driver'), 'error');

            return;
        }

        config()->set("database.connections.{$databaseName}", $connectionConfig);

        try {
            config()->save();
            $this->invalidateConfig('database');
            $this->flashMessage(__('admin-main-settings.messages.add_database_success'));
            $this->closeModal();
        } catch (Throwable $e) {
            $this->flashMessage(__('admin-main-settings.messages.add_database_error') . $e->getMessage(), 'error');
        }
    }

    public function updateDbConnection()
    {
        $data = request()->input();
        $connectionId = $this->modalParams->get('connectionId');
        $connection = rep(DatabaseConnection::class)->findByPK($connectionId);

        if (!$connection) {
            $this->flashMessage(__('admin-server.messages.connection_not_found'), 'error');

            return;
        }

        foreach (['custom_mod', 'dbname'] as $selectField) {
            if (isset($data[$selectField]) && is_array($data[$selectField])) {
                $data[$selectField] = $data[$selectField][0] ?? null;
            }
        }

        $validation = $this->validate([
            'custom_mod' => ['required', 'string', 'max-str-len:255'],
            'dbname' => ['required', 'string', 'max-str-len:255'],
        ], $data);

        if (!$validation) {
            return;
        }

        $data['mod'] = $data['custom_mod'];

        $additional = [];

        if ($data['mod'] === 'custom') {
            $customValidation = $this->validate([
                'custom_settings__name' => ['required', 'string', 'max-str-len:255'],
                'custom_settings__json' => ['nullable', 'string'],
            ], $data);

            if (!$customValidation) {
                return;
            }

            $driverName = $data['custom_settings__name'];

            if (!empty($data['custom_settings__json'])) {
                try {
                    $additional = json_decode($data['custom_settings__json'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->flashMessage(__('admin-server.messages.invalid_json'), 'error');

                        return;
                    }
                } catch (Throwable) {
                    $this->flashMessage(__('admin-server.messages.invalid_json'), 'error');

                    return;
                }
            }
        } else {
            $driverName = $data['mod'];

            $prepared = $this->prepareData($data);

            $driverParams = $this->getDriverParams($driverName);

            foreach ($driverParams as $key => $param) {
                if (isset($prepared[$key])) {
                    $additional[$key] = $prepared[$key];
                }
            }

            $validate = $this->validate($driverParams, $additional);

            if (!$validate) {
                return;
            }
        }

        try {
            $connection->mod = $driverName;
            $connection->dbname = $data['dbname'];
            $connection->additional = json_encode($additional);
            $connection->save();
            DatabaseService::flushModesCache();

            $this->dbConnections = DatabaseConnection::query()->where('server_id', $this->serverId)->fetchAll();
            $this->flashMessage(__('admin-server.messages.connection_update_success'), 'success');
            $this->closeModal();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function deleteDbConnection()
    {
        $connectionId = request()->input('connectionId');

        try {
            $this->serversService->deleteDbConnection($connectionId);
            $this->flashMessage(__('admin-server.messages.connection_delete_success'), 'success');
            $this->dbConnections = DatabaseConnection::query()->where('server_id', $this->serverId)->fetchAll();
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
}
