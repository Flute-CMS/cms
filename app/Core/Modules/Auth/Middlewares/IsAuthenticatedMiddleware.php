<?php

namespace Flute\Core\Modules\Auth\Middlewares;

use Closure;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class IsAuthenticatedMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        if (!user()->isLoggedIn()) {
            return $this->error()->notFound();
        }

        return $next($request);
    }
}
