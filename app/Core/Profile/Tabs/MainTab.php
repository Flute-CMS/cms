<?php

namespace Flute\Core\Profile\Tabs;

use Flute\Core\Contracts\ProfileTabInterface;
use Flute\Core\Database\Entities\User;

class MainTab implements ProfileTabInterface
{
    public function render(User $user)
    {
        // Will be in the future
        return '';
    }

    public function getSidebarInfo()
    {
        return [
            'name' => 'def.main',
            'icon' => 'ph ph-house',
            'position' => -1
        ];
    }

    public function getKey()
    {
        return 'main';
    }
}