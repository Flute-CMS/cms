<?php

namespace Flute\Core\Contracts;

use Flute\Core\Support\FluteRequest;

interface MiddlewareInterface
{
    public function __invoke(FluteRequest $request, \Closure $next);
}