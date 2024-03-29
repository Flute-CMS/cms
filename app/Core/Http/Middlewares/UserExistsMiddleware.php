<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Database\Entities\User;
use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class UserExistsMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        $profileId = $request->input('id');

        $user = rep(User::class)->findOne([
            is_numeric($profileId) ? 'id' : 'uri' => $profileId
        ]);

        abort_if(!empty($user), 404);

        if( $user->hidden === true && !user()->hasPermission('admin.users') && $user->id !== user()->id)
            return $this->error(__('profile.profile_hidden'));

        return $next($request);
    }
}