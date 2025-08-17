<?php

namespace Flute\Core\Router\ActiveRouter;

use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Support\FluteStr;

class ActiveRouter
{
    /**
     * Current HTTP request.
     *
     * @var \Flute\Core\Support\FluteRequest
     */
    protected $request;

    /**
     * Router instance.
     *
     * @var \Flute\Core\Router\Contracts\RouterInterface
     */
    protected $router;

    protected string $activeClass = 'active';

    /**
     * Constructor of the class.
     *
     * @param FluteRequest         $request
     * @param \Flute\Core\Router\Contracts\RouterInterface $router
     */
    public function __construct(FluteRequest $request, RouterInterface $router)
    {
        $this->request = $request;
        $this->router = $router;
    }

    /**
     * Determines if the current route is active.
     *
     * @param mixed $routes
     * @return bool
     */
    public function isActive($routes)
    {
        $routes = is_array($routes) ? $routes : func_get_args();

        [$routes, $ignoredRoutes] = $this->parseIgnoredRoutes($routes);

        if ($this->isPath($routes) || $this->isFullPath($routes) || $this->isRoute($routes)) {
            if (count($ignoredRoutes) && ($this->isPath($ignoredRoutes) || $this->isFullPath($ignoredRoutes) || $this->isRoute($ignoredRoutes))) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    /**
     * Gets the active class if the route is active.
     *
     * @param mixed  $routes
     * @param string $class
     * @param string|null $fallbackClass
     * @return string|null
     */
    public function active($routes, $class = null, $fallbackClass = null)
    {
        $routes = (array) $routes;

        if ($this->isActive($routes)) {
            return $this->getActiveClass($class);
        }

        if ($fallbackClass) {
            return $fallbackClass;
        }

        return null;
    }

    /**
     * Determines if the current path matches the provided.
     *
     * @param mixed $routes
     * @return bool
     */
    public function isPath($routes)
    {
        $routes = is_array($routes) ? $routes : func_get_args();

        return in_array($this->request->getPathInfo(), $routes);
    }

    /**
     * Determines if the full URL matches the provided.
     *
     * @param mixed $routes
     * @return bool
     */
    public function isFullPath($routes)
    {
        $routes = is_array($routes) ? $routes : func_get_args();

        return in_array($this->request->getUri(), $routes);
    }

    /**
     * Determines if the current route matches the provided.
     *
     * @param mixed $routes
     * @return bool
     */
    public function isRoute($routes)
    {
        $currentRoute = $this->router->getCurrentRoute();

        if (!$currentRoute) {
            return false;
        }

        $currentRouteName = $currentRoute->getName();

        if (!$currentRouteName) {
            return false;
        }

        $routes = is_array($routes) ? $routes : func_get_args();

        return in_array($currentRouteName, $routes);
    }

    /**
     * Returns the active class or value from the configuration.
     *
     * @param string|null $class
     * @return string
     */
    protected function getActiveClass($class = null)
    {
        return $class ?: $this->activeClass;
    }

    /**
     * Separates ignored routes from the provided.
     *
     * @param mixed $routes
     * @return array
     */
    private function parseIgnoredRoutes($routes)
    {
        $ignoredRoutes = [];

        $routes = is_array($routes) ? $routes : func_get_args();

        foreach ($routes as $index => $route) {
            if (FluteStr::startsWith($route, 'not:')) {
                $ignoredRoute = substr($route, 4);

                unset($routes[$index]);

                $ignoredRoutes[] = $ignoredRoute;
            }
        }

        return [$routes, $ignoredRoutes];
    }
}
