<?php

namespace Flute\Core\Router;

use DI\Container;
use Flute\Core\Events\OnRouteFoundEvent;
use Flute\Core\Events\RoutingFinishedEvent;
use Flute\Core\Events\RoutingStartedEvent;
use Flute\Core\Exceptions\ForcedRedirectException;
use Flute\Core\Http\Middlewares\BanCheckMiddleware;
use Flute\Core\Http\Middlewares\MaintenanceMiddleware;
use Flute\Core\Http\Middlewares\RedirectsMiddleware;
use Flute\Core\Router\RouteGroup;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Template\Template;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteDispatcher
{
    private UrlMatcher $urlMatcher;
    private ContainerControllerResolver $controllerResolver;
    private EventDispatcher $eventDispatcher;
    private RouteCollection $routeCollection;
    private Container $container;
    private UrlGenerator $urlGenerator;
    public Template $templateService;

    protected array $defaultMiddlewares = [
        MaintenanceMiddleware::class,
        BanCheckMiddleware::class,
        RedirectsMiddleware::class
    ];


    /**
     * Constructor for RouteDispatcher.
     *
     * @param UrlMatcher $urlMatcher The URL matcher.
     * @param ContainerControllerResolver $controllerResolver The controller resolver.
     * @param EventDispatcher $eventDispatcher The event dispatcher.
     * @param RouteCollection $routeCollection The route collection.
     * @param Template $templateService The template engine.
     * @param UrlGenerator $urlGenerator The url generator.
     */
    public function __construct(
        UrlMatcher $urlMatcher,
        ContainerControllerResolver $controllerResolver,
        EventDispatcher $eventDispatcher,
        RouteCollection $routeCollection,
        Template $templateService,
        UrlGenerator $urlGenerator
    ) {
        $this->urlMatcher = $urlMatcher;
        $this->controllerResolver = $controllerResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->routeCollection = $routeCollection;
        $this->templateService = $templateService;
        $this->urlGenerator = $urlGenerator;

        $this->eventDispatcher->dispatch(new RoutingStartedEvent($this), RoutingStartedEvent::NAME);
    }

    /**
     * Handles the current request.
     * 
     * @param FluteRequest $request The current request.
     * 
     * @return Response The response.
     */
    public function handle(FluteRequest $request): Response
    {
        try {
            // Matches the current request to a route in the route collection.
            $request->attributes->add($this->urlMatcher->match($request->getPathInfo()));

            // Resolves the controller for the matched route.
            $controller = $this->controllerResolver->getController($request);

            // Resolves the arguments for the controller.
            $arguments = $this->controllerResolver->getArguments($request, $controller);

            $middlewaresRaw = $request->attributes->get('_middlewares') ?? [];

            if ($controller[0] instanceof \Flute\Core\Support\AbstractController) {
                $controllerMiddlewares = $controller[0]->middlewares();
                $middlewaresRaw = array_merge($middlewaresRaw, $controllerMiddlewares, $this->defaultMiddlewares);
            }

            // Retrieves the middlewares from the request's attributes.
            $middlewares = $this->handleMiddlewares($middlewaresRaw);

            // Creates a middleware runner and runs the middleware stack.
            $middlewareRunner = new MiddlewareRunner($middlewares, $request, function () use ($controller, $arguments) {
                return call_user_func_array($controller, $arguments);
            });

            $onRouteEvent = events()->dispatch(new OnRouteFoundEvent($request, $request->getPathInfo(), $controller), OnRouteFoundEvent::NAME);

            if ($onRouteEvent->isPropagationStopped()) {
                throw new HttpException($onRouteEvent->getErrorCode(), $onRouteEvent->getErrorMessage());
            }

            $response = $middlewareRunner->run();
        } catch (ResourceNotFoundException $exception) {
            $response = response()->error(404, __('def.page_not_found'));
        } catch (MethodNotAllowedException $exception) {
            $response = response()->error(405, __('def.method_not_allowed'));
        } catch (ForcedRedirectException $exception) {
            $response = response()->redirect($exception->getUrl(), $exception->getStatusCode());
        } catch (HttpException $exception) {
            return response()->error($exception->getStatusCode(), $exception->getMessage());
        } catch (\Exception $exception) {
            if (!is_debug())
                return response()->error(500, __('def.internal_server_error'));
            else
                throw $exception;
        }

        try {
            // Ensure middleware is run even on error
            $middlewares = $this->handleMiddlewares($this->defaultMiddlewares);
            $middlewareRunner = new MiddlewareRunner($middlewares, $request, function () use ($response) {
                return $response;
            });
            $response = $middlewareRunner->run();
        } catch (ResourceNotFoundException $exception) {
            $response = response()->error(404, __('def.page_not_found'));
        } catch (MethodNotAllowedException $exception) {
            $response = response()->error(405, __('def.method_not_allowed'));
        } catch (ForcedRedirectException $exception) {
            $response = response()->redirect($exception->getUrl(), $exception->getStatusCode());
        } catch (\Exception $exception) {
            if (!is_debug())
                return response()->error(500, __('def.internal_server_error'));
            else
                throw $exception;
        }

        // Dispatches a routing finished event.
        $event = new RoutingFinishedEvent($response);
        $this->eventDispatcher->dispatch($event, RoutingFinishedEvent::NAME);

        // Returns the response.
        return $event->getResponse();
    }

    /**
     * Handle all middlewares.
     * 
     * @param array $middlewares
     * 
     * @return array
     */
    public function handleMiddlewares(array $middlewares): array
    {
        $returnMiddlewares = [];

        foreach ($middlewares as $middleware) {
            if (is_string($middleware) && class_exists($middleware)) {
                $middleware = app($middleware);
            }

            if (is_callable($middleware)) {
                $returnMiddlewares[] = $middleware;
            } else {
                throw new \InvalidArgumentException('Invalid middleware provided. It should be a callable or a class name of an existing middleware class.');
            }
        }

        return $returnMiddlewares;
    }

    /**
     * Adds a route to the route collection.
     * 
     * @param string          $methods      The HTTP method(s) for the route, separated by '|'.
     * @param string          $path         The path for the route.
     * @param mixed           $handler      The handler for the route.
     * @param array|null      $middlewares  Optional middlewares for the route.
     * 
     * @return Route The added route.
     */
    public function add(string $methods, string $path, $handler, array $middlewares = null): Route
    {
        $route = new Route($path, ['_controller' => $handler, '_middlewares' => $middlewares]);
        foreach (explode('|', $methods) as $method) {
            $this->routeCollection->add(trim($method) . '_' . $path, $route->setMethods([trim($method)]));
        }

        return $route;
    }

    /**
     * Creates a new route group.
     * 
     * @param callable $callback    The callback function for the group.
     * @param string   $pathPrefix  The path prefix for the group.
     * 
     * @return RouteGroup The created route group.
     */
    public function group(callable $callback, string $pathPrefix = ''): RouteGroup
    {
        // Creates a new route group and executes the callback function.
        $group = new RouteGroup($this, $pathPrefix);
        $callback($group);

        return $group;
    }

    /**
     * Adds a GET route to the route collection.
     * 
     * @param string $path    The path for the route.
     * @param mixed  $handler The handler for the route.
     * @param array $middlewares Optional middlewares for the route.
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
     * @param array $middlewares Optional middlewares for the route.
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
     * @param array $middlewares Optional middlewares for the route.
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
     * @param array $middlewares Optional middlewares for the route.
     * 
     * @return Route The added route.
     */
    public function delete(string $path, $handler, array $middlewares = []): Route
    {
        return $this->add('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Adds an any route to the route collection.
     * 
     * @param string $path The path for route
     * @param mixed $handler The handler for the route.
     * @param array $middlewares Optional middlewares for the route.
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
            return $this->templateService->render($view, $options);
        });
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route        The name of the route
     * @param array  $parameters   An array of parameters
     * @param int    $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string The generated URL
     */
    public function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }

    /**
     * Get all routes from the route collection.
     * 
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routeCollection->all();
    }
}