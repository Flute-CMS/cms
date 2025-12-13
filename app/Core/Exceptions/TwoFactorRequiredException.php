<?php

namespace Flute\Core\Exceptions;

use Exception;
use Flute\Core\Database\Entities\User;

class TwoFactorRequiredException extends Exception
{
    protected User $user;

    public function __construct(User $user, string $message = 'Two-factor authentication required')
    {
        parent::__construct($message);
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
