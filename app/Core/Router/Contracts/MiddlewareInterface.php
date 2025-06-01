<?php

namespace Flute\Core\Router\Contracts;

use Closure;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareInterface
{
    public function handle(FluteRequest $request, Closure $next, ...$args): Response;
}
