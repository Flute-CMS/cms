<?php

namespace Flute\Core\Modules\Installer\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class IsInstalledMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, \Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        abort_if(is_installed(), 404);

        return $next($request);
    }
}
