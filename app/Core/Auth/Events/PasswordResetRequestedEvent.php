<?php

namespace Flute\Core\Auth\Events;

use Flute\Core\Database\Entities\PasswordResetToken;
use Flute\Core\Database\Entities\User;

class PasswordResetRequestedEvent
{
    public const NAME = 'flute.password_reset_requested';

    private User $user;
    private PasswordResetToken $token;

    public function __construct(User $user, PasswordResetToken $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function getToken(): PasswordResetToken
    {
        return $this->token;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
