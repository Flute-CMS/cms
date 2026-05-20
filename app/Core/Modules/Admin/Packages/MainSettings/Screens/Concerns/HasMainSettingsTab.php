<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\DatePicker;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\RadioCards;
use Flute\Admin\Platform\Fields\RichText;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;

trait HasMainSettingsTab
{
    private function mainSettingsLayout()
    {
        return LayoutFactory::tabs([
            Tab::make(__('admin-main-settings.blocks.main_settings'))
                ->slug('main')
                ->layouts([
                    LayoutFactory::split([
                        $this->mainSettingsMainBlock(),
                        $this->mainSettingsMaintenanceBlock(),
                    ])->ratio('60/40'),
                ]),
            Tab::make(__('admin-main-settings.blocks.seo'))
                ->slug('seo')
                ->layouts([
                    $this->mainSettingsSeoBlock(),
                ]),
            Tab::make(__('admin-main-settings.blocks.branding'))
                ->slug('branding')
                ->icon('ph.bold.paint-brush-bold')
                ->layouts([
                    $this->additionalSettingsImagesBlock(),
                ]),
        ])
            ->slug('main_settings_sections')
            ->pills()
            ->sticky(false)
            ->lazyload(false);
    }

    private function mainSettingsMainBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('name')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.site_name'))
                        ->value(config('app.name')),
                )
                    ->label(__('admin-main-settings.labels.site_name'))
                    ->required(),
                LayoutFactory::field(
                    Input::make('url')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.site_url'))
                        ->value(config('app.url')),
                )
                    ->label(__('admin-main-settings.labels.site_url'))
                    ->required(),
            ]),
            LayoutFactory::field(
                Input::make('timezone')
                    ->placeholder(__('admin-main-settings.placeholders.timezone'))
                    ->value(config('app.timezone')),
            )
                ->label(__('admin-main-settings.labels.timezone'))
                ->required()
                ->small(__('admin-main-settings.examples.timezone')),
            LayoutFactory::split([
                LayoutFactory::field(
                    ButtonGroup::make('change_theme')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.change_theme') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.change_theme'))
                    ->popover(__('admin-main-settings.popovers.change_theme')),
                LayoutFactory::field(
                    RadioCards::make('default_theme')
                        ->options([
                            'dark' => [
                                'label' => __('admin-main-settings.options.theme.dark'),
                                'icon' => 'ph.bold.moon-bold',
                            ],
                            'light' => [
                                'label' => __('admin-main-settings.options.theme.light'),
                                'icon' => 'ph.bold.sun-bold',
                            ],
                        ])
                        ->columns(2)
                        ->value(config('app.default_theme', 'dark')),
                )
                    ->label(__('admin-main-settings.labels.default_theme'))
                    ->popover(__('admin-main-settings.popovers.default_theme')),
            ])->ratio('50/50'),
            LayoutFactory::field(
                Input::make('flute_key')
                    ->type('password')
                    ->placeholder(__('admin-main-settings.placeholders.flute_key'))
                    ->value(config('app.flute_key')),
            )
                ->label(__('admin-main-settings.labels.flute_key'))
                ->popover(__('admin-main-settings.popovers.flute_key')),
            LayoutFactory::field(
                Input::make('steam_api')
                    ->type('password')
                    ->placeholder(__('admin-main-settings.placeholders.steam_api'))
                    ->value(config('app.steam_api')),
            )
                ->label(__('admin-main-settings.labels.steam_api'))
                ->popover(__('admin-main-settings.popovers.steam_api')),
            LayoutFactory::field(
                Input::make('steam_cache_duration')
                    ->type('number')
                    ->placeholder(__('admin-main-settings.placeholders.steam_cache_duration'))
                    ->value(config('app.steam_cache_duration', 3600)),
            )
                ->label(__('admin-main-settings.labels.steam_cache_duration'))
                ->popover(__('admin-main-settings.popovers.steam_cache_duration'))
                ->small(__('admin-main-settings.examples.steam_cache_duration')),
            LayoutFactory::field(
                TextArea::make('footer_description')
                    ->placeholder(__('admin-main-settings.placeholders.footer_description'))
                    ->value(config('app.footer_description')),
            )->label(__('admin-main-settings.labels.footer_description')),
            LayoutFactory::field(
                RichText::make('footer_additional')
                    ->toolbarPreset('minimal')
                    ->height(100)
                    ->placeholder(__('admin-main-settings.placeholders.footer_additional'))
                    ->value(config('app.footer_additional')),
            )->label(__('admin-main-settings.labels.footer_additional')),
        ])->title(__('admin-main-settings.blocks.main_settings'))->addClass('mb-2');
    }

    private function mainSettingsMaintenanceBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                ButtonGroup::make('maintenance_mode')
                    ->options([
                        '0' => [
                            'label' => __('admin-main-settings.options.site_status.open'),
                            'icon' => 'ph.bold.globe-bold',
                        ],
                        '1' => [
                            'label' => __('admin-main-settings.options.site_status.closed'),
                            'icon' => 'ph.bold.lock-bold',
                        ],
                    ])
                    ->value(config('app.maintenance_mode') ? '1' : '0')
                    ->color('accent')
                    ->fullWidth(),
            )
                ->label(__('admin-main-settings.labels.site_status'))
                ->popover(__('admin-main-settings.popovers.maintenance_mode')),
            LayoutFactory::field(
                Input::make('maintenance_title')
                    ->placeholder(__('admin-main-settings.placeholders.maintenance_title'))
                    ->value(config('app.maintenance_title')),
            )
                ->label(__('admin-main-settings.labels.maintenance_title'))
                ->small(__('admin-main-settings.small.maintenance_title')),
            LayoutFactory::field(
                TextArea::make('maintenance_message')
                    ->placeholder(__('admin-main-settings.placeholders.maintenance_message'))
                    ->value(config('app.maintenance_message')),
            )->label(__('admin-main-settings.labels.maintenance_message')),
            LayoutFactory::field(Toggle::make('maintenance_show_timer')->value((bool) config(
                'app.maintenance_show_timer',
                false,
            )))
                ->label(__('admin-main-settings.labels.maintenance_show_timer'))
                ->popover(__('admin-main-settings.popovers.maintenance_show_timer')),
            LayoutFactory::field(
                DatePicker::make('maintenance_end_time')
                    ->enableTime()
                    ->dateFormat('Y-m-d H:i')
                    ->altFormat('d.m.Y H:i')
                    ->minDate('today')
                    ->placeholder(__('admin-main-settings.placeholders.maintenance_end_time'))
                    ->value(config('app.maintenance_end_time')),
            )
                ->label(__('admin-main-settings.labels.maintenance_end_time'))
                ->small(__('admin-main-settings.small.maintenance_end_time')),
            LayoutFactory::field(
                Select::make('maintenance_allowed_roles')
                    ->fromDatabase('roles', 'name', 'id', ['name', 'id', 'priority'])
                    ->multiple(true)
                    ->value(config('app.maintenance_allowed_roles', []))
                    ->placeholder(__('admin-main-settings.placeholders.maintenance_allowed_roles')),
            )
                ->label(__('admin-main-settings.labels.maintenance_allowed_roles'))
                ->popover(__('admin-main-settings.popovers.maintenance_allowed_roles')),
        ])->title(__('admin-main-settings.blocks.tech_work_settings'))->addClass('mb-2');
    }

    private function mainSettingsSeoBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                Input::make('keywords')
                    ->placeholder(__('admin-main-settings.placeholders.keywords'))
                    ->value(config('app.keywords')),
            )
                ->label(__('admin-main-settings.labels.keywords'))
                ->required()
                ->small(__('admin-main-settings.examples.keywords')),

            LayoutFactory::field(Select::make('robots')
                ->value(config('app.robots', 'index, follow'))
                ->aligned()
                ->options([
                    'index, follow' => __('admin-main-settings.options.robots.index_follow'),
                    'index, nofollow' => __('admin-main-settings.options.robots.index_nofollow'),
                    'noindex, nofollow' => __('admin-main-settings.options.robots.noindex_nofollow'),
                    'noindex, follow' => __('admin-main-settings.options.robots.noindex_follow'),
                ]))
                ->label(__('admin-main-settings.labels.robots'))
                ->required()
                ->small(__('admin-main-settings.examples.robots')),

            LayoutFactory::field(
                Input::make('description')
                    ->placeholder(__('admin-main-settings.placeholders.description'))
                    ->value(config('app.description')),
            )->label(__('admin-main-settings.labels.description')),
        ])
            ->title(__('admin-main-settings.blocks.seo'))
            ->addClass('mb-2')
            ->popover(__('admin-main-settings.popovers.seo'));
    }

    private function mainSettingsPersonalCabinetBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                Input::make('currency_view')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.currency_view'))
                    ->value(config('lk.currency_view')),
            )
                ->label(__('admin-main-settings.labels.currency_view'))
                ->popover(__('admin-main-settings.popovers.currency_view')),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('oferta_view')
                        ->options([
                            '0' => ['label' => __('def.hide'), 'icon' => 'ph.bold.eye-slash-bold'],
                            '1' => ['label' => __('def.show'), 'icon' => 'ph.bold.eye-bold'],
                        ])
                        ->value(config('lk.oferta_view') ? '1' : '0')
                        ->color('accent'),
                )->label(__('admin-main-settings.labels.oferta_view')),
                LayoutFactory::field(
                    ButtonGroup::make('lk_only_modal')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.lk.page'),
                                'icon' => 'ph.bold.browser-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.lk.modal'),
                                'icon' => 'ph.bold.frame-corners-bold',
                            ],
                        ])
                        ->value(config('lk.only_modal') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.lk_only_modal'))
                    ->popover(__('admin-main-settings.popovers.lk_only_modal')),
                LayoutFactory::field(
                    ButtonGroup::make('lk_step_mode')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.steps-bold'],
                        ])
                        ->value(config('lk.step_mode') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.lk_step_mode'))
                    ->popover(__('admin-main-settings.popovers.lk_step_mode')),
            ]),
            LayoutFactory::field(
                Input::make('oferta_url')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.oferta_url'))
                    ->value(config('lk.oferta_url')),
            )
                ->label(__('admin-main-settings.labels.oferta_url'))
                ->popover(__('admin-main-settings.popovers.oferta_url'))
                ->small(__('admin-main-settings.examples.oferta_url')),
        ])->title(__('admin-main-settings.blocks.personal_cabinet_settings'))->addClass('mb-2');
    }

    private function mainSettingsSiteModeBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('auth_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.auth_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.auth_enabled'))
                    ->popover(__('admin-main-settings.popovers.auth_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('profile_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.profile_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.profile_enabled'))
                    ->popover(__('admin-main-settings.popovers.profile_enabled')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('balance_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.balance_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.balance_enabled'))
                    ->popover(__('admin-main-settings.popovers.balance_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('notifications_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.notifications_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.notifications_enabled'))
                    ->popover(__('admin-main-settings.popovers.notifications_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('notifications_popup_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.notifications_popup_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.notifications_popup_enabled'))
                    ->popover(__('admin-main-settings.popovers.notifications_popup_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('notifications_sound_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('app.notifications_sound_enabled', true) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.notifications_sound_enabled'))
                    ->popover(__('admin-main-settings.popovers.notifications_sound_enabled')),
            ]),
        ])
            ->title(__('admin-main-settings.blocks.features'))
            ->description(__('admin-main-settings.blocks.features_description'))
            ->addClass('mb-2');
    }
}
