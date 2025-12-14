<?php

namespace Flute\Core\Modules\Profile\Tabs\Edit;

use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Profile\Support\ProfileTab;

class PaymentsTab extends ProfileTab
{
    public function getId(): string
    {
        return 'payments';
    }

    public function getPath(): string
    {
        return 'payments';
    }

    public function getIcon(): string
    {
        return 'ph.bold.credit-card-bold';
    }

    public function getDescription(): string|null
    {
        return __('profile.edit.payments.description');
    }

    public function getTitle(): string
    {
        return __('profile.edit.payments.title');
    }

    public function getContent(User $user)
    {
        return view('flute::partials.profile-tabs.edit.payments', [
            'user' => $user,
        ]);
    }
}
