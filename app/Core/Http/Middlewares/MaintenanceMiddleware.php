<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class MaintenanceMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        $path = $request->getPathInfo();

        if ($path === '/login' || strpos($path, '/social/') === 0) {
            return $next($request);
        }

        if (user()->hasPermission('admin')) {
            return $next($request);
        }

        abort_if((bool) config('app.maintenance_mode') === false || is_debug(), 503, __('def.maintenance_mode'));

        return $next($request);
    }
}