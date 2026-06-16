<?php

namespace Flute\Core\Modules\Auth\Events;

use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;

class SocialProviderAddedEvent
{
    public const NAME = 'flute.social_provider_added';

    private SocialNetwork $socialNetwork;

    private ?User $user;

    public function __construct(SocialNetwork $socialNetwork, ?User $user = null)
    {
        $this->socialNetwork = $socialNetwork;
        $this->user = $user;
    }

    public function getSocialNetwork(): SocialNetwork
    {
        return $this->socialNetwork;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
