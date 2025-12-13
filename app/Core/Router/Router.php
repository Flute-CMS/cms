<?php

namespace Flute\Core\Router;

/*
 * @method static void screen(string $url, string|class-string $screen)
 */

use Clickfwd\Yoyo\Exceptions\HttpException;
use DI\Container;
use Exception;
use Flute\Core\Cache\SWRQueue;
use Flute\Core\Events\OnRouteFoundEvent;
use Flute\Core\Events\RoutingFinishedEvent;
use Flute\Core\Events\RoutingStartedEvent;
use Flute\Core\Exceptions\ForcedRedirectException;
use Flute\Core\Modules\Auth\Middlewares\IsAuthenticatedMiddleware;
use Flute\Core\Modules\Auth\Middlewares\IsGuestMiddleware;
use Flute\Core\Modules\Page\Middlewares\PagePermissionsMiddleware;
use Flute\Core\Router\Contracts\MiddlewareInterface;
use Flute\Core\Router\Contracts\RouteInterface;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Router\Middlewares\BanCheckMiddleware;
use Flute\Core\Router\Middlewares\CanMiddleware;
use Flute\Core\Router\Middlewares\CsrfMiddleware;
use Flute\Core\Router\Middlewares\HtmxMiddleware;
use Flute\Core\Router\Middlewares\MaintenanceMiddleware;
use Flute\Core\Router\Middlewares\RateLimiterMiddleware;
use Flute\Core\Router\Middlewares\TokenMiddleware;
use Flute\Core\Support\FluteRequest;
use Flute\Core\Template\Template;
use Flute\Core\Traits\MacroableTrait;
use Flute\Core\Traits\SingletonTrait;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Throwable;

class Router implements RouterInterface
{
    use MacroableTrait;
    use SingletonTrait;

    protected RouteCollection $routes;

    protected RouteCollection $compilableRoutes;

    protected RouteCollection $dynamicRoutes;

    protected RouteCollection $frontCompilableRoutes;

    protected RouteCollection $adminCompilableRoutes;

    protected RouteCollection $frontDynamicRoutes;

    protected RouteCollection $adminDynamicRoutes;

    protected Container $container;

    protected array $middlewareGroups = [
        'web' => ['csrf', 'throttle'],
        'api' => ['throttle', 'ban.check'],
        'default' => ['ban.check', 'throttle', 'maintenance'],
    ];

    protected array $groupStack = [];

    protected array $middlewareAliases = [
        'can' => CanMiddleware::class,
        'csrf' => CsrfMiddleware::class,
        'auth' => IsAuthenticatedMiddleware::class,
        'htmx' => HtmxMiddleware::class,
        'guest' => IsGuestMiddleware::class,
        'throttle' => RateLimiterMiddleware::class,
        'token' => TokenMiddleware::class,
        'ban.check' => BanCheckMiddleware::class,
        'maintenance' => MaintenanceMiddleware::class,
        'page.permissions' => PagePermissionsMiddleware::class,
    ];

    protected ?RouteInterface $currentRoute = null;

    protected array $registeredDynamicRoutes = [];

    public function __construct(Container $container)
    {
        $this->routes = new RouteCollection();
        $this->compilableRoutes = new RouteCollection();
        $this->dynamicRoutes = new RouteCollection();
        $this->frontCompilableRoutes = new RouteCollection();
        $this->adminCompilableRoutes = new RouteCollection();
        $this->frontDynamicRoutes = new RouteCollection();
        $this->adminDynamicRoutes = new RouteCollection();
        $this->container = $container;

        self::$instance = $this;

        events()->dispatch(new RoutingStartedEvent($this), RoutingStartedEvent::NAME);
    }

    public function aliasMiddleware(string $name, string $class): void
    {
        $this->middlewareAliases[$name] = $class;
    }

    /**
     * Create a group of routes with middleware.
     *
     * @param array|callable $attributes Attributes of the group or callback.
     * @param callable|null $callback Callback function.
     */
    public function group(array|callable $attributes, ?callable $callback = null): void
    {
        if (is_callable($attributes)) {
            $callback = $attributes;
            $attributes = [];
        }

        $this->updateGroupStack($attributes);

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * Set middleware for the current group of routes.
     *
     * @param array|string $middleware Middleware to apply.
     * @return $this
     */
    public function middleware(array|string $middleware): self
    {
        $middleware = array_merge($this->getGroupAttribute('middleware', []), (array) $middleware);

        return $this->updateGroupStack(['middleware' => $middleware]);
    }

    /**
     * Get the current route.
     */
    public function getCurrentRoute(): ?RouteInterface
    {
        return $this->currentRoute;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Exclude middleware from the current group of routes.
     *
     * @param array|string $middleware Middleware to exclude.
     * @return $this
     */
    public function withoutMiddleware(array|string $middleware): self
    {
        $excludedMiddleware = array_merge($this->getGroupAttribute('excluded_middleware', []), (array) $middleware);

        return $this->updateGroupStack(['excluded_middleware' => $excludedMiddleware]);
    }

    /**
     * Add a route with consideration of the current group.
     *
     * @param Closure|array|string|object $action
     */
    public function addRoute(array|string $methods, string $uri, array|string|object $action): RouteInterface
    {
        $methods = (array) $methods;
        $uri = '/' . trim($uri, '/');

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

        $originalName = $routeName;

        $route->setAfterModifyCallback(function (Route $modifiedRoute) use ($originalName) {
            $currentName = $modifiedRoute->getName();

            if ($currentName !== $originalName) {
                foreach ([$this->routes, $this->frontCompilableRoutes, $this->adminCompilableRoutes, $this->frontDynamicRoutes, $this->adminDynamicRoutes] as $collection) {
                    if ($collection->get($originalName)) {
                        $collection->remove($originalName);
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
            }
        });

        return $route;
    }

    /**
     * Register a GET route.
     */
    public function get(string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register a POST route.
     */
    public function post(string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a route for any HTTP method.
     */
    public function any(string $uri, array|string|object $action): RouteInterface
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];

        return $this->addRoute($methods, $uri, $action);
    }

    /**
     * Register a route that responds to multiple methods.
     */
    public function match(array $methods, string $uri, array|string|object $action): RouteInterface
    {
        return $this->addRoute($methods, $uri, $action);
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
        return $this->addRoute('GET', $path, static fn () => response()->view($view, $options));
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
        return $this->addRoute('GET', $path, static fn () => redirect($destination, $status));
    }

    /**
     * Dispatch the request through the router.
     */
    public function dispatch(FluteRequest $request): Response
    {
        $this->container->get(Template::class);

        $context = new RequestContext();
        $context->fromRequest($request);

        $isAdmin = is_admin_path();
        $compilable = new RouteCollection();
        $dynamicCollection = new RouteCollection();
        if ($isAdmin) {
            $compilable->addCollection($this->frontCompilableRoutes);
            $compilable->addCollection($this->adminCompilableRoutes);
            $dynamicCollection->addCollection($this->frontDynamicRoutes);
            $dynamicCollection->addCollection($this->adminDynamicRoutes);
        } else {
            $compilable->addCollection($this->frontCompilableRoutes);
            $dynamicCollection->addCollection($this->frontDynamicRoutes);
        }

        $urlMatcher = null;

        $cacheFile = path('storage/app/cache/routes_compiled' . ($isAdmin ? '_admin' : '_front') . '.php');
        $staleCacheDir = (string) (config('cache.stale_directory') ?? '');
        $staleCacheFile = '';
        if ($staleCacheDir !== '') {
            $staleCacheFile = rtrim(str_replace('\\', '/', $staleCacheDir), '/') . '/routes_compiled' . ($isAdmin ? '_admin' : '_front') . '.php';
        }

        if (!is_debug()) {
            $sourceFile = null;
            if (file_exists($cacheFile)) {
                $sourceFile = $cacheFile;
            } elseif ($staleCacheFile !== '' && file_exists($staleCacheFile)) {
                $sourceFile = $staleCacheFile;
            }

            if ($sourceFile) {
                $compiledRoutes = require $sourceFile;
                if ($compiledRoutes instanceof \Symfony\Component\Routing\Matcher\CompiledUrlMatcher) {
                    $urlMatcher = $compiledRoutes;
                    $urlMatcher->setContext($context);
                }
            }
        }

        if (!$urlMatcher) {
            $urlMatcher = new UrlMatcher($compilable, $context);
        }

        if (!is_debug() && !file_exists($cacheFile)) {
            SWRQueue::queue('router.routes_compiled.' . ($isAdmin ? 'admin' : 'front'), static function () use ($cacheFile, $staleCacheFile, $compilable): void {
                if (file_exists($cacheFile)) {
                    return;
                }

                // Fast path: restore previous compiled file from stale cache dir.
                if ($staleCacheFile !== '' && file_exists($staleCacheFile)) {
                    $content = @file_get_contents($staleCacheFile);
                    if (is_string($content) && $content !== '') {
                        @mkdir(dirname($cacheFile), 0o755, true);
                        $tmp = $cacheFile . '.' . uniqid('routes', true) . '.tmp';
                        if (@file_put_contents($tmp, $content, LOCK_EX) !== false) {
                            @rename($tmp, $cacheFile);

                            return;
                        }
                    }
                }

                $lockFile = $cacheFile . '.lock';
                $lockHandle = @fopen($lockFile, 'w+');
                if (!$lockHandle) {
                    return;
                }

                if (@flock($lockHandle, LOCK_EX | LOCK_NB)) {
                    try {
                        if (file_exists($cacheFile)) {
                            return;
                        }

                        $dumper = new CompiledUrlMatcherDumper($compilable);
                        $compiledSource = $dumper->dump(['class' => 'FluteCompiledRoutes']);
                        $compiledSource = (string) $compiledSource;
                        $php = (str_contains($compiledSource, '<?php') ? $compiledSource : "<?php\n" . $compiledSource) . "\nreturn new FluteCompiledRoutes([]);";

                        $tmp = $cacheFile . '.' . uniqid('routes', true) . '.tmp';
                        if (@file_put_contents($tmp, $php, LOCK_EX) !== false) {
                            @rename($tmp, $cacheFile);
                        }
                    } catch (Throwable $e) {
                        if (function_exists('logs')) {
                            logs()->warning($e);
                        }
                    } finally {
                        @flock($lockHandle, LOCK_UN);
                        @fclose($lockHandle);
                        @unlink($lockFile);
                    }
                } else {
                    @fclose($lockHandle);
                }
            });
        }

        $t0 = microtime(true);

        try {
            $parameters = $urlMatcher->match($request->getPathInfo());
            \Flute\Core\Router\RoutingTiming::add('Route Matching', microtime(true) - $t0);

            $this->container->get(FluteRequest::class)->attributes->add($parameters);

            $this->currentRoute = $this->resolveRoute($parameters);

            $onRouteEvent = events()->dispatch(new OnRouteFoundEvent($request, $this->currentRoute), OnRouteFoundEvent::NAME);

            if ($onRouteEvent->isPropagationStopped()) {
                throw new HttpException($onRouteEvent->getErrorCode(), $onRouteEvent->getErrorMessage());
            }

            $middleware = $this->gatherMiddleware();
            $pipeline = new MiddlewareRunner($middleware, $request, fn ($request) => $this->runRoute($request, $parameters));

            $tPipe = microtime(true);
            $response = $pipeline->run();
            \Flute\Core\Router\RoutingTiming::add('Middleware+Controller', microtime(true) - $tPipe);
        } catch (ResourceNotFoundException | MethodNotAllowedException $e) {
            $dynamicMatcher = new UrlMatcher($dynamicCollection, $context);

            try {
                $parameters = $dynamicMatcher->match($request->getPathInfo());
                \Flute\Core\Router\RoutingTiming::add('Route Matching', microtime(true) - $t0);

                $this->container->get(FluteRequest::class)->attributes->add($parameters);

                $this->currentRoute = $this->resolveRoute($parameters);

                $onRouteEvent = events()->dispatch(new OnRouteFoundEvent($request, $this->currentRoute), OnRouteFoundEvent::NAME);

                if ($onRouteEvent->isPropagationStopped()) {
                    throw new HttpException($onRouteEvent->getErrorCode(), $onRouteEvent->getErrorMessage());
                }

                $middleware = $this->gatherMiddleware();
                $pipeline = new MiddlewareRunner($middleware, $request, fn ($request) => $this->runRoute($request, $parameters));

                $tPipe = microtime(true);
                $response = $pipeline->run();
                \Flute\Core\Router\RoutingTiming::add('Middleware+Controller', microtime(true) - $tPipe);
            } catch (Exception $dynamicE) {
                if (is_debug()) {
                    throw $dynamicE;
                }

                $response = response()->error(404, __('def.page_not_found'));
                $response->setStatusCode(404);
            }
        } catch (ForcedRedirectException $exception) {
            $response = response()->redirect($exception->getUrl(), $exception->getStatusCode());
        } catch (HttpException $exception) {
            $response = response()->error($exception->getStatusCode(), $exception->getMessage());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $response = response()->error($exception->getStatusCode(), $exception->getMessage());
        } catch (Exception $exception) {
            if (is_debug()) {
                throw $exception;
            }

            $response = response()->error(500, __('def.internal_server_error'));
        }

        $event = new RoutingFinishedEvent($response);
        $event = events()->dispatch($event, RoutingFinishedEvent::NAME);

        if ($event->isPropagationStopped()) {
            throw new HttpException($event->getResponse()->getStatusCode(), $event->getResponse()->getContent());
        }

        return $response;
    }

    /**
     * Ensures compiled routes cache file exists (front/admin).
     * Intended for cron warmup and should not execute controllers.
     */
    public function warmupCompiledRoutes(bool $admin = false): void
    {
        if (is_debug()) {
            return;
        }

        $compilable = new RouteCollection();
        if ($admin) {
            $compilable->addCollection($this->frontCompilableRoutes);
            $compilable->addCollection($this->adminCompilableRoutes);
        } else {
            $compilable->addCollection($this->frontCompilableRoutes);
        }

        $cacheFile = path('storage/app/cache/routes_compiled' . ($admin ? '_admin' : '_front') . '.php');
        if (file_exists($cacheFile)) {
            return;
        }

        $lockFile = $cacheFile . '.lock';
        $lockHandle = @fopen($lockFile, 'w+');
        if (!$lockHandle) {
            return;
        }

        if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
            @fclose($lockHandle);

            return;
        }

        try {
            if (file_exists($cacheFile)) {
                return;
            }

            $dumper = new CompiledUrlMatcherDumper($compilable);
            $compiledSource = $dumper->dump(['class' => 'FluteCompiledRoutes']);
            $compiledSource = (string) $compiledSource;
            $php = (str_contains($compiledSource, '<?php') ? $compiledSource : "<?php\n" . $compiledSource) . "\nreturn new FluteCompiledRoutes([]);";

            @mkdir(dirname($cacheFile), 0o755, true);
            $tmp = $cacheFile . '.' . uniqid('routes', true) . '.tmp';
            if (@file_put_contents($tmp, $php, LOCK_EX) !== false) {
                @rename($tmp, $cacheFile);
            }
        } catch (Throwable $e) {
            logs()->warning($e);
        } finally {
            @flock($lockHandle, LOCK_UN);
            @fclose($lockHandle);
            @unlink($lockFile);
        }
    }

    /**
     * Register a middleware group.
     */
    public function middlewareGroup(string $name, array $middleware): void
    {
        $this->middlewareGroups[$name] = $middleware;
    }

    /**
     * Generate a URL for a named route.
     */
    public function url(string $name, array $parameters = []): string
    {
        $generator = new UrlGenerator($this->routes, (new RequestContext())->fromRequest(request()));

        if (!$this->routes->get($name)) {
            throw new Exception("Route '{$name}' not found.");
        }

        return $generator->generate($name, $parameters);
    }

    /**
     * Register routes from controllers using PHP 8 attributes
     *
     * @param array $directories Directories to scan for controllers
     * @param string $namespace The root namespace for the controllers
     * @return int Number of routes registered
     */
    public function registerAttributeRoutes(array $directories, string $namespace): int
    {
        $loader = new AttributeRouteLoader($this);

        return $loader->loadFromDirectories($directories, $namespace);
    }

    /**
     * Register routes from a specific controller class using PHP 8 attributes
     *
     * @param string $controllerClass Fully qualified class name of the controller
     * @return int Number of routes registered
     */
    public function registerAttributeRoutesFromClass(string $controllerClass): int
    {
        $loader = new AttributeRouteLoader($this);

        return $loader->loadFromClass($controllerClass);
    }

    /**
     * Check if a route already exists for the given URI and methods
     *
     * @param string $uri The URI to check
     * @param array|string $methods HTTP methods to check (empty means check all)
     * @return bool True if route exists, false otherwise
     */
    public function hasRoute(string $uri, array|string $methods = []): bool
    {
        $uri = '/' . trim($uri, '/');
        $methods = (array) $methods;

        if (isset($this->registeredDynamicRoutes[$uri])) {
            if (empty($methods)) {
                return true;
            }

            $existingMethods = $this->registeredDynamicRoutes[$uri];
            foreach ($methods as $method) {
                if (in_array(strtoupper($method), $existingMethods)) {
                    return true;
                }
            }
        }

        foreach ($this->routes->all() as $route) {
            $routePath = $route->getPath();

            if ($routePath === $uri) {
                if (empty($methods)) {
                    return true;
                }

                $routeMethods = $route->getMethods();
                if (empty($routeMethods)) {
                    return true;
                }

                foreach ($methods as $method) {
                    if (in_array(strtoupper($method), $routeMethods)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Gather middleware for the current route.
     */
    protected function gatherMiddleware(): array
    {
        static $cache = [];

        $routeKey = $this->currentRoute?->getName() ?: spl_object_id($this->currentRoute);
        if (isset($cache[$routeKey])) {
            return $cache[$routeKey];
        }
        $middleware = array_merge(
            $this->middlewareGroups['default'],
            $this->currentRoute->getMiddleware()
        );

        // Expand middleware groups and resolve aliases
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
                // Check for middleware with parameters, e.g., 'can:admin.pages'
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
                            throw new InvalidArgumentException("Middleware {$middlewareClass} must implement MiddlewareInterface.");
                        }

                        return $middleware->handle($request, $next, ...$parameters);
                    };
                } else {
                    // Assume it's a class name
                    $middlewareClass = $alias;
                    $resolvedMiddleware[] = function ($request, $next) use ($middlewareClass) {
                        $middleware = $this->container->get($middlewareClass);

                        if (!$middleware instanceof MiddlewareInterface) {
                            throw new InvalidArgumentException("Middleware {$middlewareClass} must implement MiddlewareInterface.");
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

    /**
     * Update the group stack with new attributes.
     *
     * @param array $attributes Attributes of the group.
     * @return $this
     */
    protected function updateGroupStack(array $attributes): self
    {
        $this->groupStack[] = $this->mergeGroupAttributes($attributes);

        return $this;
    }

    /**
     * Merge new group attributes with current ones.
     *
     * @param array $new New attributes.
     */
    protected function mergeGroupAttributes(array $new): array
    {
        $old = $this->groupStack ? end($this->groupStack) : [];

        $new['prefix'] = isset($old['prefix']) ? trim($old['prefix'], '/') . '/' . trim($new['prefix'] ?? '', '/') : ($new['prefix'] ?? '');

        if (isset($old['middleware'])) {
            $middleware = array_merge((array) $old['middleware'], (array) ($new['middleware'] ?? []));
            $new['middleware'] = $middleware;
        }

        if (isset($old['excluded_middleware'])) {
            $excludedMiddleware = array_merge((array) $old['excluded_middleware'], (array) ($new['excluded_middleware'] ?? []));
            $new['excluded_middleware'] = $excludedMiddleware;
        }

        return array_merge($old, $new);
    }

    /**
     * Get the group attribute from the group stack.
     *
     * @param string $key The key of the attribute.
     * @param mixed $default The default value.
     * @return mixed
     */
    protected function getGroupAttribute(string $key, $default = null)
    {
        return $this->groupStack ? ($this->groupStack[count($this->groupStack) - 1][$key] ?? $default) : $default;
    }

    /**
     * Resolve the current route from matched parameters.
     */
    protected function resolveRoute(array $parameters): RouteInterface
    {
        $action = $parameters['_controller'] ?? null;
        $routeName = $parameters['_route'] ?? null;
        $middleware = $parameters['_middleware'] ?? [];
        unset($parameters['_controller'], $parameters['_route'], $parameters['_middleware']);

        $symfonyRoute = $this->routes->get($routeName);

        $route = new Route([], '', $action);
        $route->setSymfonyRoute($symfonyRoute);
        $route->setParameters($parameters);
        $route->name($routeName);

        if (!empty($middleware)) {
            $route->middleware($middleware);
        }

        return $route;
    }

    /**
     * Run the matched route.
     */
    protected function runRoute(FluteRequest $request, array $parameters): Response
    {
        $start = microtime(true);
        $response = $this->currentRoute->run($request, $parameters, $this->container);
        \Flute\Core\Router\RoutingTiming::add('Controller', microtime(true) - $start);

        return $response;
    }

    /**
     * Track a dynamically registered route
     */
    protected function trackDynamicRoute(string $uri, array|string $methods): void
    {
        $uri = '/' . trim($uri, '/');
        $methods = array_map('strtoupper', (array) $methods);

        if (!isset($this->registeredDynamicRoutes[$uri])) {
            $this->registeredDynamicRoutes[$uri] = [];
        }

        $this->registeredDynamicRoutes[$uri] = array_unique(
            array_merge($this->registeredDynamicRoutes[$uri], $methods)
        );
    }
}
