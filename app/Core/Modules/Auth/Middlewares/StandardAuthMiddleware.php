<?php

namespace Flute\Core\Modules\Auth\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for handling standard authentication logic
 */
class StandardAuthMiddleware extends BaseMiddleware
{
    /**
     * Handle the request
     *
     * @param FluteRequest $request The request instance
     * @param \Closure $next The next middleware
     * @param mixed ...$args Additional arguments
     * @return Response
     */
    public function handle(FluteRequest $request, \Closure $next, ...$args) : Response
    {
        $isSocialEmpty = social()->isEmpty();
        $allowStandardAuth = !config('auth.only_social', false) || (config('auth.only_social') && $isSocialEmpty);

        if (!$allowStandardAuth) {
            return $this->error()->notFound();
        }

        if (config('auth.only_modal') && !$request->htmx()->isHtmxRequest()) {
            return $this->error()->notFound();
        }

        return $next($request);
    }
}