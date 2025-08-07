<?php

namespace Flute\Core\Exceptions;

use Hybridauth\User\Profile;

class NeedRegistrationException extends \Exception
{
    protected Profile $profile;

    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }
}
