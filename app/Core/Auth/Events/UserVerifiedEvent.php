<?php

namespace Flute\Core\Auth\Events;

use Flute\Core\Database\Entities\User;

class UserVerifiedEvent
{
    public const NAME = 'flute.user_verified';

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
