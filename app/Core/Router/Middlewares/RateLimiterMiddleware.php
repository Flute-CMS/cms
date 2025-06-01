<?php

namespace Flute\Core\Router\Middlewares;

use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimiterMiddleware extends BaseMiddleware
{
    protected RateLimiterFactory $rateLimiterFactory;

    public function __construct(RateLimiterFactory $rateLimiterFactory)
    {
        $this->rateLimiterFactory = $rateLimiterFactory;
    }

    public function handle(FluteRequest $request, \Closure $next, ...$args): Response
    {
        $limiter = $this->rateLimiterFactory->create($this->getLimiterKey($request));

        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->error()->tooManyRequests();
        }

        return $next($request);
    }

    protected function getLimiterKey(FluteRequest $request): string
    {
        return $request->getClientIp();
    }
}
