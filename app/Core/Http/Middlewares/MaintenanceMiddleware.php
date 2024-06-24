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

        if( config('app.maintenance_mode') && !is_debug() ) {
            return $this->error(config('app.maintenance_message') ? __(config('app.maintenance_message')) : __('def.maintenance_mode'), 503);
        }

        return $next($request);
    }
}