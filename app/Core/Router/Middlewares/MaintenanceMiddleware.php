<?php

namespace Flute\Core\Router\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class MaintenanceMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, \Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        if (!is_installed()) {
            return $next($request);
        }

        $path = $request->getPathInfo();

        if ($path === '/login' || $path === '/live' || strpos($path, '/social/') === 0) {
            return $next($request);
        }

        if (user()->can('admin')) {
            return $next($request);
        }

        if (config('app.maintenance_mode') && !is_debug()) {
            return $this->error()->custom(config('app.maintenance_message') ? __(config('app.maintenance_message')) : __('def.maintenance_mode'), 503);
        }

        return $next($request);
    }
}
