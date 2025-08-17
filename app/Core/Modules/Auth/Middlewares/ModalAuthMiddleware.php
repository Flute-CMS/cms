<?php

namespace Flute\Core\Modules\Auth\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for handling modal authentication logic
 */
class ModalAuthMiddleware extends BaseMiddleware
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
        $useModalAuth = config('auth.only_modal');

        if ($useModalAuth && !$request->htmx()->isHtmxRequest()) {
            return $this->error()->notFound();
        }

        return $next($request);
    }
}
