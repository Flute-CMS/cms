<?php

namespace Flute\Core\Modules\Profile\Components;

use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Support\FluteComponent;

class EditSocialsComponent extends FluteComponent
{
    public $socialKey = null;
    public $checked = null;

    public function changeVisibility()
    {
        if (
            $this->validate([
                'socialKey' => 'required',
            ])
        ) {
            $user = user()->getCurrentUser();

            $socialNetwork = $user->getSocialNetwork($this->socialKey);

            if ($socialNetwork) {
                $socialNetwork->hidden = !$socialNetwork->hidden;

                transaction($socialNetwork)->run();

                $this->checked = !$socialNetwork->hidden;
            }
        }
    }

    public function removeSocial()
    {
        if (!$this->validate(['socialKey' => 'required|string'])) {
            return;
        }
        $user = user()->getCurrentUser();
        $socialNetwork = $user->getSocialNetwork($this->socialKey);
        if (!$socialNetwork) {
            return;
        }
        if (empty($user->password) && count($user->socialNetworks) === 1) {
            $this->flashMessage(__('profile.edit.social.last_social_network'), 'error');

            return;
        }
        transaction($socialNetwork, 'delete')->run();
        $this->flashMessage(__('profile.social_deleted'), 'success');

        $this->redirectTo(url('profile/settings')->addParams(['tab' => 'social']), 500);
    }

    public function render()
    {
        return $this->view('flute::components.profile-tabs.edit.social', [
            'user' => user()->getCurrentUser(),
            "socials" => SocialNetwork::findAll([
                'enabled' => 1,
            ]),
        ]);
    }
}
