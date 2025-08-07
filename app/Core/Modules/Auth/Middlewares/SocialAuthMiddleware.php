<?php

namespace Flute\Core\Modules\Auth\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for handling social authentication logic
 */
class SocialAuthMiddleware extends BaseMiddleware
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
        $isSocialEmpty = social()->isEmpty();

        if ($isSocialEmpty && config('auth.only_social', false)) {
            return $this->error()->notFound();
        }

        return $next($request);
    }
}
