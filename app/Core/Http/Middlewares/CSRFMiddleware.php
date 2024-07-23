<?php

namespace Flute\Core\Http\Middlewares;

use Flute\Core\Services\CsrfTokenService;
use Flute\Core\Support\AbstractMiddleware;
use Flute\Core\Support\FluteRequest;

class CSRFMiddleware extends AbstractMiddleware
{
    public function __invoke(FluteRequest $request, \Closure $next)
    {
        if (!$this->isInstalled()) {
            return $next($request);
        }

        if ($this->isApiRequest()) {
            return $next($request);
        }

        if ($this->shouldValidateToken($request) && !$this->isTokenValid()) {
            return $this->error(__('def.csrf_expired'));
        }

        return $next($request);
    }

    private function isInstalled(): bool
    {
        return is_installed();
    }

    private function isApiRequest(): bool
    {
        return user()->isLoggedIn() && user()->getCurrentUser()->id === 'API_ID';
    }

    private function shouldValidateToken(FluteRequest $request): bool
    {
        return !$request->isMethod('GET') && config('auth.csrf_enabled');
    }

    private function isTokenValid(): bool
    {
        return app(CsrfTokenService::class)->validateToken();
    }
}
