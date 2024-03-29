<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class ProfileChangeMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        abort_if(user()->isLoggedIn(), 404, 'You must be logged in!');

        return $next($request);
    }
}