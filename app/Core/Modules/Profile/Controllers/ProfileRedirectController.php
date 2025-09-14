<?php

namespace Flute\Core\Modules\Profile\Controllers;

use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Modules\Profile\Events\ProfileSearchEvent;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class ProfileRedirectController extends BaseController
{
    /**
     * Cached profile redirect resolution within one request to save DB round-trips.
     */
    private static array $resolveCache = [];

    public function search(FluteRequest $request, $value)
    {
        $redirectUrl = $request->input('else-redirect', null);
        $stringValue = (string) $value;

        $cacheKey = 'profile.search.resolve.' . sha1($stringValue);
        $cachedTarget = cache()->get($cacheKey);
        if (is_string($cachedTarget) && $cachedTarget !== '') {
            return redirect(url('profile/' . $cachedTarget));
        }

        if (preg_match('/^\d{1,9}$/', $stringValue) === 1) {
            $fastUser = user()->get((int) $stringValue);
            if ($this->isValidUser($fastUser)) {
                cache()->set($cacheKey, $fastUser->getUrl(), 86400);
                return redirect(url('profile/' . $fastUser->getUrl()));
            }
        }

        if (isset(self::$resolveCache[$value])) {
            $user = self::$resolveCache[$value];
        } else {
            $event = events()->dispatch(new ProfileSearchEvent($stringValue), ProfileSearchEvent::NAME);
            $candidate = $event->getUser();
            $user = $this->isValidUser($candidate) ? $candidate : null;

            if (!$this->isValidUser($user)) {
                $userNetwork = UserSocialNetwork::query()->where('value', $stringValue)->load('user')->fetchOne();
                $user = $userNetwork?->user;
            }

            self::$resolveCache[$value] = $user;
        }

        if (!empty($redirectUrl) && empty($user)) {
            return redirect($redirectUrl);
        } elseif (empty($user)) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if (!$this->isValidUser($user)) {
            return $this->error(__('def.user_not_found'), 404);
        }

        if ($user->hidden === true && !user()->can('admin.users') && $user->id !== user()->id) {
            return $this->error(__('profile.profile_hidden'));
        }

        cache()->set($cacheKey, $user->getUrl(), 86400);

        return redirect(url('profile/'.$user->getUrl()));
    }

    private function isValidUser($user): bool
    {
        return $user instanceof \Flute\Core\Database\Entities\User && isset($user->id) && is_int($user->id) && $user->id > 0;
    }
}
