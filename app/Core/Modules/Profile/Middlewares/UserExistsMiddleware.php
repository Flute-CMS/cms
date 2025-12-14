<?php

namespace Flute\Core\Modules\Profile\Middlewares;

use Closure;
use Flute\Core\Database\Entities\User;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class UserExistsMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        $profileId = $request->input('id');

        $isNumeric = is_numeric($profileId);
        $cacheKey = $isNumeric
            ? 'profile.exists.id.' . (int) $profileId
            : 'profile.exists.uri.' . sha1((string) $profileId);

        $cached = cache()->get($cacheKey);
        if (is_array($cached)) {
            if ($cached['hidden'] === true && (!user()->isLoggedIn() || (!user()->can('admin.users') && user()->id !== $cached['id']))) {
                return $this->error()->custom(__('profile.profile_hidden'), 404);
            }

            return $next($request);
        }

        if ($isNumeric) {
            $found = User::query()->where(['id' => (int) $profileId])->fetchOne();
        } else {
            $found = User::query()->where(['uri' => (string) $profileId])->fetchOne();
        }

        if (empty($found)) {
            return $this->error()->notFound();
        }

        cache()->set($cacheKey, ['id' => $found->id, 'hidden' => (bool) $found->hidden], 600);

        if ($found->hidden === true && (!user()->isLoggedIn() || (!user()->can('admin.users') && user()->id !== $found->id))) {
            return $this->error()->custom(__('profile.profile_hidden'), 404);
        }

        return $next($request);
    }
}
