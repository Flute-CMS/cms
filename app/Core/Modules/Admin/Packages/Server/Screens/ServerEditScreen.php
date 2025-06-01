<?php

namespace Flute\Admin\Packages\Server\Screens;

use Flute\Admin\Packages\Server\Services\AdminServersService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Illuminate\Support\Str;

class ServerEditScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.servers';

    public ?Server $server = null;
    public ?int $serverId = null;
    public $dbConnections;

    /**
     * @var AdminServersService
     */
    public $serversService;
    public $ranksFormats = ['webp' => 'webp', 'png' => 'png', 'svg' => 'svg', 'jpg' => 'jpg', 'gif' => 'gif', 'jpeg' => 'jpeg'];

    private $availableDrivers = null;

    public bool $isEditMode = false;

    /**
     * Инициализация экрана при загрузке.
     */
    public function mount() : void
    {
        $this->serversService = app(AdminServersService::class);
        $this->serverId = (int) request()->input('id');

        if ($this->serverId) {
            $this->initServer();
            $this->isEditMode = true;
        } else {
            $this->name = __('admin-server.title.create');
            $this->description = __('admin-server.title.description');
        }

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-server.title.list'), url('/admin/servers'))
            ->add($this->serverId ? $this->server->name : __('admin-server.title.create'));
    }

    protected function initServer() : void
    {
        $this->server = Server::findByPK($this->serverId);

        if (!$this->server) {
            $this->flashMessage(__('admin-server.messages.server_not_found'), 'error');
            $this->redirectTo('/admin/servers', 300);
            return;
        }

        $this->dbConnections = $this->server->dbconnections;
        $this->name = __('admin-server.title.edit') . ': ' . $this->server->name;
    }

    /**
     * Командная панель с кнопками действий.
     */
    public function commandBar() : array
    {
        $buttons = [
            Button::make(__('admin-server.buttons.cancel'))
                ->type(Color::OUTLINE_PRIMARY)
                ->redirect('/admin/servers'),
        ];

        if (user()->can('admin.servers')) {
            $buttons[] = Button::make(__('admin-server.buttons.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('saveServer');
        }

        return $buttons;
    }

    /**
     * Определение макета экрана с использованием вкладок.
     */
    public function layout() : array
    {
        $tabs = [];

        $tabs[] = Tab::make(__('admin-server.tabs.main'))
            ->icon('ph.bold.gear-bold')
            ->layouts([$this->mainTabLayout()])
            ->active(true);

        if ($this->serverId) {
            $tabs[] = Tab::make(__('admin-server.tabs.db_connections'))
                ->icon('ph.bold.database-bold')
                ->layouts([$this->dbConnectionsLayout()])
                ->badge(sizeof($this->dbConnections ?? []));
        }

        return [
            LayoutFactory::tabs($tabs)
                ->slug('server-edit')
                ->pills(),
        ];
    }

    /**
     * Макет вкладки "Основные".
     */
    private function mainTabLayout()
    {
        $canEditServer = user()->can('admin.servers');

        return $this->serverId ? LayoutFactory::split([
            $this->getMainLayout($canEditServer),
            $this->getActionsLayout($canEditServer)
        ])->ratio('70/30') : $this->getMainLayout($canEditServer);
    }

    private function getMainLayout(bool $canEditServer)
    {
        $fields = [
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('name')
                        ->type('text')
                        ->value($this->server?->name ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder(__('admin-server.fields.name.placeholder'))
                )
                    ->label(__('admin-server.fields.name.label'))
                    ->required(),

                LayoutFactory::field(
                    Input::make('ip')
                        ->type('text')
                        ->value($this->server?->ip ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder(__('admin-server.fields.ip.placeholder'))
                )
                    ->label(__('admin-server.fields.ip.label'))
                    ->required(),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('port')
                        ->type('number')
                        ->value($this->server?->port ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder(__('admin-server.fields.port.placeholder'))
                )
                    ->label(__('admin-server.fields.port.label'))
                    ->required(),

                LayoutFactory::field(
                    Select::make('mod')
                        ->options($this->serversService->getListGames())
                        ->value($this->server?->mod ?? null)
                        ->placeholder(__('admin-server.fields.mod.placeholder'))
                        ->disabled(!$canEditServer || $this->serverId)
                )
                    ->label(__('admin-server.fields.mod.label'))
                    ->required(),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('rcon')
                        ->type('password')
                        ->value($this->server?->rcon ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder(__('admin-server.fields.rcon.placeholder'))
                )
                    ->label(__('admin-server.fields.rcon.label'))
                    ->small(__('admin-server.fields.rcon.help')),

                LayoutFactory::field(
                    Input::make('display_ip')
                        ->type('text')
                        ->value($this->server?->display_ip ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder(__('admin-server.fields.display_ip.placeholder'))
                )
                    ->label(__('admin-server.fields.display_ip.label'))
                    ->small(__('admin-server.fields.display_ip.help')),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Select::make('ranks')
                        ->options($this->serversService->getListRanks())
                        ->value($this->server?->ranks ?? 'default')
                        ->placeholder(__('admin-server.fields.ranks.placeholder'))
                )
                    ->label(__('admin-server.fields.ranks.label'))
                    ->required(),

                LayoutFactory::field(
                    Select::make('ranks_format')
                        ->options($this->ranksFormats)
                        ->value($this->server?->ranks_format ?? 'webp')
                        ->placeholder(__('admin-server.fields.ranks_format.placeholder'))
                )
                    ->label(__('admin-server.fields.ranks_format.label'))
                    ->required(),

                LayoutFactory::field(
                    Toggle::make('enabled')
                        ->checked($this->server?->enabled ?? true)
                        ->disabled(!$canEditServer)
                )
                    ->label(__('admin-server.fields.enabled.label'))
                    ->popover(__('admin-server.fields.enabled.help')),
            ]),
        ];

        return LayoutFactory::block($fields)
            ->title(__('admin-server.title.main_info'));
    }

    private function getActionsLayout(bool $canEditServer)
    {
        return LayoutFactory::rows([
            Button::make(__('admin-server.buttons.delete'))
                ->type(Color::OUTLINE_DANGER)
                ->icon('ph.bold.trash-bold')
                ->setVisible($canEditServer && $this->serverId)
                ->method('deleteServer')
                ->confirm(__('admin-server.confirms.delete_server'))
                ->fullWidth(),
        ])
            ->title(__('admin-server.title.actions'))
            ->description(__('admin-server.title.actions_description'))
            ->setVisible($this->serverId);
    }

    /**
     * Макет вкладки "Подключения к БД".
     */
    private function dbConnectionsLayout()
    {
        return LayoutFactory::table('dbConnections', [
            TD::make('mod', __('admin-server.db_connection.fields.mod.label'))
                ->render(fn(DatabaseConnection $connection) => $connection->mod)
                ->width('200px'),

            TD::make('dbname', __('admin-server.db_connection.fields.dbname.label'))
                ->render(fn(DatabaseConnection $connection) => $connection->dbname)
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
                        __('admin-server.db_connection.fields.params')
                    );
                })
                ->width('200px'),

            TD::make('actions', __('admin-server.buttons.actions'))
                ->render(fn(DatabaseConnection $connection) => $this->dbConnectionActionsDropdown($connection))
                ->width('100px'),
        ])
            ->searchable([
                'mod',
                'dbname'
            ])
            ->commands([
                Button::make(__('admin-server.db_connection.add.button'))
                    ->type(Color::OUTLINE_PRIMARY)
                    ->icon('ph.bold.plus-bold')
                    ->modal('addDbConnectionModal')
                    ->fullWidth(),
            ])
            ->setVisible($this->serverId);
    }

    /**
     * Действия над подключением к БД через выпадающее меню.
     */
    private function dbConnectionActionsDropdown(DatabaseConnection $connection) : string
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

    /**
     * Модальное окно для добавления подключения к БД.
     */
    public function addDbConnectionModal(Repository $parameters)
    {
        $databaseOptions = $this->getDatabaseOptions();
        $availableDrivers = $this->getAvailableDrivers();
        $selectedDriver = request()->input('custom_mod');

        $fields = [
            LayoutFactory::field(
                Select::make('custom_mod')
                    ->options($availableDrivers)
                    ->allowEmpty()
                    ->yoyo()
                    ->placeholder(__('admin-server.db_connection.fields.mod.placeholder'))
                    ->value($selectedDriver)
            )
                ->label(__('admin-server.db_connection.fields.mod.label'))
                ->small(__('admin-server.db_connection.fields.mod.help'))
                ->required(),

            LayoutFactory::field(
                Select::make('dbname')
                    ->options($databaseOptions)
                    ->allowEmpty()
                    ->value(request()->input('dbname', ''))
                    ->placeholder(__('admin-server.db_connection.fields.dbname.placeholder'))
            )
                ->label(__('admin-server.db_connection.fields.dbname.label'))
                ->required(),
        ];

        if ($selectedDriver) {
            $driverView = $this->getDriverView($selectedDriver);

            if (view()->exists($driverView)) {
                $fields[] = LayoutFactory::view($driverView, [
                    'settings' => [],
                    'driverName' => $selectedDriver
                ]);
            }
        }

        return LayoutFactory::modal($parameters, $fields)
            ->title(__('admin-server.db_connection.add.title'))
            ->applyButton(__('admin-server.buttons.add'))
            ->method('addDbConnection');
    }

    /**
     * Модальное окно для редактирования подключения к БД.
     */
    public function editDbConnectionModal(Repository $parameters)
    {
        $connectionId = $parameters->get('connectionId');
        $connection = DatabaseConnection::findByPK($connectionId);

        if (!$connection) {
            $this->flashMessage(__('admin-server.messages.connection_not_found'), 'danger');
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
                    ->placeholder(__('admin-server.db_connection.fields.mod.placeholder'))
            )
                ->label(__('admin-server.db_connection.fields.mod.label'))
                ->small(__('admin-server.db_connection.fields.mod.help'))
                ->required(),

            LayoutFactory::field(
                Select::make('dbname')
                    ->options($databaseOptions)
                    ->allowEmpty()
                    ->value(request()->input('dbname', $connection->dbname))
                    ->placeholder(__('admin-server.db_connection.fields.dbname.placeholder'))
            )
                ->label(__('admin-server.db_connection.fields.dbname.label'))
                ->required(),
        ];


        if ($selectedDriver) {
            $driverView = $this->getDriverView($selectedDriver);

            if (view()->exists($driverView)) {
                $fields[] = LayoutFactory::view($driverView, [
                    'settings' => json_decode($connection->additional ?? '{}', true),
                    'driverName' => $connection->mod
                ]);
            }
        }

        return LayoutFactory::modal($parameters, $fields)
            ->title(__('admin-server.db_connection.edit.title'))
            ->applyButton(__('admin-server.buttons.save'))
            ->method('updateDbConnection');
    }

    /**
     * Получить список доступных драйверов (модов).
     */
    private function getAvailableDrivers(?string $mod = null) : array
    {
        if ($this->availableDrivers !== null) {
            return $this->availableDrivers;
        }

        $registeredDrivers = $this->serversService->getDrivers();
        $result = [
            'custom' => __('admin-server.db_connection.fields.driver.custom')
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

    private function getDriverView(string $driverName) : string
    {
        $driver = $this->serversService->makeDriver($driverName);
        return $driver->getSettingsView();
    }

    /**
     * Get database options from config/database.php
     */
    private function getDatabaseOptions() : array
    {
        $databases = config('database.databases', []);
        $options = [];

        foreach ($databases as $key => $value) {
            if($key === 'default') {
                continue;
            }

            $options[$key] = $key;
        }

        return $options;
    }

    /**
     * Сохранение сервера.
     */
    public function saveServer()
    {
        $data = request()->input();

        if(request()->input('tab-server-edit') === Str::slug(__('admin-server.tabs.db_connections'))) {
            $this->flashMessage(__('admin-server.messages.save_not_for_db_connections'), 'error');
            return;
        }

        $validation = $this->validate([
            'name' => ['required', 'string', 'max-str-len:255'],
            'ip' => ['required', 'string', 'max-str-len:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mod' => ['required', 'string', 'max-str-len:255'],
            'rcon' => ['nullable', 'string', 'max-str-len:255'],
            'display_ip' => ['nullable', 'string', 'max-str-len:255'],
            'enabled' => ['required', 'boolean'],
            'ranks' => ['required', 'string', 'max-str-len:255'],
            'ranks_format' => ['required', 'string', 'max-str-len:255'],
        ], $data);

        if (!$validation) {
            return;
        }

        if(str_contains($data['ip'], ':')) {
            $this->inputError('ip', __('admin-server.messages.invalid_ip'));
            return;
        }

        try {
            $server = $this->serversService->saveServer($this->server, $data);

            if (!$this->serverId) {
                $this->flashMessage(__('admin-server.messages.server_created'), 'success');
                $this->redirect('/admin/servers/' . $server->id . '/edit');
            } else {
                $this->flashMessage(__('admin-server.messages.server_updated'), 'success');
            }
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Добавление подключения к БД.
     */
    public function addDbConnection()
    {
        if (!$this->server) {
            $this->flashMessage(__('admin-server.messages.save_server_first'), 'error');
            return;
        }

        $data = request()->input();

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
                } catch (\Exception $e) {
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

            $this->flashMessage(__('admin-server.messages.connection_add_success'), 'success');
            $this->closeModal();
            $this->initServer();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    private function getDriverParams(string $driverName) : array
    {
        $driver = $this->serversService->makeDriver($driverName);
        return $driver->getValidationRules();
    }

    private function prepareData(array $data) : array
    {
        $driver = $this->serversService->makeDriver($data['mod']);

        if(method_exists($driver, 'prepareData')) {
            // custom method for prepare data
            return $driver->prepareData($data);
        }

        return [];
    }

    /**
     * Обновление подключения к БД.
     */
    public function updateDbConnection()
    {
        $data = request()->input();
        $connectionId = $this->modalParams->get('connectionId');
        $connection = rep(DatabaseConnection::class)->findByPK($connectionId);

        if (!$connection) {
            $this->flashMessage(__('admin-server.messages.connection_not_found'), 'error');
            return;
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
                } catch (\Exception $e) {
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

            $this->initServer();
            $this->flashMessage(__('admin-server.messages.connection_update_success'), 'success');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Удаление подключения к БД.
     */
    public function deleteDbConnection()
    {
        $connectionId = request()->input('connectionId');

        try {
            $this->serversService->deleteDbConnection($connectionId);
            $this->flashMessage(__('admin-server.messages.connection_delete_success'), 'success');
            $this->initServer();
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    /**
     * Удаление сервера.
     */
    public function deleteServer()
    {
        if (!user()->can('admin.servers')) {
            $this->flashMessage(__('admin-server.messages.no_permission.delete'), 'error');
            return;
        }

        try {
            $this->server->delete();
            $this->flashMessage(__('admin-server.messages.delete_success'), 'success');
            $this->redirectTo('/admin/servers');
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
}