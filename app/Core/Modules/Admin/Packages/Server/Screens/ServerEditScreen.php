<?php

namespace Flute\Admin\Packages\Server\Screens;

use Flute\Admin\Packages\Server\Screens\Concerns\HandlesDbActions;
use Flute\Admin\Packages\Server\Screens\Concerns\HandlesDbLayout;
use Flute\Admin\Packages\Server\Screens\Concerns\HandlesDbModals;
use Flute\Admin\Packages\Server\Screens\Concerns\HandlesMainLayout;
use Flute\Admin\Packages\Server\Screens\Concerns\HandlesRcon;
use Flute\Admin\Packages\Server\Screens\Concerns\HandlesServerActions;
use Flute\Admin\Packages\Server\Services\AdminServersService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Core\Database\Entities\Server;

class ServerEditScreen extends Screen
{
    use HandlesDbActions;
    use HandlesDbLayout;
    use HandlesDbModals;
    use HandlesMainLayout;
    use HandlesRcon;
    use HandlesServerActions;

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

    public $ranksFormats = [
        'webp' => 'webp',
        'png' => 'png',
        'jpg' => 'jpg',
        'gif' => 'gif',
        'jpeg' => 'jpeg',
    ];

    public bool $isEditMode = false;

    public ?array $serverStatus = null;

    public array $rconHistory = [];

    private $availableDrivers = null;

    public function mount(): void
    {
        $this->serversService = app(AdminServersService::class);
        $this->serverId = (int) ( request()->input('id') ?: $this->serverId );

        if ($this->serverId) {
            $this->initServer();
            $this->isEditMode = true;
            $this->rconHistory = session()->get("rcon_history_{$this->serverId}", []);
        } else {
            $this->name = __('admin-server.title.create');
            $this->description = __('admin-server.title.description');
        }

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(
            __('admin-server.title.list'),
            url('/admin/servers'),
        )->add($this->serverId ? $this->server->name : __('admin-server.title.create'));

        $this->loadJS('app/Core/Modules/Admin/Packages/Server/Resources/assets/js/rank-upload.js');
    }

    public function commandBar(): array
    {
        $buttons = [
            Button::make(__('admin-server.buttons.cancel'))->type(Color::OUTLINE_PRIMARY)->redirect('/admin/servers'),
        ];

        if (user()->can('admin.servers')) {
            $buttons[] = Button::make(__('admin-server.buttons.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('saveServer');
        }

        return $buttons;
    }

    public function layout(): array
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

            if ($this->server && !empty($this->server->rcon)) {
                $tabs[] = Tab::make(__('admin-server.rcon.title'))
                    ->icon('ph.bold.terminal-bold')
                    ->layouts([$this->rconTabLayout()]);
            }
        }

        return [
            LayoutFactory::tabs($tabs)->slug('server-edit')->pills(),
        ];
    }

    protected function initServer(): void
    {
        $this->server = Server::findByPK($this->serverId);

        if (!$this->server) {
            $this->flashMessage(__('admin-server.messages.server_not_found'), 'error');
            $this->redirectTo('/admin/servers', 300);

            return;
        }

        $this->dbConnections = DatabaseConnection::query()->where('server_id', $this->serverId)->fetchAll();
        $this->name = __('admin-server.title.edit') . ': ' . $this->server->name;
    }
}
