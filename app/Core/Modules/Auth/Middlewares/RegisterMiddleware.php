<?php

namespace Flute\Core\Modules\Auth\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for handling register authentication logic
 */
class RegisterMiddleware extends BaseMiddleware
{
    /**
     * Handle the request
     *
     * @param FluteRequest $request The request instance
     * @param \Closure $next The next middleware
     * @param mixed ...$args Additional arguments
     * @return Response
     */
    public function handle(FluteRequest $request, \Closure $next, ...$args): Response
    {
        if (config('auth.only_social')) {
            return $this->error()->notFound();
        }

        return $next($request);
    }
}
