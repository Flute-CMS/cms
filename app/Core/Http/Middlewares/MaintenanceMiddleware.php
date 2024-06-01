<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class MaintenanceMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        abort_if((bool) config('app.maintenance_mode') === false || is_debug(), 503, __('def.maintenance_mode'));

        return $next($request);
    }
}