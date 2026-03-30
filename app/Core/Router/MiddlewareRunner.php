<?php

namespace Flute\Core\Router;

use Closure;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class MiddlewareRunner
{
    protected array $middleware;

    protected FluteRequest $request;

    protected Closure $destination;

    protected int $current = 0;

    public function __construct(array $middleware, FluteRequest $request, Closure $destination)
    {
        $this->middleware = $middleware;
        $this->request = $request;
        $this->destination = $destination;
    }

    /**
     * Run the middleware pipeline
     */
    public function run(): Response
    {
        return $this->process($this->request);
    }

    /**
     * Process the next middleware in the pipeline
     */
    protected function process(FluteRequest $request): Response
    {
        if ($this->current >= count($this->middleware)) {
            return ( $this->destination )($request);
        }

        $middleware = $this->middleware[$this->current];
        $this->current++;

        try {
            return $middleware($request, fn($request) => $this->process($request));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if (function_exists('is_debug') && is_debug()) {
                throw $e;
            }

            if (function_exists('logs')) {
                logs()->error('Middleware failed: ' . $e->getMessage(), ['exception' => $e]);
            }

            \Flute\Core\Services\CrashReportService::capture($e, ['source' => 'middleware']);

            return response()->error(500, __('def.internal_server_error'));
        }
    }
}
