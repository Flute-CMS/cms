<?php

namespace Flute\Core\Support;

use Flute\Core\Contracts\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    /**
     * Return error for API
     *
     * @param string|null $message Error message
     * @param int $status HTTP Status code
     *
     * @return Response
     */
    protected function error(string $message = null, int $status = 403): Response
    {
        if (request()->expectsJson() || request()->isAjax())
            return json([
                "error" => $message,
            ], $status);

        return response()->error($status, $message);
    }
}