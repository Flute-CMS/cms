<?php

namespace Flute\Core\Exceptions;

use Exception;
use Flute\Core\Database\Entities\SocialNetwork;
use Hybridauth\User\Profile;

class NeedRegistrationException extends Exception
{
    protected Profile $profile;

    protected SocialNetwork $socialNetwork;

    public function __construct(Profile $profile, SocialNetwork $socialNetwork)
    {
        $this->profile = $profile;
        $this->socialNetwork = $socialNetwork;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }

    public function getSocialNetwork(): SocialNetwork
    {
        return $this->socialNetwork;
    }
}
