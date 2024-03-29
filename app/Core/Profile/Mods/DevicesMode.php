<?php

namespace Flute\Core\Profile\Mods;

use Flute\Core\Contracts\ProfileModInterface;
use Flute\Core\Database\Entities\User;

class DevicesMode implements ProfileModInterface
{
    public function getKey() : string
    {
        return 'devices';
    }

    public function render(User $user): string
    {
        return 'devices';
    }

    public function getSidebarInfo() : array
    {
        return [
            'icon' => 'ph ph-devices',
            'name' => 'profile.settings.devices',
            'desc' => 'profile.settings.devices_desc',
        ];
    }
}