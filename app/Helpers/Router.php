<?php

use Flute\Core\Router\ActiveRouter\ActiveRouter;
use Flute\Core\Router\Router;
use Illuminate\Support\Str;

if (! function_exists('route')) {
    /**
     * Returns the URL for the given route name with optional parameters.
     * 
     * @param string $name The route name.
     * @param array $parameters Parameters for the route.
     * 
     * @return string The generated URL.
     */
    function route(string $name, array $parameters = []) : string
    {
        return router()->url($name, $parameters);
    }
}

if (! function_exists("router")) {
    /**
     * Returns the router instance
     * 
     * @return Router
     */
    function router() : Router
    {
        return app(Router::class);
    }
}

if (! function_exists('active_router')) {
    /**
     * Получить экземпляр ActiveRouter.
     *
     * @return ActiveRouter
     */
    function active_router() : ActiveRouter
    {
        return app(ActiveRouter::class);
    }
}

if (! function_exists('controller_name')) {
    /**
     * Получить имя контроллера текущего маршрута.
     *
     * @param string|null $separator
     * @param bool $includeNamespace
     * @param string $trimNamespace
     * @return string|null
     */
    function controller_name($separator = null, $includeNamespace = true, $trimNamespace = 'App\Http\Controllers\\')
    {
        $currentRoute = active_router()->getRouter()->getCurrentRoute();

        if ($currentRoute) {
            $action = $currentRoute->getAction(); // Предполагается, что RouteInterface имеет метод getAction()

            $separator = is_null($separator) ? ' ' : $separator;

            $controller = head(Str::parseCallback($action, null));

            if (substr($controller, 0, strlen($trimNamespace)) === $trimNamespace) {
                $controller = substr($controller, strlen($trimNamespace));
            }

            if (substr($controller, -strlen('Controller')) === 'Controller') {
                $controller = substr($controller, 0, -strlen('Controller'));
            }

            $controller = str_replace('_', $separator, Str::snake($controller));

            $controller = $includeNamespace ? str_replace('\\', '', $controller) : substr(strrchr($controller, '\\'), 1);

            return trim($controller);
        }

        return null;
    }
}

if (! function_exists('action_name')) {
    /**
     * Получить имя действия текущего маршрута.
     *
     * @param bool $removeHttpMethod
     * @return string|null
     */
    function action_name($removeHttpMethod = true)
    {
        $currentRoute = active_router()->getRouter()->getCurrentRoute();

        if ($currentRoute) {
            $action = $currentRoute->getAction();

            $action = last(Str::parseCallback($action, null));

            if ($removeHttpMethod) {
                $action = preg_replace('/^(get|post|patch|put|delete)_/', '', $action);
            }

            return Str::snake($action, '-');
        }

        return null;
    }
}

if (! function_exists('active')) {
    /**
     * Получить класс активности, если маршрут активен.
     *
     * @param mixed $routes
     * @param string|null $class
     * @param string|null $fallbackClass
     * @return string|null
     */
    function active($routes = null, $class = null, $fallbackClass = null) : ActiveRouter|string|null
    {
        if (is_null($routes)) {
            return active_router();
        }

        $routes = is_array($routes) ? $routes : [$routes];

        return active_router()->active($routes, $class, $fallbackClass);
    }
}

if (! function_exists('is_active')) {
    /**
     * Определить, активен ли один из предоставленных маршрутов.
     *
     * @param mixed $routes
     * @return bool
     */
    function is_active($routes)
    {
        $routes = is_array($routes) ? $routes : func_get_args();

        return active_router()->isActive($routes);
    }
}