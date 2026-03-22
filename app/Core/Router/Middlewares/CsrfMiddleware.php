<?php

namespace Flute\Core\Router\Middlewares;

use Closure;
use Flute\Core\Services\CsrfTokenService;
use Flute\Core\Support\BaseMiddleware;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class CsrfMiddleware extends BaseMiddleware
{
    protected CsrfTokenService $csrfTokenService;

    public function __construct(CsrfTokenService $csrfTokenService)
    {
        $this->csrfTokenService = $csrfTokenService;
    }

    public function handle(FluteRequest $request, Closure $next, ...$args): Response
    {
        if (!$this->isInstalled()) {
            return $next($request);
        }

        if ($this->shouldValidateToken($request) && !$this->isTokenValid($request)) {
            return $this->error()->forbidden(__('def.csrf_expired'));
        }

        $response = $next($request);

        if ($request->isXmlHttpRequest() || $request->headers->has('HX-Request')) {
            $response->headers->set('X-CSRF-Token', $this->csrfTokenService->getToken());
        }

        return $response;
    }

    protected function getRequestToken(FluteRequest $request): ?string
    {
        return (
            $request->input('_csrf_token') ?? $request->headers->get('X-CSRF-Token') ?? $request->input(
                'x-csrf-token',
            ) ?? $request->headers->get('x-csrf-token')
        );
    }

    private function isInstalled(): bool
    {
        return is_installed();
    }

    private function shouldValidateToken(FluteRequest $request): bool
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return false;
        }

        $enabled = (bool) config('app.csrf_enabled');

        if (!$enabled) {
            static $warned = false;
            if (!$warned) {
                logs()->warning(
                    'CSRF protection is disabled via configuration. This is a security risk in production.',
                );
                $warned = true;
            }
        }

        return $enabled;
    }

    private function isTokenValid(FluteRequest $request): bool
    {
        $token = $this->getRequestToken($request);

        if (!$token) {
            return false;
        }

        return $this->csrfTokenService->validateToken($token);
    }
}
