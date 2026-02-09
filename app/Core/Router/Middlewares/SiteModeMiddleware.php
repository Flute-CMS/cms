<?php

namespace Flute\Core\Router\Middlewares;

use Closure;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class SiteModeMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param mixed ...$args
     */
    public function handle(FluteRequest $request, Closure $next, ...$args): Response
    {
        $feature = $args[0] ?? null;

        if (!$feature) {
            throw new InvalidArgumentException('Site mode feature parameter is required.');
        }

        $configKey = 'app.' . $feature . '_enabled';

        if (!config($configKey, true)) {
            if (user()->isLoggedIn() && user()->can('admin')) {
                return $next($request);
            }

            return $this->error()->notFound();
        }

        return $next($request);
    }
}
