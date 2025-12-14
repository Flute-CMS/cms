<?php

namespace Flute\Core\Modules\Auth\Events;

use Flute\Core\Database\Entities\User;

class UserLoggedInEvent
{
    public const NAME = 'flute.user_logged_in';

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
