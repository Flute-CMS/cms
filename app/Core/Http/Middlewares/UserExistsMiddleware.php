<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class UserExistsMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        $profileId = $request->input('id');

        if( is_numeric($profileId) ) {
            $user = user()->get(intval($profileId));
        } else {
            $user = user()->getByRoute($profileId);
        }

        abort_if(!empty($user), 404);

        if( $user->hidden === true && !user()->hasPermission('admin.users') && $user->id !== user()->id)
            return $this->error(__('profile.profile_hidden'));

        return $next($request);
    }
}