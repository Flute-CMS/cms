<?php

namespace Flute\Core\Modules\Profile\Tabs\Edit;

use Flute\Core\Modules\Profile\Support\ProfileTab;
use Flute\Core\Database\Entities\User;

class MainTab extends ProfileTab
{
    public function getId(): string
    {
        return 'main';
    }

    public function getPath(): string
    {
        return 'main';
    }

    public function getIcon() : string
    {
        return 'ph.bold.gear-bold';
    }

    public function getDescription(): string|null
    {
        return __('profile.edit.main.description');
    }

    public function getTitle(): string
    {
        return __('profile.edit.main.title');
    }

    public function getContent(User $user)
    {
        return view('flute::partials.profile-tabs.edit.main', [
            'user' => $user
        ]);
    }
}
