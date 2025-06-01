<?php

namespace Flute\Core\Router\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;

class HtmxMiddleware extends BaseMiddleware
{
    public function handle(FluteRequest $request, \Closure $next, ...$args): \Symfony\Component\HttpFoundation\Response
    {
        if (!$request->htmx()->isHtmxRequest())
            return $this->error()->notFound();

        return $next($request);
    }
}