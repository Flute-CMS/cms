<?php

namespace Flute\Admin\Packages\Server\Screens\Concerns;

use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Core\Database\Entities\DatabaseConnection;

trait HandlesDbModals
{
    public function addDbConnectionModal(Repository $parameters)
    {
        $databaseOptions = $this->getDatabaseOptions();
        $availableDrivers = $this->getAvailableDrivers();
        $selectedDriver = request()->input('custom_mod');
        $selectedDb = request()->input('dbname', '');

        $currentStep = 1;
        if ($selectedDriver) {
            $currentStep = 2;
        }
        if ($selectedDriver && $selectedDb) {
            $currentStep = 3;
        }

        $fields = [];

        $fields[] = LayoutFactory::view('admin-server::db-connections.steps-header', [
            'currentStep' => $currentStep,
            'description' => $currentStep === 1 ? __('admin-server.db_connection.add.description') : null,
        ]);

        if (empty($databaseOptions) && !$selectedDriver) {
            $fields[] = LayoutFactory::view('admin-server::db-connections.empty');
        }

        $fields[] = LayoutFactory::field(
            Select::make('custom_mod')
                ->options($availableDrivers)
                ->allowEmpty()
                ->yoyo()
                ->placeholder(__('admin-server.db_connection.fields.mod.placeholder'))
                ->value($selectedDriver),
        )
            ->label(__('admin-server.db_connection.fields.mod.label'))
            ->small(__('admin-server.db_connection.fields.mod.help'))
            ->required();

        if ($selectedDriver) {
            $dbHelpParts = [__('admin-server.db_connection.fields.dbname.help')];

            $fields[] = LayoutFactory::field(
                Select::make('dbname')
                    ->options($databaseOptions)
                    ->allowEmpty()
                    ->yoyo()
                    ->value($selectedDb)
                    ->placeholder(__('admin-server.db_connection.fields.dbname.placeholder')),
            )
                ->label(__('admin-server.db_connection.fields.dbname.label'))
                ->small(implode(' ', $dbHelpParts))
                ->required();

            if ($selectedDb) {
                $driverView = $this->getDriverView($selectedDriver);

                if (view()->exists($driverView)) {
                    $fields[] = LayoutFactory::view($driverView, [
                        'settings' => [],
                        'driverName' => $selectedDriver,
                    ]);
                }
            }
        }

        return LayoutFactory::modal($parameters, $fields)
            ->title(__('admin-server.db_connection.add.title'))
            ->applyButton(__('admin-server.db_connection.add.button'))
            ->method('addDbConnection');
    }

    public function editDbConnectionModal(Repository $parameters)
    {
        $connectionId = $parameters->get('connectionId');
        $connection = DatabaseConnection::findByPK($connectionId);

        if (!$connection) {
            $this->flashMessage(__('admin-server.messages.connection_not_found'), 'error');

            return;
        }

        $databaseOptions = $this->getDatabaseOptions();
        $availableDrivers = $this->getAvailableDrivers($connection->mod);

        $isCustomDriver = !in_array($connection->mod, array_keys($availableDrivers)) && $connection->mod !== 'custom';
        $selectedDriver = request()->input('custom_mod', $isCustomDriver ? 'custom' : $connection->mod);

        $fields = [
            LayoutFactory::field(
                Select::make('custom_mod')
                    ->options($availableDrivers)
                    ->allowEmpty()
                    ->value(request()->input('custom_mod', $isCustomDriver ? 'custom' : $connection->mod))
                    ->yoyo()
                    ->placeholder(__('admin-server.db_connection.fields.mod.placeholder')),
            )
                ->label(__('admin-server.db_connection.fields.mod.label'))
                ->small(__('admin-server.db_connection.fields.mod.help'))
                ->required(),

            LayoutFactory::field(
                Select::make('dbname')
                    ->options($databaseOptions)
                    ->allowEmpty()
                    ->value(request()->input('dbname', $connection->dbname))
                    ->placeholder(__('admin-server.db_connection.fields.dbname.placeholder')),
            )
                ->label(__('admin-server.db_connection.fields.dbname.label'))
                ->small(__('admin-server.db_connection.fields.dbname.help'))
                ->required(),
        ];

        if ($selectedDriver) {
            $driverView = $this->getDriverView($selectedDriver);

            if (view()->exists($driverView)) {
                $fields[] = LayoutFactory::view($driverView, [
                    'settings' => json_decode($connection->additional ?? '{}', true),
                    'driverName' => $connection->mod,
                ]);
            }
        }

        return LayoutFactory::modal($parameters, $fields)
            ->title(__('admin-server.db_connection.edit.title'))
            ->applyButton(__('admin-server.buttons.save'))
            ->method('updateDbConnection');
    }

    public function addDatabaseModal(Repository $parameters)
    {
        if (!user()->can('admin.boss')) {
            $this->flashMessage(__('def.permission_denied'), 'error');

            return null;
        }

        $defaultConnection = config('database.connections.default');

        $explode = explode('\\', $defaultConnection->driver);
        $driver = str_replace('driver', '', strtolower(end($explode)));
        $supportsMysqlOptions = $driver === 'mysql';
        $supportsReconnect = in_array($driver, ['mysql', 'postgres'], true);

        return LayoutFactory::modal($parameters, [
            LayoutFactory::view('admin-server::db-connections.create-note'),
            LayoutFactory::field(
                ButtonGroup::make('driver')
                    ->options([
                        'mysql' => ['label' => 'MySQL', 'icon' => 'ph.bold.database-bold'],
                        'postgres' => ['label' => 'PostgreSQL', 'icon' => 'ph.bold.database-bold'],
                    ])
                    ->value($driver)
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_driver'))
                ->required(),
            LayoutFactory::field(
                Input::make('databaseName')
                    ->type('text')
                    ->value(request()->input('databaseName', ''))
                    ->placeholder(__('admin-main-settings.placeholders.database_name')),
            )
                ->label(__('admin-main-settings.labels.database_name'))
                ->required(),
            LayoutFactory::field(
                Input::make('host')
                    ->type('text')
                    ->value(request()->input('host', $defaultConnection->connection->host))
                    ->placeholder(__('admin-main-settings.placeholders.db_host')),
            )
                ->label(__('admin-main-settings.labels.host'))
                ->required(),
            LayoutFactory::field(
                Input::make('port')
                    ->type('number')
                    ->value(request()->input('port', $defaultConnection->connection->port))
                    ->placeholder(__('admin-main-settings.placeholders.db_port')),
            )
                ->label(__('admin-main-settings.labels.port'))
                ->required(),
            LayoutFactory::field(
                Input::make('user')
                    ->type('text')
                    ->value(request()->input('user', ''))
                    ->placeholder(__('admin-main-settings.placeholders.db_user')),
            )
                ->label(__('admin-main-settings.labels.user'))
                ->required(),
            LayoutFactory::field(
                Input::make('database')
                    ->type('text')
                    ->value(request()->input('database', ''))
                    ->placeholder(__('admin-main-settings.placeholders.db_database')),
            )
                ->label(__('admin-main-settings.labels.database'))
                ->required(),
            LayoutFactory::field(
                Input::make('password')
                    ->type('password')
                    ->value(request()->input('password', ''))
                    ->placeholder(__('admin-main-settings.placeholders.db_password')),
            )->label(__('admin-main-settings.labels.password')),
            LayoutFactory::field(
                ButtonGroup::make('persistent')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value(request()->input('persistent', '0'))
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.persistent_connections'))
                ->popover(__('admin-main-settings.popovers.persistent_connections')),
            LayoutFactory::field(
                Input::make('init_sql')
                    ->type('text')
                    ->value(request()->input('init_sql', ''))
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
                    ->value(request()->input('compression', '0'))
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
                    ->value(request()->input('reconnect', '1'))
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.db_reconnect'))
                ->popover(__('admin-main-settings.popovers.db_reconnect'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('connect_timeout')
                    ->type('number')
                    ->value(request()->input('connect_timeout', 5))
                    ->placeholder(__('admin-main-settings.placeholders.db_connect_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_connect_timeout'))
                ->popover(__('admin-main-settings.popovers.db_connect_timeout'))
                ->setVisible($supportsReconnect),
            LayoutFactory::field(
                Input::make('read_timeout')
                    ->type('number')
                    ->value(request()->input('read_timeout', 30))
                    ->placeholder(__('admin-main-settings.placeholders.db_read_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_read_timeout'))
                ->popover(__('admin-main-settings.popovers.db_read_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('write_timeout')
                    ->type('number')
                    ->value(request()->input('write_timeout', 30))
                    ->placeholder(__('admin-main-settings.placeholders.db_write_timeout')),
            )
                ->label(__('admin-main-settings.labels.db_write_timeout'))
                ->popover(__('admin-main-settings.popovers.db_write_timeout'))
                ->setVisible($supportsMysqlOptions),
            LayoutFactory::field(
                Input::make('prefix')
                    ->type('text')
                    ->value(request()->input('prefix', ''))
                    ->placeholder(__('admin-main-settings.placeholders.db_prefix')),
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
}
