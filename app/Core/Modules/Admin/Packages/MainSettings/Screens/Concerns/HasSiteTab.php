<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Layouts\LayoutFactory;

trait HasSiteTab
{
    private function siteSettingsLayout()
    {
        return LayoutFactory::tabs([
            Tab::make(__('admin-main-settings.blocks.features'))
                ->icon('ph.bold.toggle-left-bold')
                ->layouts([
                    $this->mainSettingsSiteModeBlock(),
                ]),
            Tab::make(__('admin-main-settings.blocks.personal_cabinet_settings'))
                ->icon('ph.bold.wallet-bold')
                ->layouts([
                    $this->mainSettingsPersonalCabinetBlock(),
                ]),
        ])
            ->slug('site_settings_sections')
            ->pills()
            ->sticky(false)
            ->lazyload(false);
    }
}
