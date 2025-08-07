<?php

namespace Flute\Admin\Packages\Server\Screens;

use Flute\Admin\Packages\Server\Services\AdminServersService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Components\Cells\DateTime;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Server;

class ServerListScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.servers';

    public $servers;

    public function mount(): void
    {
        $this->name = __('admin-server.title.list');
        $this->description = __('admin-server.title.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-server.title.list'));

        $this->servers = rep(Server::class)->select();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('servers', [
                TD::make('mod')
                    ->title(__('admin-server.fields.mod.label'))
                    ->render(fn (Server $server) => app(AdminServersService::class)->getGameName($server->mod))
                    ->width('200px')
                    ->sort()
                    ->cantHide(),

                TD::make('name')
                    ->title(__('admin-server.fields.name.label'))
                    ->render(fn (Server $server) => view('admin-server::cells.server', compact('server')))
                    ->minWidth('200px')
                    ->cantHide(),

                TD::make('enabled')
                    ->title(__('admin-server.fields.enabled.label'))
                    ->render(fn (Server $server) => view('admin-server::cells.enabled', compact('server')))
                    ->popover(__('admin-server.fields.enabled.help')),

                TD::make('createdAt')
                    ->title(__('admin-server.fields.created_at'))
                    ->asComponent(DateTime::class)
                    ->width('200px')
                    ->sort()
                    ->cantHide(),

                TD::make('actions')
                    ->title(__('admin-server.buttons.actions'))
                    ->width('200px')
                    ->alignCenter()
                    ->render(
                        fn (Server $server) => DropDown::make()
                            ->icon('ph.regular.dots-three-outline-vertical')
                            ->list([
                                DropDownItem::make(__('admin-server.buttons.edit'))
                                    ->redirect(url('/admin/servers/' . $server->id . '/edit'))
                                    ->icon('ph.bold.pencil-bold')
                                    ->type(Color::OUTLINE_PRIMARY)
                                    ->size('small')
                                    ->fullWidth(),

                                DropDownItem::make(__('admin-server.buttons.delete'))
                                    ->confirm(__('admin-server.confirms.delete_server'))
                                    ->method('delete', ['delete-id' => $server->id])
                                    ->icon('ph.bold.trash-bold')
                                    ->type(Color::OUTLINE_DANGER)
                                    ->size('small')
                                    ->fullWidth(),
                            ])
                    ),
            ])
                ->searchable(['name', 'ip', 'port'])
                ->commands([
                    Button::make(__('admin-server.buttons.add'))
                        ->icon('ph.bold.plus-bold')
                        ->redirect(url('/admin/servers/add')),
                ]),
        ];
    }

    public function delete(): void
    {
        $server = Server::findByPK(request()->get('delete-id'));

        if ($server) {
            $server->delete();
        }

        $this->flashMessage(__('admin-server.messages.server_deleted'));
    }
}
