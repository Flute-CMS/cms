<?php

namespace Flute\Core\Modules\Profile\Events;

use Flute\Core\Database\Entities\User;

/**
 * Event for searching a user by request value.
 */

class ProfileSearchEvent
{
    public const NAME = 'profile.search';

    private ?User $user;
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
        $this->user = null;
    }

    public function getUser() : ?User
    {
        return $this->user;
    }

    public function setUser(User $user) : void
    {
        $this->user = $user;
    }

    public function getValue() : string
    {
        return $this->value;
    }
}