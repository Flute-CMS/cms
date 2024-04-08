<?php

namespace Flute\Core\Profile\Mods;

use Flute\Core\Contracts\ProfileModInterface;
use Flute\Core\Database\Entities\User;
use WhichBrowser\Parser;

class DevicesMode implements ProfileModInterface
{
    public function getKey(): string
    {
        return 'devices';
    }

    public function render(User $user): string
    {
        $devices = $user->getUserDevices();
        $data = [];

        foreach ($devices as $key => $val) {
            $result = new Parser();
            $result->setCache(cache()->getAdapter());
            $result->analyse($val->deviceDetails);

            $data[] = [
                'id' => $val->id,
                'is_mobile' => $result->isMobile(),
                'platform' => $result->device->toString(),
                'browser' => $result->browser->toString(),
                'ip' => $val->ip
            ];
        }

        return render('pages/profile/edit/devices', [
            "devices" => $data
        ], true);
    }

    public function getSidebarInfo(): array
    {
        return [
            'icon' => 'ph ph-devices',
            'name' => 'profile.settings.devices',
            'desc' => 'profile.settings.devices_desc',
        ];
    }
}