<?php

namespace Flute\Core\Support;

use Flute\Core\Router\Contracts\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class BaseMiddleware implements MiddlewareInterface
{
    /**
     * Returns an instance of ErrorHandler for error handling
     *
     * @return ErrorHandler
     */
    protected function error(): ErrorHandler
    {
        return new ErrorHandler($this);
    }

    /**
     * Returns an error for API or a regular response
     *
     * Supports the current functionality for calling through $this->error($message, $status)
     *
     * @param string|null $message Error message
     * @param int $status HTTP status code
     * @return Response
     */
    protected function errorResponse(string $message = null, int $status = 403): Response
    {
        return $this->respondWithError($message, $status);
    }

    /**
     * Common method for generating an error response
     *
     * @param string|null $message Error message
     * @param int $status HTTP status code
     * @return Response
     */
    public function respondWithError(string $message = null, int $status = 403): Response
    {
        if (request()->expectsJson() || request()->isAjax()) {
            return json([
                "error" => $message,
            ], $status);
        }

        // Устанавливаем флаг ошибки в сессии
        session()->set('error_page', true);
        session()->set('error_code', $status);
        session()->set('error_message', $message);

        throw new HttpException($status, $message);
    }

    /**
     * Dynamic method for handling errors through $this->error($message, $status)
     *
     * @param string|mixed ...$args
     * @return Response|ErrorHandler
     */
    public function __call($name, $arguments)
    {
        if ($name === 'error') {
            $message = $arguments[0] ?? null;
            $status = $arguments[1] ?? 403;

            return $this->errorResponse($message, $status);
        }

        throw new \BadMethodCallException("Method {$name} does not exist.");
    }
}
