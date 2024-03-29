<?php

namespace Flute\Core\Auth\Events;

use Flute\Core\Database\Entities\User;

class SocialLoggedInEvent
{
    public const NAME = 'flute.social_logged_in';

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
