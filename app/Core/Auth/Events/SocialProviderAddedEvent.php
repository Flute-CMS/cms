<?php

namespace Flute\Core\Auth\Events;

use Flute\Core\Database\Entities\SocialNetwork;

class SocialProviderAddedEvent
{
    public const NAME = 'flute.social_provider_added';

    private SocialNetwork $socialNetwork;

    public function __construct(SocialNetwork $socialNetwork)
    {
        $this->socialNetwork = $socialNetwork;
    }

    public function getSocialNetwork(): SocialNetwork
    {
        return $this->socialNetwork;
    }
}
