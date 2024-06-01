<?php

namespace Flute\Core\Http\Controllers\Profile;

use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class ProfileRedirectController extends AbstractController
{
    public function search(FluteRequest $request, $value)
    {
        $redirectUrl = $request->input('else-redirect', null);

        $user = rep(UserSocialNetwork::class)->findOne([
            'value' => $value
        ]);

        if (!empty($redirectUrl) && empty($user))
            return redirect($redirectUrl);
        elseif (empty($user)) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if ($user->user->hidden === true && !user()->hasPermission('admin.users') && $user->user->id !== user()->id)
            return $this->error(__('profile.profile_hidden'));

        return redirect(url('profile/' . $user->user->getUrl())->get());
    }
}