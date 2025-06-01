<?php

namespace Flute\Core\Router\Middlewares;

use Closure;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class CanMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param FluteRequest $request
     * @param Closure $next
     * @param mixed ...$args
     * @return Response
     */
    public function handle(FluteRequest $request, Closure $next, ...$args): Response
    {
        $permission = $args[0] ?? null;

        if (!$permission) {
            throw new \InvalidArgumentException('Permission parameter is required.');
        }

        if (!user()->can($permission)) {
            return $this->error()->unauthorized();
        }

        return $next($request);
    }
}
