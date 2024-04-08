<?php

namespace Flute\Core\Profile\Mods;

use Flute\Core\Contracts\ProfileModInterface;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;

class ThemeMode implements ProfileModInterface
{
    public function getKey() : string
    {
        return 'theme';
    }

    public function render(User $user): string
    {
        return render('pages/profile/edit/theme', [
            "user" => user()->getCurrentUser(),
        ], true);
    }

    public function getSidebarInfo() : array
    {
        return [
            'name' => 'profile.settings.theme',
            'desc' => 'profile.settings.theme_desc',
            'icon' => 'ph ph-paint-roller',
        ];
    }
}