<?php

namespace Flute\Core\Modules\Profile\Tabs\Edit;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Support\ProfileTab;

class SocialTab extends ProfileTab
{
    public function getId(): string
    {
        return 'social';
    }

    public function getPath(): string
    {
        return 'social';
    }

    public function getIcon(): string
    {
        return 'ph.bold.plugs-connected-bold';
    }

    public function getDescription(): string|null
    {
        return __('profile.edit.social.description');
    }

    public function getTitle(): string
    {
        return __('profile.edit.social.title');
    }

    public function getContent(User $user)
    {
        return view('flute::partials.profile-tabs.edit.social', [
            'user' => $user,
        ]);
    }
}
