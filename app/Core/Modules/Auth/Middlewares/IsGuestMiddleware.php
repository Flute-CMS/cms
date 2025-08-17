<?php

namespace Flute\Core\Modules\Auth\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class IsGuestMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, \Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        if (!is_installed()) {
            return $next($request);
        }

        if (user()->isLoggedIn()) {
            return $this->error()->notFound();
        }

        return $next($request);
    }
}
