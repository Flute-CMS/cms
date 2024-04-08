<?php

namespace Flute\Core\Support;

use Flute\Core\Exceptions\TooManyRequestsException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController
{
    protected array $middlewares = [];

    /**
     * Return error for API or View
     * 
     * @param string|array $message Error message
     * @param int $status HTTP Status code
     * 
     * @return Response
     */
    public function error($message, int $status = 403): Response
    {
        /** @var FluteRequest $request */
        $request = app(FluteRequest::class);

        if ($request->expectsJson() || $request->isAjax())
            return json([
                "error" => $message,
            ], $status);

        return response()->error($status, $message);
    }

    /**
     * Return success for API
     * 
     * @param string|array $message Error message
     * @param int $status HTTP Status code
     * 
     * @return JsonResponse
     */
    public function success($message = null, int $status = 200): JsonResponse
    {
        return json([
            "success" => $message,
        ], $status);
    }

    /**
     * Return flash message
     *
     * @param string $message Error message
     * @param string $type
     * @return void
     */
    public function flash(string $message, string $type = 'info'): void
    {
        flash()->add($type, $message);
    }

    /**
     * Return json response
     * 
     * @param array $data
     * @param int $status
     * 
     * @return JsonResponse
     */
    public function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    /**
     * Add a middleware inside a controller
     * 
     * @return array
     */
    public function middleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Returns middlewares list
     * 
     * @return array
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Check if csrf token is valid
     * 
     * @return bool
     */
    public function isCsrfValid(): bool
    {
        return template()->getBlade()->csrfIsValid();
    }

    /**
     * Throttle the requests to limit the number of attempts per minute.
     *
     * @param string $key The action key.
     * @param int $maxRequest The maximum number of requests allowed.
     * @param int $perMinute The time period in minutes.
     * @param int $burstiness The maximum number of requests in a burst.
     * @throws TooManyRequestsException
     */
    protected function throttle(string $key, int $maxRequest = 5, int $perMinute = 60, int $burstiness = 5): void
    {
        throttler()->throttle(
            ['action' => $key, request()->ip()],
            $maxRequest,
            $perMinute,
            $burstiness
        );
    }
}