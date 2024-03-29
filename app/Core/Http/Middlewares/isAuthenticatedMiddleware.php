<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class isAuthenticatedMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        abort_if(user()->isLoggedIn(), 404);

        return $next($request);
    }
}