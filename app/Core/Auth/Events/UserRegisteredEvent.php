<?php

namespace Flute\Core\Auth\Events;

use Flute\Core\Database\Entities\User;

class UserRegisteredEvent
{
    public const NAME = 'flute.user_registered';

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
