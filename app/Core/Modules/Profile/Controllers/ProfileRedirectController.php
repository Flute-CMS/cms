<?php

namespace Flute\Core\Modules\Profile\Controllers;

use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Modules\Profile\Events\ProfileSearchEvent;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class ProfileRedirectController extends BaseController
{
    public function search(FluteRequest $request, $value)
    {
        $redirectUrl = $request->input('else-redirect', null);

        $userNetwork = UserSocialNetwork::query()->where('value', $value)->load('user')->fetchOne();

        if (empty($userNetwork)) {
            $event = events()->dispatch(new ProfileSearchEvent($value), ProfileSearchEvent::NAME);

            $user = $event->getUser();
        } else {
            $user = $userNetwork->user;
        }

        if (! empty($redirectUrl) && empty($user)) {
            return redirect($redirectUrl);
        } elseif (empty($user)) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if ($user->hidden === true && ! user()->can('admin.users') && $user->id !== user()->id)
            return $this->error(__('profile.profile_hidden'));

        return redirect(url('profile/'.$user->getUrl()));
    }
}