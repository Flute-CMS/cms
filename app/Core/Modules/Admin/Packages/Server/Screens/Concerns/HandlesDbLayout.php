<?php

namespace Flute\Admin\Packages\Server\Screens\Concerns;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\DatabaseConnection;

trait HandlesDbLayout
{
    private function dbConnectionsLayout()
    {
        return LayoutFactory::table('dbConnections', [
            TD::make('mod', __('admin-server.db_connection.fields.mod.label'))
                ->render(static fn(DatabaseConnection $connection) => $connection->mod)
                ->width('200px'),

            TD::make('dbname', __('admin-server.db_connection.fields.dbname.label'))
                ->render(static fn(DatabaseConnection $connection) => $connection->dbname)
                ->width('200px'),

            TD::make('additional', __('admin-server.db_connection.fields.additional.label'))
                ->render(function (DatabaseConnection $connection) {
                    $additional = json_decode($connection->additional ?? '{}', true);

                    if (empty($additional)) {
                        return '-';
                    }

                    $availableDrivers = $this->getAvailableDrivers();
                    $driverName = $availableDrivers[$connection->mod] ?? $connection->mod;

                    $paramCount = count($additional);

                    return sprintf(
                        '%s (%d %s)',
                        $driverName,
                        $paramCount,
                        __('admin-server.db_connection.fields.params'),
                    );
                })
                ->width('200px'),

            TD::make('actions', __('admin-server.buttons.actions'))
                ->render(fn(DatabaseConnection $connection) => $this->dbConnectionActionsDropdown($connection))
                ->width('100px'),
        ])
            ->empty(
                'ph.regular.plugs-connected',
                __('admin-server.empty.db_connections.title'),
                __('admin-server.empty.db_connections.sub'),
            )
            ->emptyButton(
                Button::make(__('admin-server.db_connection.add.button'))
                    ->type(Color::OUTLINE_PRIMARY)
                    ->icon('ph.bold.plus-bold')
                    ->modal('addDbConnectionModal'),
            )
            ->searchable([
                'mod',
                'dbname',
            ])
            ->commands($this->dbConnectionCommands())
            ->setVisible($this->serverId);
    }

    private function dbConnectionCommands(): array
    {
        $commands = [];

        if (user()->can('admin.boss')) {
            $commands[] = Button::make(__('admin-server.db_connection.create_db.button'))
                ->type(Color::OUTLINE_SECONDARY)
                ->icon('ph.bold.database-bold')
                ->modal('addDatabaseModal')
                ->fullWidth();
        }

        $commands[] = Button::make(__('admin-server.db_connection.add.button'))
            ->type(Color::OUTLINE_PRIMARY)
            ->icon('ph.bold.plus-bold')
            ->modal('addDbConnectionModal')
            ->fullWidth();

        return $commands;
    }

    private function dbConnectionActionsDropdown(DatabaseConnection $connection): string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('admin-server.buttons.edit'))
                    ->modal('editDbConnectionModal', ['connectionId' => $connection->id])
                    ->icon('ph.bold.pencil-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('admin-server.buttons.delete'))
                    ->confirm(__('admin-server.db_connection.delete.confirm'))
                    ->method('deleteDbConnection', ['connectionId' => $connection->id])
                    ->icon('ph.bold.trash-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }

    private function getAvailableDrivers(?string $mod = null): array
    {
        if ($this->availableDrivers !== null) {
            return $this->availableDrivers;
        }

        $registeredDrivers = $this->serversService->getDrivers();
        $result = [
            'custom' => __('admin-server.db_connection.fields.driver.custom'),
        ];

        $dbConnections = collect($this->dbConnections);

        foreach ($registeredDrivers as $key => $driverClass) {
            if ($this->isEditMode && $dbConnections->contains('mod', $key) && $key !== $mod) {
                continue;
            }

            $driver = $this->serversService->makeDriver($key);
            $result[$key] = $driver->getName();
        }

        $this->availableDrivers = $result;

        return $result;
    }

    private function getDriverView(string $driverName): string
    {
        $driver = $this->serversService->makeDriver($driverName);

        return $driver->getSettingsView();
    }

    private function getDatabaseOptions(): array
    {
        $databases = config('database.databases', []);
        $options = [];

        foreach ($databases as $key => $value) {
            if ($key === 'default') {
                continue;
            }

            $options[$key] = $key;
        }

        return $options;
    }

    private function invalidateConfig(string $configName): void
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(path('config/' . $configName . '.php'), true);
        }
    }

    private function getDriverParams(string $driverName): array
    {
        $driver = $this->serversService->makeDriver($driverName);

        return $driver->getValidationRules();
    }

    private function prepareData(array $data): array
    {
        $driver = $this->serversService->makeDriver($data['mod']);

        if (method_exists($driver, 'prepareData')) {
            return $driver->prepareData($data);
        }

        return [];
    }
}
