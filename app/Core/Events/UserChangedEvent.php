<?php

namespace Flute\Core\Events;

use Flute\Core\Database\Entities\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserChangedEvent extends Event
{
    public const NAME = 'flute.user.changed';

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