<?php

namespace Flute\Core\Modules\Profile\Tabs\Edit;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Support\ProfileTab;

class NotificationsTab extends ProfileTab
{
    public function getId(): string
    {
        return 'notifications';
    }

    public function getPath(): string
    {
        return 'notifications';
    }

    public function getIcon(): string
    {
        return 'ph.bold.bell-bold';
    }

    public function getDescription(): ?string
    {
        return __('profile.edit.notifications.description');
    }

    public function getTitle(): string
    {
        return __('profile.edit.notifications.title');
    }

    public function getOrder(): int
    {
        return 90;
    }

    public function getContent(User $user)
    {
        return view('flute::partials.profile-tabs.edit.notifications', [
            'user' => $user,
        ]);
    }
}
