<?php

namespace Flute\Admin\Packages\Server\Screens\Concerns;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Support\Color;

trait HandlesMainLayout
{
    private function buildModField(bool $canEditServer)
    {
        $field = LayoutFactory::field(
            Select::make('mod')
                ->options($this->serversService->getListGames())
                ->value($this->server?->mod ?? null)
                ->placeholder(__('admin-server.fields.mod.placeholder'))
                ->disabled(!$canEditServer || $this->serverId),
        )
            ->label(__('admin-server.fields.mod.label'))
            ->required();

        if ($this->serverId) {
            $field->small(__('admin-server.fields.mod.help'));
        }

        return $field;
    }

    private function mainTabLayout()
    {
        $canEditServer = user()->can('admin.servers');

        return $this->serverId
            ? LayoutFactory::split([
                $this->getMainLayout($canEditServer),
                $this->getActionsLayout($canEditServer),
            ])->ratio('60/40')
            : $this->getMainLayout($canEditServer);
    }

    private function getCurrentRankPack(): string
    {
        return request()->input('ranks') ?? $this->server?->ranks ?? 'default';
    }

    private function getCurrentRankFormat(): string
    {
        $rankPack = $this->getCurrentRankPack();
        $fromRequest = request()->input('ranks');

        if ($fromRequest !== null) {
            return $this->serversService->detectBestFormat(path('public/assets/img/ranks/' . $rankPack));
        }

        return $this->server?->ranks_format ?? 'webp';
    }

    private function isPremierRanks(): bool
    {
        $fromRequest = request()->input('ranks_premier');

        if ($fromRequest !== null) {
            return filter_var($fromRequest, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) ( $this->server?->ranks_premier ?? false );
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
                        ->placeholder(__('admin-server.fields.name.placeholder')),
                )
                    ->label(__('admin-server.fields.name.label'))
                    ->required(),

                LayoutFactory::field(
                    Input::make('ip')
                        ->type('text')
                        ->value($this->server?->ip ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder(__('admin-server.fields.ip.placeholder')),
                )
                    ->label(__('admin-server.fields.ip.label'))
                    ->small(__('admin-server.fields.ip.help'))
                    ->required(),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('port')
                        ->type('number')
                        ->value($this->server?->port ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder(__('admin-server.fields.port.placeholder')),
                )
                    ->label(__('admin-server.fields.port.label'))
                    ->required(),

                $this->buildModField($canEditServer),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('rcon')
                        ->type('password')
                        ->value($this->server?->rcon ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder(__('admin-server.fields.rcon.placeholder')),
                )
                    ->label(__('admin-server.fields.rcon.label'))
                    ->small(__('admin-server.fields.rcon.help')),

                LayoutFactory::field(
                    Input::make('display_ip')
                        ->type('text')
                        ->value($this->server?->display_ip ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder(__('admin-server.fields.display_ip.placeholder')),
                )
                    ->label(__('admin-server.fields.display_ip.label'))
                    ->small(__('admin-server.fields.display_ip.help')),
            ]),

            LayoutFactory::view('admin-server::partials.ranks-card-header', [
                'isPremier' => $this->isPremierRanks(),
                'premierValue' => $this->server?->ranks_premier ?? false ? '1' : '0',
            ]),

            ...(
                $this->isPremierRanks()
                    ? [
                        LayoutFactory::view('admin-server::partials.ranks-premier-preview'),
                    ] : [
                        LayoutFactory::split([
                            LayoutFactory::field(
                                Select::make('ranks')
                                    ->options($this->serversService->getListRanks())
                                    ->value($this->getCurrentRankPack())
                                    ->yoyo()
                                    ->placeholder(__('admin-server.fields.ranks.placeholder')),
                            )
                                ->label(__('admin-server.fields.ranks.label'))
                                ->small(__('admin-server.fields.ranks.help'))
                                ->required(),

                            LayoutFactory::field(
                                Select::make('ranks_format')
                                    ->options($this->ranksFormats)
                                    ->aligned()
                                    ->value($this->getCurrentRankFormat())
                                    ->placeholder(__('admin-server.fields.ranks_format.placeholder')),
                            )
                                ->label(__('admin-server.fields.ranks_format.label'))
                                ->small(__('admin-server.fields.ranks_format.help'))
                                ->required(),
                        ]),

                        LayoutFactory::view('admin-server::partials.rank-upload'),
                    ]
            ),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('settings__query_port')
                        ->type('number')
                        ->value($this->server?->getSetting('query_port') ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder('27015'),
                )
                    ->small(__('admin-server.fields.query_port.help'))
                    ->label(__('admin-server.fields.query_port.label')),

                LayoutFactory::field(
                    Input::make('settings__rcon_port')
                        ->type('number')
                        ->value($this->server?->getSetting('rcon_port') ?? '')
                        ->disabled(!$canEditServer)
                        ->placeholder('27015'),
                )
                    ->small(__('admin-server.fields.rcon_port.help'))
                    ->label(__('admin-server.fields.rcon_port.label')),
            ]),

            LayoutFactory::field(
                ButtonGroup::make('enabled')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value($this->server?->enabled ?? true ? '1' : '0')
                    ->disabled(!$canEditServer)
                    ->color('accent'),
            )
                ->label(__('admin-server.fields.enabled.label'))
                ->popover(__('admin-server.fields.enabled.help')),
        ];

        return LayoutFactory::block($fields)->title(__('admin-server.title.main_info'));
    }

    private function getActionsLayout(bool $canEditServer)
    {
        $layouts = [];

        if ($this->serverStatus !== null) {
            $layouts[] = LayoutFactory::view('admin-server::partials.server-status', [
                'status' => $this->serverStatus,
            ]);
        }

        $layouts[] = LayoutFactory::rows([
            Button::make(__('admin-server.buttons.test_connection'))
                ->type(Color::OUTLINE_PRIMARY)
                ->icon('ph.bold.wifi-high-bold')
                ->method('testConnection')
                ->fullWidth(),

            Button::make(__('admin-server.buttons.delete'))
                ->type(Color::OUTLINE_DANGER)
                ->icon('ph.bold.trash-bold')
                ->setVisible($canEditServer && $this->serverId)
                ->method('deleteServer')
                ->confirm(__('admin-server.confirms.delete_server'))
                ->fullWidth(),
        ]);

        return LayoutFactory::block($layouts)
            ->title(__('admin-server.title.actions'))
            ->description(__('admin-server.title.actions_description'))
            ->setVisible($this->serverId);
    }
}
