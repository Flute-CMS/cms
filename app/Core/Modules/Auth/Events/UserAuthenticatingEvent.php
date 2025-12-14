<?php

namespace Flute\Core\Modules\Auth\Events;

class UserAuthenticatingEvent
{
    public const NAME = 'user.authenticating';

    public array $credentials;

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }
}
