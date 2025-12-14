<?php

namespace Flute\Core\Router\Contracts;

use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;

interface RouterInterface
{
    public function addRoute(array|string $methods, string $uri, array|string|object $action): RouteInterface;

    public function get(string $uri, array|string|object $action): RouteInterface;

    public function post(string $uri, array|string|object $action): RouteInterface;

    public function put(string $uri, array|string|object $action): RouteInterface;

    public function delete(string $uri, array|string|object $action): RouteInterface;

    public function any(string $uri, array|string|object $action): RouteInterface;

    public function match(array $methods, string $uri, array|string|object $action): RouteInterface;

    public function group(array|callable $attributes, ?callable $callback = null): void;

    public function getRoutes(): RouteCollection;

    public function middlewareGroup(string $name, array $middleware): void;

    public function dispatch(FluteRequest $request): Response;

    public function getCurrentRoute(): ?RouteInterface;

    public function hasRoute(string $uri, array|string $methods = []): bool;
}
