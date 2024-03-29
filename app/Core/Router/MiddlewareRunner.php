<?php

namespace Flute\Core\Router;

use Exception;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class MiddlewareRunner
{
    private array $middlewares;
    private FluteRequest $request;
    private $controller;

    /**
     * Constructor for MiddlewareRunner.
     * 
     * @param array   $middlewares The middlewares to run.
     * @param FluteRequest $request     The request to pass to the middlewares.
     * @param mixed   $controller  The controller to execute after running the middlewares.
     */
    public function __construct(array $middlewares, FluteRequest $request, $controller)
    {
        $this->middlewares = $middlewares;
        $this->request = $request;
        $this->controller = $controller;
    }

    /**
     * Runs the middlewares and executes the controller.
     *
     * @return Response The response returned by the controller.
     * @throws Exception
     */
    public function run(): Response
    {
        return $this->runMiddleware(0);
    }

    /**
     * Recursively runs the middlewares.
     *
     * @param int $index The index of the current middleware in the $middlewares array.
     *
     * @return Response The response returned by the current middleware or the next middleware in the array.
     * @throws Exception
     */
    private function runMiddleware(int $index): Response
    {
        if ($index === count($this->middlewares)) {
            $controllerResponse = call_user_func($this->controller, $this->request);
            if (!($controllerResponse instanceof Response)) {
                throw new Exception('Controller must return a Response object');
            }
            return $controllerResponse;
        }

        $middleware = $this->middlewares[$index];
        $next = function (FluteRequest $request) use ($index) {
            return $this->runMiddleware($index + 1);
        };

        $response = call_user_func($middleware, $this->request, $next);

        if (!($response instanceof Response)) {
            throw new Exception('Middleware must return a Response object');
        }

        return $response;
    }
}