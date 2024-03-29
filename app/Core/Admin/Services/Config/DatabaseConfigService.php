<?php

namespace Flute\Core\Admin\Services\Config;

use Flute\Core\Admin\Support\AbstractConfigService;
use Flute\Core\Database\Entities\DatabaseConnection;
use Spiral\Database\Driver\MySQL\MySQLDriver;
use Spiral\Database\Driver\Postgres\PostgresDriver;
use Spiral\Database\Driver\SQLite\SQLiteDriver;
use Symfony\Component\HttpFoundation\Response;

class DatabaseConfigService extends AbstractConfigService
{
    public function updateConfig(array $params): Response
    {
        $func = $params['func'] ?? 'updateGeneral';
        $config = config('database');

        try {
            switch ($func) {
                case 'editdb':
                    $this->editDatabase($config, $params);
                    break;
                case 'createdb':
                    $this->createDatabase($config, $params);
                    break;
                case 'deletedb':
                    $this->deleteDatabase($config, $params);
                    break;
                case 'editconnection':
                    $this->editConnection($config, $params);
                    break;
                case 'addconnection':
                    $this->addConnection($config, $params);
                    break;
                case 'deleteconnection':
                    $this->deleteConnection($config, $params);
                    break;
                case 'updateGeneral':
                default:
                    $this->updateGeneralConfig($config, $params);
                    break;
            }

            $this->fileSystemService->updateConfig($this->getConfigPath('database'), $config);
            return response()->success(__('def.success'));
        } catch (\Exception $e) {
            logs()->error($e);
            return response()->error(500, $e->getMessage() ?? __('def.unknown_error'));
        }
    }

    protected function deleteDatabase(array &$config, array $params)
    {
        $dbName = $params['dbName'];
        if (!isset($config['databases'][$dbName])) {
            throw new \Exception(__('admin.db_not_found'));
        }

        if (count($config['databases']) <= 1) {
            throw new \Exception(__('admin.cannot_delete_last_db'));
        }

        if ($config['default'] === $dbName) {
            throw new \Exception(__('admin.cannot_delete_default_db'));
        }

        $dbs = rep(DatabaseConnection::class)->findAll([
            'dbname' => $dbName
        ]);

        foreach ($dbs as $db) {
            $db->dbname = $params['dbName'];

            transaction($db, 'delete');
        }

        unset($config['databases'][$dbName]);
    }

    protected function deleteConnection(array &$config, array $params)
    {
        $connectionName = $params['dbConnectionName'];
        if (!isset($config['connections'][$connectionName])) {
            throw new \Exception(__('admin.connection_not_found', ['connectionName' => $connectionName]));
        }

        if (count($config['connections']) <= 1) {
            throw new \Exception(__('admin.cannot_delete_last_connection'));
        }

        // Проверяем, не используется ли подключение в качестве стандартного
        $defaultDbConnection = $config['databases'][$config['default']]['connection'] ?? null;
        if ($defaultDbConnection === $connectionName) {
            throw new \Exception(__('admin.cannot_delete_default_connection'));
        }

        // Удалить все базы данных, использующие это подключение
        foreach ($config['databases'] as $dbName => $dbConfig) {
            if ($dbConfig['connection'] === $connectionName) {
                unset($config['databases'][$dbName]);
            }
        }

        unset($config['connections'][$connectionName]);
    }

    protected function updateGeneralConfig(array &$config, array $params)
    {
        $config['default'] = $params['defaultDatabase'];
        $config['debug'] = $this->b($params['debugMode']);
    }

    protected function editDatabase(array &$config, array $params)
    {
        $dbName = $params['lastDbName'];

        // Проверяем, существует ли БД
        if (!isset($config['databases'][$dbName])) {
            throw new \Exception(__('admin.db_not_found'));
        }

        $dbs = rep(DatabaseConnection::class)->findAll([
            'dbname' => $dbName
        ]);

        foreach ($dbs as $db) {
            $db->dbname = $params['dbName'];

            transaction($db)->run();
        }

        unset($config['databases'][$dbName]);

        $config['databases'][$params['dbName']] = [
            'connection' => $params['defaultDatabase'],
            'prefix' => $params['tablePrefix'],
        ];
    }

    protected function createDatabase(array &$config, array $params)
    {
        $dbName = $params['dbName'];

        // Проверяем, не существует ли уже БД
        if (isset($config['databases'][$dbName])) {
            throw new \Exception(__('admin.db_already_exists'));
        }

        $config['databases'][$dbName] = [
            'connection' => $params['defaultDatabase'],
            'prefix' => $params['tablePrefix'],
        ];
    }

    protected function editConnection(array &$config, array $params)
    {
        $lastConnectionName = $params['lastDbConnectionName'];
        $newConnectionName = $params['dbConnectionName'];

        if (!isset($config['connections'][$lastConnectionName])) {
            throw new \Exception(__('admin.connection_not_found', ['connectionName' => $lastConnectionName]));
        }

        if (!$this->testDbConnection($params)) {
            throw new \Exception(__('admin.db_error'));
        }

        // Удаляем старое подключение
        unset($config['connections'][$lastConnectionName]);

        // Добавляем новое подключение
        $config['connections'][$newConnectionName] = $this->getConnectionParams($params);

        // Обновляем название подключения во всех базах данных, которые его использовали
        foreach ($config['databases'] as $dbName => $dbConfig) {
            if ($dbConfig['connection'] === $lastConnectionName) {
                $config['databases'][$dbName]['connection'] = $newConnectionName;
            }
        }
    }

    protected function addConnection(array &$config, array $params)
    {
        $connectionName = $params['dbConnectionName'];
        if (isset($config['connections'][$connectionName])) {
            throw new \Exception(__('admin.connection_already_exists', ['connectionName' => $connectionName]));
        }

        if (!$this->testDbConnection($params)) {
            throw new \Exception(__('admin.db_error'));
        }

        $config['connections'][$connectionName] = $this->getConnectionParams($params);
    }

    protected function testDbConnection(array $params): bool
    {
        try {
            $dsn = $this->getDsn($params);
            $pdo = new \PDO($dsn, $params['dbUser'], $params['dbPassword']);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    protected function getDsn(array $params): string
    {
        switch ($params['dbDriver']) {
            case 'sqlite':
                return "sqlite:" . $params['dbName'];
            case 'postgresql':
                return "pgsql:host=" . $params['dbHost'] . ";port=" . $params['dbPort'] . ";dbname=" . $params['dbName'];
            default: // MySQL
                return "mysql:host=" . $params['dbHost'] . ";port=" . $params['dbPort'] . ";dbname=" . $params['dbName'];
        }
    }

    protected function getConnectionParams(array $params)
    {
        return [
            'driver' => $this->getDriverInstance($params['dbDriver']),
            'connection' => $params['dbDriver'] . ':host=' . $params['dbHost'] . ';port=' . $params['dbPort'] . ';dbname=' . $params['dbName'],
            'username' => $params['dbUser'],
            'password' => $params['dbPassword'],
        ];
    }

    protected function getDriverInstance(string $driver): string
    {
        switch ($driver) {
            case 'sqlite':
                return SQLiteDriver::class;
            case 'postgresql':
                return PostgresDriver::class;
            default:
                return MySQLDriver::class;
        }
    }
}
