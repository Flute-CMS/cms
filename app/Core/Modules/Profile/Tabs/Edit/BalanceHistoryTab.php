<?php

namespace Flute\Core\Modules\Profile\Tabs\Edit;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Support\ProfileTab;

class BalanceHistoryTab extends ProfileTab
{
    public function getId(): string
    {
        return 'balance-history';
    }

    public function getPath(): string
    {
        return 'balance-history';
    }

    public function getIcon(): string
    {
        return 'ph.bold.clock-counter-clockwise-bold';
    }

    public function getDescription(): ?string
    {
        return __('profile.edit.balance_history.description');
    }

    public function getTitle(): string
    {
        return __('profile.edit.balance_history.title');
    }

    public function isFullWidth(): bool
    {
        return true;
    }

    public function getContent(User $user)
    {
        return view('flute::partials.profile-tabs.edit.balance-history', [
            'user' => $user,
        ]);
    }
}
