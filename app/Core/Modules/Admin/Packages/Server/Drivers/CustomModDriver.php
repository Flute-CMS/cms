<?php

namespace Flute\Admin\Packages\Server\Drivers;

use Flute\Admin\Packages\Server\Contracts\ModDriverInterface;

class CustomModDriver implements ModDriverInterface
{
    /**
     * Get the driver name.
     */
    public function getName(): string
    {
        return __('admin-server.mods.custom');
    }

    /**
     * Get the settings view for this driver.
     */
    public function getSettingsView(): string
    {
        return 'admin-server::mods.custom';
    }

    /**
     * Get validation rules for this driver's settings.
     */
    public function getValidationRules(): array
    {
        return [
            'custom_settings__name' => ['required', 'string'],
            'custom_settings__json' => ['required', 'string'],
        ];
    }
}
