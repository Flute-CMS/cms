<?php

namespace Flute\Core\Router\Concerns;

use Flute\Core\Router\Contracts\MiddlewareInterface;
use InvalidArgumentException;

trait HandlesMiddlewareResolution
{
    public function aliasMiddleware(string $name, string $class): void
    {
        $this->middlewareAliases[$name] = $class;
    }

    public function middleware(array|string $middleware): self
    {
        $middleware = array_merge($this->getGroupAttribute('middleware', []), (array) $middleware);

        return $this->updateGroupStack(['middleware' => $middleware]);
    }

    public function withoutMiddleware(array|string $middleware): self
    {
        $excludedMiddleware = array_merge($this->getGroupAttribute('excluded_middleware', []), (array) $middleware);

        return $this->updateGroupStack(['excluded_middleware' => $excludedMiddleware]);
    }

    public function middlewareGroup(string $name, array $middleware): void
    {
        $this->middlewareGroups[$name] = $middleware;
    }

    protected function gatherMiddleware(): array
    {
        static $cache = [];

        $routeKey = $this->currentRoute?->getName() ?: spl_object_id($this->currentRoute);
        if (isset($cache[$routeKey])) {
            return $cache[$routeKey];
        }
        $middleware = array_merge($this->middlewareGroups['default'], $this->currentRoute->getMiddleware());

        $excluded = $this->currentRoute->getExcludedMiddleware();
        if (!empty($excluded)) {
            $middleware = array_filter($middleware, static function (string $m) use ($excluded): bool {
                $alias = str_contains($m, ':') ? explode(':', $m, 2)[0] : $m;

                return !in_array($alias, $excluded, true) && !in_array($m, $excluded, true);
            });
        }

        $expandedMiddleware = [];
        foreach ($middleware as $m) {
            if (isset($this->middlewareGroups[$m])) {
                $expandedMiddleware = array_merge($expandedMiddleware, $this->middlewareGroups[$m]);
            } else {
                $expandedMiddleware[] = $m;
            }
        }

        $resolvedMiddleware = [];
        foreach ($expandedMiddleware as $m) {
            if (is_string($m)) {
                if (strpos($m, ':') !== false) {
                    [$alias, $parameters] = explode(':', $m, 2);
                    $parameters = explode(',', $parameters);
                } else {
                    $alias = $m;
                    $parameters = [];
                }

                if (isset($this->middlewareAliases[$alias])) {
                    $middlewareClass = $this->middlewareAliases[$alias];
                    $resolvedMiddleware[] = function ($request, $next) use ($middlewareClass, $parameters) {
                        $middleware = $this->container->get($middlewareClass);

                        if (!$middleware instanceof MiddlewareInterface) {
                            throw new InvalidArgumentException(
                                "Middleware {$middlewareClass} must implement MiddlewareInterface.",
                            );
                        }

                        return $middleware->handle($request, $next, ...$parameters);
                    };
                } else {
                    $middlewareClass = $alias;
                    $resolvedMiddleware[] = function ($request, $next) use ($middlewareClass) {
                        $middleware = $this->container->get($middlewareClass);

                        if (!$middleware instanceof MiddlewareInterface) {
                            throw new InvalidArgumentException(
                                "Middleware {$middlewareClass} must implement MiddlewareInterface.",
                            );
                        }

                        return $middleware->handle($request, $next);
                    };
                }
            } elseif (is_callable($m)) {
                $resolvedMiddleware[] = $m;
            } else {
                throw new InvalidArgumentException('Invalid middleware specified.');
            }
        }

        return $cache[$routeKey] = $resolvedMiddleware;
    }
}
