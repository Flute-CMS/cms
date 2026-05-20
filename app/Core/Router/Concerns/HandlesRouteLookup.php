<?php

namespace Flute\Core\Router\Concerns;

use Exception;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;

trait HandlesRouteLookup
{
    public function url(string $name, array $parameters = []): string
    {
        $generator = new UrlGenerator($this->routes, ( new RequestContext() )->fromRequest(request()));

        if (!$this->routes->get($name)) {
            throw new Exception("Route '{$name}' not found.");
        }

        return $generator->generate($name, $parameters);
    }

    public function hasRoute(string $uri, array|string $methods = []): bool
    {
        $uri = '/' . trim($uri, '/');
        $methods = array_map('strtoupper', (array) $methods);

        if (isset($this->registeredDynamicRoutes[$uri])) {
            if (empty($methods)) {
                return true;
            }

            foreach ($methods as $method) {
                if (in_array($method, $this->registeredDynamicRoutes[$uri], true)) {
                    return true;
                }
            }
        }

        if ($this->routePathIndex === null) {
            $this->buildRoutePathIndex();
        }

        if (!isset($this->routePathIndex[$uri])) {
            return false;
        }

        if (empty($methods)) {
            return true;
        }

        foreach ($this->routePathIndex[$uri] as $routeMethods) {
            if (empty($routeMethods)) {
                return true;
            }

            foreach ($methods as $method) {
                if (in_array($method, $routeMethods, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
