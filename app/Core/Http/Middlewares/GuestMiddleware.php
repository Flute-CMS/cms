<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class GuestMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        if( !is_installed() ) return $next($request);

        abort_if(!user()->isLoggedIn(), 404);

        return $next($request);
    }
}