<?php

namespace Flute\Core\Router;

use Symfony\Component\Routing\Route;

class RouteGroup
{
    private RouteDispatcher $routeDispatcher;
    private string $pathPrefix;
    private array $middlewares = [];

    /**
     * Constructor for RouteGroup.
     * 
     * @param RouteDispatcher $routeDispatcher The route dispatcher for the group.
     * @param string          $pathPrefix      The path prefix for the group.
     */
    public function __construct(RouteDispatcher $routeDispatcher, string $pathPrefix = '')
    {
        $this->routeDispatcher = $routeDispatcher;
        $this->pathPrefix = $pathPrefix;
    }

    /**
     * Adds a route to the route collection.
     * 
     * @param string $method  The HTTP method for the route.
     * @param string $path    The path for the route.
     * @param mixed  $handler The handler for the route.
     * @param array $middlewares The middlewares for the route.
     * 
     * @return Route The added route.
     */
    public function add(string $method, string $path, $handler, array $middlewares = []): Route
    {
        return $this->routeDispatcher->add($method, $this->pathPrefix . $path, $handler, array_merge($this->middlewares, $middlewares));
    }

    /**
     * Adds a GET route to the route collection.
     * 
     * @param string $path    The path for the route.
     * @param mixed  $handler The handler for the route.
     * @param array|null $middlewares Optional middlewares for the route.
     * 
     * @return Route The added route.
     */
    public function get(string $path, $handler, array $middlewares = []): Route
    {
        return $this->add('GET', $path, $handler, $middlewares);
    }

    /**
     * Adds a POST route to the route collection.
     * 
     * @param string $path    The path for the route.
     * @param mixed  $handler The handler for the route.
     * @param array|null $middlewares Optional middlewares for the route.
     * 
     * @return Route The added route.
     */
    public function post(string $path, $handler, array $middlewares = []): Route
    {
        return $this->add('POST', $path, $handler, $middlewares);
    }

    /**
     * Adds a PUT route to the route collection.
     * 
     * @param string $path    The path for the route.
     * @param mixed  $handler The handler for the route.
     * @param array|null $middlewares Optional middlewares for the route.
     * 
     * @return Route The added route.
     */
    public function put(string $path, $handler, array $middlewares = []): Route
    {
        return $this->add('PUT', $path, $handler, $middlewares);
    }

    /**
     * Adds a DELETE route to the route collection.
     * 
     * @param string $path    The path for the route.
     * @param mixed  $handler The handler for the route.
     * @param array|null $middlewares Optional middlewares for the route.
     * 
     * @return Route The added route.
     */
    public function delete(string $path, $handler, array $middlewares = []): Route
    {
        return $this->add('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Adds a any route to the route collection.
     * 
     * @param string $path The path for route
     * @param mixed $handler The handler for the route.
     * @param array|null $middlewares Optional middlewares for the route.
     * 
     * @return Route The added route.
     */
    public function any(string $path, $handler, array $middlewares = []): Route
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $route = null;
        foreach ($methods as $method) {
            $route = $this->add($method, $path, $handler, $middlewares);
        }
        return $route;
    }

    /**
     * Make redirect route
     * 
     * @param string $path The path for route
     * @param string $destination The destination for the route
     * @param int $status The status code for the route
     * 
     * @return Route The added route.
     */
    public function redirect(string $path, string $destination, int $status = 302): Route
    {
        return $this->add('GET', $path, function () use ($destination, $status) {
            return response()->redirect($destination, $status);
        });
    }

    /**
     * Adds a resource route to the route collection.
     * 
     * @param string $name The name of the resource.
     * @param string $controller The controller for the resource.
     * @param array $options The options for the resource route.
     * 
     * @return void
     */
    public function resource(string $name, string $controller, array $options = []): void
    {
        $verbs = [
            'index' => ['get', '/'],
            'create' => ['get', '/create'],
            'store' => ['post', '/'],
            'show' => ['get', '/{id}'],
            'edit' => ['get', '/{id}/edit'],
            'update' => ['put', '/{id}'],
            'destroy' => ['delete', '/{id}']
        ];

        foreach ($verbs as $action => [$method, $uri]) {
            if (isset($options['only']) && !in_array($action, $options['only'])) {
                continue;
            }

            if (isset($options['except']) && in_array($action, $options['except'])) {
                continue;
            }

            $this->add($method, "/{$name}{$uri}", [$controller, $action]);
        }
    }

    /**
     * Adds an API resource route to the route collection.
     * 
     * @param string $name The name of the resource.
     * @param string $controller The controller for the resource.
     * @param array $options The options for the resource route.
     * 
     * @return void
     */
    public function apiResource(string $name, string $controller, array $options = []): void
    {
        $verbs = [
            'store' => ['post', '/'],
            'update' => ['put', '/{id}'],
            'destroy' => ['delete', '/{id}']
        ];

        // Аналогично функции resource, но без методов create и edit
        foreach ($verbs as $action => [$method, $uri]) {
            if (isset($options['only']) && !in_array($action, $options['only'])) {
                continue;
            }

            if (isset($options['except']) && in_array($action, $options['except'])) {
                continue;
            }

            $this->add($method, "/api/{$name}{$uri}", [$controller, $action]);
        }
    }

    /**
     * Add array of resource routes to the route collection.
     * 
     * @param array $resources The array of resources.
     * 
     * @return void
     */
    public function resources(array $resources): void
    {
        // Loop through the resources and call the resource function for each one
        foreach ($resources as $name => $controller) {
            $this->resource($name, $controller);
        }
    }
    
    /**
     * Add array of apiResource routes to the route collection.
     * 
     * @param array $resources The array of apiResources.
     * 
     * @return void
     */
    public function apiResources(array $resources): void
    {
        foreach ($resources as $name => $controller) {
            $this->apiResource($name, $controller);
        }
    }

    /**
     * Add a view route to the route collection.
     * 
     * @param string $path The path for the route.
     * @param string $view The view for the route.
     * @param array $options The options for the route.
     * 
     * @return Route The added route.
     */
    public function view(string $path, string $view, array $options = []): Route
    {
        return $this->add('GET', $path, function () use ($view, $options) {
            return render($view, $options);
        });
    }

    /**
     * Adds a middleware to the group.
     * 
     * @param mixed $middleware The middleware to add.
     * 
     * @return RouteGroup The current RouteGroup object.
     */
    public function middleware($middleware): self
    {
        if (is_string($middleware) && class_exists($middleware)) {
            $middleware = app($middleware);
        }

        if (is_callable($middleware)) {
            $this->middlewares[] = $middleware;
        } else {
            throw new \InvalidArgumentException('Invalid middleware provided. It should be a callable or a class name of an existing middleware class.');
        }

        return $this;
    }

    /**
     * Creates a new subgroup within the current group.
     * 
     * @param callable $callback   The callback function for the group.
     * @param string   $pathPrefix The path prefix for the subgroup.
     * 
     * @return RouteGroup The created subgroup.
     */
    public function group(callable $callback, string $pathPrefix = ''): RouteGroup
    {
        // Creates a new route group with the combined path prefix and executes the callback function.
        $group = new RouteGroup($this->routeDispatcher, $this->pathPrefix . $pathPrefix);
        $group->middlewares = $this->middlewares;
        $callback($group);

        return $group;
    }

    /**
     * Gets the middlewares for the group.
     * 
     * @return array The middlewares for the group.
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /** 
     * Sets the prefix for the group.
     * 
     * @param string $prefix The prefix for the group.
     * 
     * @return RouteGroup The current RouteGroup object.
     */
    public function prefix(string $prefix): RouteGroup
    {
        $this->pathPrefix .= $prefix;

        return $this;
    }
}
