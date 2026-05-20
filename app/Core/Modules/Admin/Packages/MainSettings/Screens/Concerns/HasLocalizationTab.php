<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Layouts\LayoutFactory;

trait HasLocalizationTab
{
    private function localizationSettingsLayout()
    {
        return LayoutFactory::columns([
            LayoutFactory::block([
                LayoutFactory::field(
                    Select::make('locale')
                        ->placeholder(__('admin-main-settings.placeholders.locale'))
                        ->value(config('lang.locale'))
                        ->aligned()
                        ->options(array_combine(
                            config('lang.available'),
                            array_map(static fn($key) => __('langs.' . $key), config('lang.available')),
                        )),
                )->label(__('admin-main-settings.labels.locale')),
            ])->title(__('admin-main-settings.blocks.localization_settings')),
            LayoutFactory::block([
                LayoutFactory::view('admin-main-settings::languages', [
                    'languages' => config('lang.all'),
                    'available' => config('lang.available'),
                ]),
            ])->title(__('admin-main-settings.blocks.active_languages'))->description(__(
                'admin-main-settings.blocks.active_languages_description',
            )),
        ]);
    }
}
