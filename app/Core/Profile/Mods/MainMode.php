<?php

namespace Flute\Core\Profile\Mods;

use Flute\Core\Contracts\ProfileModInterface;
use Flute\Core\Database\Entities\User;

class MainMode implements ProfileModInterface
{
    public function getKey() : string
    {
        return 'main';
    }

    public function render(User $user): string
    {
        return render('pages/profile/edit/main', [
            "user" => user()->getCurrentUser()
        ], true);
    }

    public function getSidebarInfo() : array
    {
        return [
            'icon' => 'ph ph-user-circle',
            'name' => 'profile.settings.main',
            'desc' => 'profile.settings.main_desc',
        ];
    }
}