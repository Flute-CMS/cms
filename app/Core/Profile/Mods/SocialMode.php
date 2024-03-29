<?php

namespace Flute\Core\Profile\Mods;

use Flute\Core\Contracts\ProfileModInterface;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;

class SocialMode implements ProfileModInterface
{
    public function getKey() : string
    {
        return 'social';
    }

    public function render(User $user): string
    {
        return render('pages/profile/edit/social', [
            "user" => user()->getCurrentUser(),
            "socials" => $this->getEnabledSocialNetworks()
        ], true);
    }

    protected function getEnabledSocialNetworks() : array
    {
        return rep(SocialNetwork::class)->findAll([
            'enabled' => 1
        ]);
    }

    public function getSidebarInfo() : array
    {
        return [
            'name' => 'profile.settings.social',
            'desc' => 'profile.settings.social_desc',
            'icon' => 'ph ph-link-simple',
        ];
    }
}