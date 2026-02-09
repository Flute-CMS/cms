<?php

namespace Flute\Core\Modules\Installer\Middlewares;

use Closure;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class IsInstalledMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        $lockFile = storage_path('.installed');
        $isInstalled = is_installed() || file_exists($lockFile);

        abort_if($isInstalled, 404);

        return $next($request);
    }
}
