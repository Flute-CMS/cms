<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use PDO;

use function constant;
use function defined;

trait HasDatabaseModals
{
    public function addDatabaseModal(Repository $parameters)
    {
        $defaultConnection = config('database.connections.default');

        $explode = explode('\\', $defaultConnection->driver);

        $driver = str_replace('driver', '', strtolower(end($explode)));
        $supportsMysqlOptions = $driver === 'mysql';
        $supportsReconnect = in_array($driver, ['mysql', 'postgres'], true);

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                ButtonGroup::make('driver')
                    ->options([
                        'mysql' => [
                            'label' => 'MySQL',
                            'icon' => 'ph.bold.database-bold',
                        ],
                        'postgres' => [
                            'label' => 'PostgreSQL',
                            'icon' => 'ph.bold.database-bold',
                        ],
                    ])
                    ->value($driver)
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_driver'))
                ->required(),
            LayoutFactory::field(
                Input::make('databaseName')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.database_name')),
            )
                ->label(__('admin-main-settings.labels.database_name'))
                ->required(),
            LayoutFactory::field(
                Input::make('host')
                    ->type('text')
                    ->value($defaultConnection->connection->host)
                    ->placeholder(__('admin-main-settings.placeholders.db_host')),
            )
                ->label(__('admin-main-settings.labels.host'))
                ->required(),
            LayoutFactory::field(
                Input::make('port')
                    ->type('number')
                    ->value($defaultConnection->connection->port)
                    ->placeholder(__('admin-main-settings.placeholders.db_port')),
            )
                ->label(__('admin-main-settings.labels.port'))
                ->required(),
            LayoutFactory::field(
                Input::make('user')->type('text')->placeholder(__('admin-main-settings.placeholders.db_user')),
            )
                ->label(__('admin-main-settings.labels.user'))
                ->required(),
            LayoutFactory::field(
                Input::make('database')->type('text')->placeholder(__('admin-main-settings.placeholders.db_database')),
            )
                ->label(__('admin-main-settings.labels.database'))
                ->required(),
            LayoutFactory::field(
                Input::make('password')
                    ->type('password')
                    ->placeholder(__('admin-main-settings.placeholders.db_password')),
            )->label(__('admin-main-settings.labels.password')),
            LayoutFactory::field(
                ButtonGroup::make('persistent')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value('0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.persistent_connections'))
                ->popover(__('admin-main-settings.popovers.persistent_connections')),
            LayoutFactory::field(
                Input::make('init_sql')->type('text')->placeholder(__('admin-main-settings.placeholders.db_init_sql')),
            )
                ->label(__('admin-main-settings.labels.db_init_sql'))
                ->popover(__('admin-main-settings.popovers.db_init_sql'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                ButtonGroup::make('compression')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value('0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_compression'))
                ->popover(__('admin-main-settings.popovers.db_compression'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                ButtonGroup::make('reconnect')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value('1')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_reconnect'))
                ->popover(__('admin-main-settings.popovers.db_reconnect'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('connect_timeout')
                    ->type('number')
                    ->value(5)
                    ->placeholder(__('admin-main-settings.placeholders.db_connect_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_connect_timeout'))
                ->popover(__('admin-main-settings.popovers.db_connect_timeout'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('read_timeout')
                    ->type('number')
                    ->value(30)
                    ->placeholder(__('admin-main-settings.placeholders.db_read_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_read_timeout'))
                ->popover(__('admin-main-settings.popovers.db_read_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('write_timeout')
                    ->type('number')
                    ->value(30)
                    ->placeholder(__('admin-main-settings.placeholders.db_write_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_write_timeout'))
                ->popover(__('admin-main-settings.popovers.db_write_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('prefix')->type('text')->placeholder(__('admin-main-settings.placeholders.db_prefix')),
            )
                ->label(__('admin-main-settings.labels.prefix'))
                ->popover(__('admin-main-settings.popovers.prefix'))
                ->small(__('admin-main-settings.examples.prefix')),
        ])
            ->method('addDatabase')
            ->title(__('admin-main-settings.modals.add_database'))
            ->applyButton(__('admin-main-settings.buttons.add'))
            ->right();
    }

    public function editDatabaseModal(Repository $parameters)
    {
        $databaseId = $parameters->get('databaseId');

        $dbConfig = config('database');
        $database = $dbConfig['databases'][$databaseId] ?? null;

        if (!$database) {
            $this->flashMessage(__('admin-main-settings.messages.database_not_found'), 'error');

            return;
        }

        if ($databaseId === 'default') {
            $this->flashMessage(__('admin-main-settings.messages.cannot_edit_default_db'), 'error');

            return;
        }

        $connectionName = $database['connection'];
        $connectionConfig = $dbConfig['connections'][$connectionName] ?? null;

        if (!$connectionConfig) {
            $this->flashMessage(__('admin-main-settings.messages.connection_not_found'), 'error');

            return;
        }

        if ($connectionConfig instanceof \Cycle\Database\Config\MySQLDriverConfig) {
            $driver = 'mysql';
            $tcpConnection = $connectionConfig->connection;
        } elseif ($connectionConfig instanceof \Cycle\Database\Config\PostgresDriverConfig) {
            $driver = 'postgres';
            $tcpConnection = $connectionConfig->connection;
        } else {
            $driver = 'mysql';
            $tcpConnection = $connectionConfig->connection;
        }

        $supportsMysqlOptions = $driver === 'mysql';
        $supportsReconnect = in_array($driver, ['mysql', 'postgres'], true);

        $persistent = false;
        if (isset($tcpConnection->options) && is_array($tcpConnection->options)) {
            $persistent = (bool) ( $tcpConnection->options[PDO::ATTR_PERSISTENT] ?? false );
        }

        $initSql = null;
        $compression = false;
        if (isset($tcpConnection->options) && is_array($tcpConnection->options)) {
            $mysqlInitKey = defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? constant('PDO::MYSQL_ATTR_INIT_COMMAND') : null;
            if ($mysqlInitKey !== null) {
                $initSql = $tcpConnection->options[$mysqlInitKey] ?? null;
            }
            $mysqlCompressKey = defined('PDO::MYSQL_ATTR_COMPRESS') ? constant('PDO::MYSQL_ATTR_COMPRESS') : null;
            if ($mysqlCompressKey !== null) {
                $compression = (bool) ( $tcpConnection->options[$mysqlCompressKey] ?? false );
            }
        }

        $reconnect = (bool) ( $connectionConfig->reconnect ?? true );

        $connectTimeout = null;
        $readTimeout = null;
        $writeTimeout = null;
        if (isset($tcpConnection->options) && is_array($tcpConnection->options)) {
            $mysqlConnectKey = defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                ? constant('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')
                : null;
            $mysqlReadKey = defined('PDO::MYSQL_ATTR_READ_TIMEOUT') ? constant('PDO::MYSQL_ATTR_READ_TIMEOUT') : null;
            $mysqlWriteKey = defined('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                ? constant('PDO::MYSQL_ATTR_WRITE_TIMEOUT')
                : null;

            $connectTimeout = $mysqlConnectKey !== null ? $tcpConnection->options[$mysqlConnectKey] ?? null : null;
            $connectTimeout ??= $tcpConnection->options[PDO::ATTR_TIMEOUT] ?? null;

            if ($mysqlReadKey !== null) {
                $readTimeout = $tcpConnection->options[$mysqlReadKey] ?? null;
            }
            if ($mysqlWriteKey !== null) {
                $writeTimeout = $tcpConnection->options[$mysqlWriteKey] ?? null;
            }
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                ButtonGroup::make('driver')
                    ->options([
                        'mysql' => [
                            'label' => 'MySQL',
                            'icon' => 'ph.bold.database-bold',
                        ],
                        'postgres' => [
                            'label' => 'PostgreSQL',
                            'icon' => 'ph.bold.database-bold',
                        ],
                    ])
                    ->value($driver)
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_driver'))
                ->required(),
            LayoutFactory::field(
                Input::make('databaseName')
                    ->type('text')
                    ->value($databaseId)
                    ->placeholder(__('admin-main-settings.placeholders.database_name'))
                    ->readonly()
                    ->required(),
            )
                ->label(__('admin-main-settings.labels.database_name'))
                ->required(),
            LayoutFactory::field(
                Input::make('host')
                    ->type('text')
                    ->value($tcpConnection->host)
                    ->placeholder(__('admin-main-settings.placeholders.db_host')),
            )
                ->label(__('admin-main-settings.labels.host'))
                ->required(),
            LayoutFactory::field(
                Input::make('port')
                    ->type('number')
                    ->value($tcpConnection->port)
                    ->placeholder(__('admin-main-settings.placeholders.db_port')),
            )
                ->label(__('admin-main-settings.labels.port'))
                ->required(),
            LayoutFactory::field(
                Input::make('user')
                    ->type('text')
                    ->value($tcpConnection->user)
                    ->placeholder(__('admin-main-settings.placeholders.db_user')),
            )
                ->label(__('admin-main-settings.labels.user'))
                ->required(),
            LayoutFactory::field(
                Input::make('database')
                    ->type('text')
                    ->value($tcpConnection->database)
                    ->placeholder(__('admin-main-settings.placeholders.db_database')),
            )
                ->label(__('admin-main-settings.labels.database'))
                ->required(),
            LayoutFactory::field(
                Input::make('password')
                    ->type('password')
                    ->value($tcpConnection->password)
                    ->placeholder(__('admin-main-settings.placeholders.db_password')),
            )->label(__('admin-main-settings.labels.password')),
            LayoutFactory::field(
                ButtonGroup::make('persistent')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value($persistent ? '1' : '0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.persistent_connections'))
                ->popover(__('admin-main-settings.popovers.persistent_connections')),
            LayoutFactory::field(
                Input::make('init_sql')
                    ->type('text')
                    ->value($initSql)
                    ->placeholder(__('admin-main-settings.placeholders.db_init_sql')),
            )
                ->label(__('admin-main-settings.labels.db_init_sql'))
                ->popover(__('admin-main-settings.popovers.db_init_sql'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                ButtonGroup::make('compression')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value($compression ? '1' : '0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_compression'))
                ->popover(__('admin-main-settings.popovers.db_compression'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                ButtonGroup::make('reconnect')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value($reconnect ? '1' : '0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_reconnect'))
                ->popover(__('admin-main-settings.popovers.db_reconnect'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('connect_timeout')
                    ->type('number')
                    ->value($connectTimeout ?? 5)
                    ->placeholder(__('admin-main-settings.placeholders.db_connect_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_connect_timeout'))
                ->popover(__('admin-main-settings.popovers.db_connect_timeout'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('read_timeout')
                    ->type('number')
                    ->value($readTimeout ?? 30)
                    ->placeholder(__('admin-main-settings.placeholders.db_read_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_read_timeout'))
                ->popover(__('admin-main-settings.popovers.db_read_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('write_timeout')
                    ->type('number')
                    ->value($writeTimeout ?? 30)
                    ->placeholder(__('admin-main-settings.placeholders.db_write_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_write_timeout'))
                ->popover(__('admin-main-settings.popovers.db_write_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('prefix')
                    ->type('text')
                    ->value($database['prefix'])
                    ->placeholder(__('admin-main-settings.placeholders.db_prefix')),
            )
                ->label(__('admin-main-settings.labels.prefix'))
                ->popover(__('admin-main-settings.popovers.prefix'))
                ->small(__('admin-main-settings.examples.prefix')),
        ])
            ->method('changeDatabase')
            ->title(__('admin-main-settings.modals.edit_database', ['db' => $databaseId]))
            ->applyButton(__('admin-main-settings.buttons.save'))
            ->right();
    }
}
