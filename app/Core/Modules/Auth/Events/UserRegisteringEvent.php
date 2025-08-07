<?php

namespace Flute\Core\Modules\Auth\Events;

use Flute\Core\Database\Entities\User;

class UserRegisteringEvent
{
    public const NAME = 'user.registering';

    public User $user;
    public array $credentials;

    public function __construct(User $user, array $credentials)
    {
        $this->user = $user;
        $this->credentials = $credentials;
    }
}
