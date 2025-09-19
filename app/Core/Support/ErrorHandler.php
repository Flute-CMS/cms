<?php

namespace Flute\Core\Support;

use Symfony\Component\HttpFoundation\Response;

class ErrorHandler
{
    protected $middleware;

    public function __construct(BaseMiddleware|BaseController $middleware)
    {
        $this->middleware = $middleware;
    }

    public function notFound(?string $message = null): Response
    {
        $defaultMessage = __('def.page_not_found');

        return $this->middleware->respondWithError($message ?? $defaultMessage, 404);
    }

    public function forbidden(?string $message = null): Response
    {
        $defaultMessage = __('def.forbidden');

        return $this->middleware->respondWithError($message ?? $defaultMessage, 403);
    }

    public function internalError(?string $message = null): Response
    {
        $defaultMessage = __('def.internal_server_error');

        return $this->middleware->respondWithError($message ?? $defaultMessage, 500);
    }

    public function unauthorized(?string $message = null): Response
    {
        $defaultMessage = __('def.unauthorized_access');

        return $this->middleware->respondWithError($message ?? $defaultMessage, 401);
    }

    public function badRequest(?string $message = null): Response
    {
        $defaultMessage = __('def.bad_request');

        return $this->middleware->respondWithError($message ?? $defaultMessage, 400);
    }

    public function tooManyRequests(?string $message = null): Response
    {
        $defaultMessage = __('def.too_many_requests');

        return $this->middleware->respondWithError($message ?? $defaultMessage, 429);
    }

    public function custom(string $message, int $status): Response
    {
        return $this->middleware->respondWithError($message, $status);
    }
}
