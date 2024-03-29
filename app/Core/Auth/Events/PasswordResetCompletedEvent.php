<?php

namespace Flute\Core\Auth\Events;

use Flute\Core\Database\Entities\User;

class PasswordResetCompletedEvent
{
    public const NAME = 'flute.password_reset_completed';

    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
