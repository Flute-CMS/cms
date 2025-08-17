<?php

namespace Flute\Core\Modules\Profile\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class UserExistsMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, \Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        $profileId = $request->input('id');

        if (is_numeric($profileId)) {
            $user = user()->get(intval($profileId));
        } else {
            $user = user()->getByRoute($profileId);
        }

        if (empty($user)) {
            return $this->error()->notFound();
        }

        if ($user->hidden === true && !user()->can('admin.users') && $user->id !== user()->id) {
            return $this->error()->custom(__('profile.profile_hidden'), 404);
        }

        return $next($request);
    }
}
