<?php

namespace Flute\Core\Router\Concerns;

use Flute\Core\Router\Contracts\RouteInterface;
use Flute\Core\Router\Route;

trait HandlesRouteRegistration
{
    /**
     * Add a route with consideration of the current group.
     */
    public function addRoute(array|string $methods, string $uri, array|string|object $action): RouteInterface
    {
        $methods = (array) $methods;
        $uri = '/' . trim($uri, '/');

        $this->routePathIndex = null;
        $this->trackDynamicRoute($uri, $methods);

        $isAdminRoute = false;
        if (!empty($this->groupStack)) {
            $group = end($this->groupStack);
            if (isset($group['prefix']) && str_starts_with(trim($group['prefix'], '/'), '/admin')) {
                $isAdminRoute = true;
            }
        }

        $routeName = 'route_' . md5($uri . implode(',', $methods));

        $route = new Route($methods, $uri, $action);

        if (!empty($this->groupStack)) {
            $route->setGroupAttributes(end($this->groupStack));
        }

        $route->name($routeName);
        $route->setIsAdmin($isAdminRoute);

        $symfonyRoute = $route->getSymfonyRoute();
        $isCompilable = is_string($action) || is_array($action);
        if ($isCompilable) {
            $collection = $isAdminRoute ? $this->adminCompilableRoutes : $this->frontCompilableRoutes;
            $collection->add($routeName, $symfonyRoute);
            $this->routes->add($routeName, $symfonyRoute);
        } else {
            $collection = $isAdminRoute ? $this->adminDynamicRoutes : $this->frontDynamicRoutes;
            $collection->add($routeName, $symfonyRoute);
            $clone = clone $symfonyRoute;
            $defaults = $clone->getDefaults();
            $defaults['_controller'] = 'dynamic';
            $clone->setDefaults($defaults);
            $this->routes->add($routeName, $clone);
        }

        $registeredName = $routeName;

        $route->setAfterModifyCallback(function (Route $modifiedRoute) use (&$registeredName) {
            $currentName = $modifiedRoute->getName();

            if ($currentName && $currentName !== $registeredName) {
                foreach ([
                    $this->routes,
                    $this->frontCompilableRoutes,
                    $this->adminCompilableRoutes,
                    $this->frontDynamicRoutes,
                    $this->adminDynamicRoutes,
                ] as $collection) {
                    if ($collection->get($registeredName)) {
                        $collection->remove($registeredName);
                    }
                }

                $symfonyRoute = $modifiedRoute->getSymfonyRoute();
                $action = $modifiedRoute->getAction();
                $isCompilable = is_string($action) || is_array($action);
                $isAdminRoute = $modifiedRoute->getIsAdmin();
                if ($isCompilable) {
                    $collection = $isAdminRoute ? $this->adminCompilableRoutes : $this->frontCompilableRoutes;
                    $collection->add($currentName, $symfonyRoute);
                    $this->routes->add($currentName, $symfonyRoute);
                } else {
                    $collection = $isAdminRoute ? $this->adminDynamicRoutes : $this->frontDynamicRoutes;
                    $collection->add($currentName, $symfonyRoute);
                    $clone = clone $symfonyRoute;
                    $defaults = $clone->getDefaults();
                    $defaults['_controller'] = 'dynamic';
                    $clone->setDefaults($defaults);
                    $this->routes->add($currentName, $clone);
                }

                $registeredName = $currentName;
            }
        });

        return $route;
    }

    public function get(string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function delete(string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function any(string $uri, array|string|object $action): RouteInterface
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];

        return $this->addRoute($methods, $uri, $action);
    }

    public function match(array $methods, string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute($methods, $uri, $action);
    }

    public function view(string $path, string $view, array $options = []): Route
    {
        return $this->addRoute('GET', $path, static fn() => response()->view($view, $options));
    }

    public function redirect(string $path, string $destination, int $status = 302): Route
    {
        return $this->addRoute('GET', $path, static fn() => redirect($destination, $status));
    }

    protected function trackDynamicRoute(string $uri, array|string $methods): void
    {
        $uri = '/' . trim($uri, '/');
        $methods = array_map('strtoupper', (array) $methods);

        if (!isset($this->registeredDynamicRoutes[$uri])) {
            $this->registeredDynamicRoutes[$uri] = [];
        }

        $this->registeredDynamicRoutes[$uri] = array_unique(array_merge(
            $this->registeredDynamicRoutes[$uri],
            $methods,
        ));
    }
}
