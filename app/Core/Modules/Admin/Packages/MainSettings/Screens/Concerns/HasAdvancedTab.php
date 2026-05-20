<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Support\Color;

trait HasAdvancedTab
{
    private function advancedSettingsLayout()
    {
        return LayoutFactory::tabs([
            Tab::make(__('admin-main-settings.blocks.performance'))
                ->icon('ph.bold.lightning-bold')
                ->layouts([
                    $this->advancedPerformanceBlock(),
                ]),
            Tab::make(__('admin-main-settings.blocks.debug_settings'))
                ->icon('ph.bold.bug-bold')
                ->layouts([
                    $this->advancedDebugBlock(),
                ]),
            Tab::make(__('admin-main-settings.blocks.misc_settings'))
                ->icon('ph.bold.dots-three-bold')
                ->layouts([
                    $this->additionalSettingsGeneralBlock(),
                ]),
        ])
            ->slug('advanced_settings_sections')
            ->pills()
            ->sticky(false)
            ->lazyload(false);
    }

    private function advancedPerformanceBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('is_performance')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.lightning-bold'],
                        ])
                        ->value(config('app.is_performance') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.is_performance'))
                    ->popover(__('admin-main-settings.popovers.is_performance')),
                LayoutFactory::field(
                    ButtonGroup::make('cron_mode')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.clock-bold'],
                        ])
                        ->value(config('app.cron_mode') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.cron_mode'))
                    ->popover(__('admin-main-settings.popovers.cron_mode')),
            ]),
            LayoutFactory::view('admin-main-settings::cron')->setVisible(boolval(config('app.cron_mode'))),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('convert_to_webp')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.image-bold'],
                        ])
                        ->value(config('app.convert_to_webp') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.convert_to_webp'))
                    ->popover(__('admin-main-settings.popovers.convert_to_webp')),
                LayoutFactory::field(
                    ButtonGroup::make('minify')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.file-zip-bold'],
                        ])
                        ->value(config('assets.minify') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.minify'))
                    ->small(__('admin-main-settings.labels.minify_description')),
                LayoutFactory::field(
                    ButtonGroup::make('autoprefix')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.browsers-bold'],
                        ])
                        ->value(config('assets.autoprefix', false) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.autoprefix'))
                    ->small(__('admin-main-settings.labels.autoprefix_description')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('create_backup')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.download-bold'],
                        ])
                        ->value(config('app.create_backup', false) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.create_backup'))
                    ->popover(__('admin-main-settings.popovers.create_backup')),
                LayoutFactory::field(
                    ButtonGroup::make('auto_update')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.arrow-clockwise-bold'],
                        ])
                        ->value(config('app.auto_update', false) ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.auto_update'))
                    ->setVisible(config('app.cron_mode'))
                    ->popover(__('admin-main-settings.popovers.auto_update')),
                LayoutFactory::field(
                    ButtonGroup::make('csrf_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.shield-check-bold'],
                        ])
                        ->value(config('app.csrf_enabled') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.csrf_enabled'))
                    ->popover(__('admin-main-settings.popovers.csrf_enabled')),
            ]),
        ])
            ->title(__('admin-main-settings.blocks.performance'))
            ->addClass('mb-2')
            ->description(__('admin-main-settings.blocks.performance_description'));
    }

    private function advancedDebugBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('debug')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.debug.off'),
                                'icon' => 'ph.bold.eye-slash-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.debug.on'),
                                'icon' => 'ph.bold.bug-bold',
                            ],
                        ])
                        ->value(is_development() || config('app.debug') ? '1' : '0')
                        ->disabled(is_development())
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.debug'))
                    ->popover(__('admin-main-settings.popovers.debug')),
                LayoutFactory::field(
                    ButtonGroup::make('development_mode')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.mode.production'),
                                'icon' => 'ph.bold.rocket-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.mode.development'),
                                'icon' => 'ph.bold.wrench-bold',
                            ],
                        ])
                        ->value(config('app.development_mode') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.site_mode'))
                    ->popover(__('admin-main-settings.popovers.development_mode')),
            ]),
            LayoutFactory::field(
                Input::make('debug_ips')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.debug_ips'))
                    ->value(is_array(config('app.debug_ips')) ? implode(', ', config('app.debug_ips')) : ''),
            )
                ->label(__('admin-main-settings.labels.debug_ips'))
                ->popover(__('admin-main-settings.popovers.debug_ips'))
                ->small(__('admin-main-settings.examples.debug_ips')),
        ])->title(__('admin-main-settings.blocks.debug_settings'))->addClass('mb-2');
    }

    private function additionalSettingsGeneralBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('share')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.share-network-bold'],
                        ])
                        ->value(config('app.share') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.share'))
                    ->small(__('admin-main-settings.labels.share_description')),
                LayoutFactory::field(
                    ButtonGroup::make('flute_copyright')
                        ->options([
                            '0' => ['label' => __('def.hide'), 'icon' => 'ph.bold.eye-slash-bold'],
                            '1' => ['label' => __('def.show'), 'icon' => 'ph.bold.eye-bold'],
                        ])
                        ->value(config('app.flute_copyright') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.copyright'))
                    ->small(__('admin-main-settings.labels.copyright_description')),
                LayoutFactory::field(
                    ButtonGroup::make('discord_link_roles')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.discord-logo-bold'],
                        ])
                        ->value(config('app.discord_link_roles') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.discord_link_roles'))
                    ->small(__('admin-main-settings.labels.discord_link_roles_description')),
            ]),
        ])->title(__('admin-main-settings.blocks.misc_settings'))->addClass('mb-3');
    }

    private function additionalSettingsImagesBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    Input::make('logo')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp, image/svg+xml')
                        ->defaultFile(!str_ends_with(config('app.logo'), '.svg') ? asset(config('app.logo')) : null),
                )->label(__('admin-main-settings.labels.logo')),
                LayoutFactory::field(
                    Input::make('logo_light')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp, image/svg+xml')
                        ->defaultFile(
                            !str_ends_with(config('app.logo_light', ''), '.svg')
                                ? asset(config('app.logo_light', ''))
                                : null,
                        ),
                )->label(__('admin-main-settings.labels.logo_light')),
                LayoutFactory::field(
                    Input::make('bg_image')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp')
                        ->defaultFile(config('app.bg_image') ? asset(config('app.bg_image')) : null),
                )
                    ->label(__('admin-main-settings.labels.bg_image'))
                    ->small(__('admin-main-settings.examples.bg_image')),
                LayoutFactory::field(
                    Input::make('bg_image_light')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp')
                        ->defaultFile(config('app.bg_image_light') ? asset(config('app.bg_image_light')) : null),
                )
                    ->label(__('admin-main-settings.labels.bg_image_light'))
                    ->small(__('admin-main-settings.examples.bg_image_light')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    Input::make('favicon')
                        ->type('file')
                        ->filePond()
                        ->accept('image/x-icon, image/vnd.microsoft.icon, .ico')
                        ->defaultFile(file_exists(public_path('favicon.ico')) ? asset('favicon.ico') : null),
                )->label(__('admin-main-settings.labels.favicon')),
                LayoutFactory::field(
                    Input::make('social_image')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png')
                        ->defaultFile(
                            file_exists(public_path('assets/img/social-image.png'))
                                ? asset('assets/img/social-image.png')
                                : null,
                        ),
                )->label(__('admin-main-settings.labels.social_image')),
            ]),
            LayoutFactory::rows([
                Button::make(__('admin-main-settings.buttons.save_flute_images'))
                    ->size('small')
                    ->type(Color::ACCENT)
                    ->method('saveFluteImages'),
            ]),
        ])->title(__('admin-main-settings.blocks.image_settings'));
    }
}
