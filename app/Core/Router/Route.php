<?php

namespace Flute\Core\Router;

use Closure;
use DI\Container;
use Exception;
use Flute\Core\Router\Contracts\RouteInterface;
use Flute\Core\Support\FluteRequest;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class Route implements RouteInterface
{
    protected array $methods;
    protected string $uri;
    protected mixed $action;
    protected array $parameters = [];
    protected array $middleware = [];
    protected array $groupAttributes = [];
    protected ?string $name = null;
    protected array $requirements = [];
    protected array $defaults = [];
    protected \Symfony\Component\Routing\Route $symfonyRoute;
    protected ?\Closure $afterModifyCallback = null;
    protected bool $isAdmin = false;

    public function __construct(array $methods, string $uri, mixed $action)
    {
        $normalizedMethods = array_map('strtoupper', $methods);
        if (in_array('GET', $normalizedMethods, true) && !in_array('HEAD', $normalizedMethods, true)) {
            $normalizedMethods[] = 'HEAD';
        }

        $this->methods = $normalizedMethods;
        $this->uri = $uri;
        $this->action = $action;

        $this->symfonyRoute = new \Symfony\Component\Routing\Route(
            $this->uri,
            ['_controller' => $this->action],
            $this->requirements,
            [],
            '',
            [],
            $this->methods
        );
    }

    /**
     * Execute the route action.
     */
    public function run(FluteRequest $request, array $parameters, Container $container): Response
    {
        $action = $this->action;

        if ($action instanceof Closure) {
            return $container->call($action, $parameters);
        }

        if (is_string($action)) {
            $action = explode('@', $action);
        }

        if (is_array($action)) {
            [$controller, $method] = $action;

            $controllerInstance = $container->get($controller);

            if (method_exists($controllerInstance, $method)) {
                $response = $container->call([$controllerInstance, $method], $parameters);

                // Support laravel blade view.
                if ($response instanceof View) {
                    try {
                        return response()->make($response->render());
                    } catch (\Throwable $e) {
                        $root = $e;
                        while ($root->getPrevious() !== null) {
                            $root = $root->getPrevious();
                        }

                        if ($root !== $e) {
                            throw $root;
                        }

                        throw $e;
                    }
                }

                // Support rendered fragments or other...
                if (is_string($response)) {
                    return response()->make($response);
                }

                return $response;
            }

            throw new Exception("Method {$method} not found in controller ".get_class($controllerInstance));
        }

        if (is_object($action) && method_exists($action, '__invoke')) {
            return $container->call([$action, '__invoke'], $parameters);
        }

        throw new Exception('Invalid route action');
    }

    /**
     * Set a callback to be executed after the route is modified
     *
     * @param \Closure $callback Callback function with the route as parameter
     * @return self
     */
    public function setAfterModifyCallback(\Closure $callback): self
    {
        $this->afterModifyCallback = $callback;

        return $this;
    }

    /**
     * Execute the after modify callback if set
     *
     * @return void
     */
    protected function executeAfterModifyCallback(): void
    {
        if ($this->afterModifyCallback !== null) {
            call_user_func($this->afterModifyCallback, $this);
        }
    }

    /**
     * Add middleware to the route.
     */
    public function middleware(array|string|null $middleware): self
    {
        if (is_null($middleware)) {
            return $this;
        }

        $this->middleware = array_merge($this->middleware, (array) $middleware);

        // Обновляем defaults в Symfony Route
        $defaults = $this->symfonyRoute->getDefaults();
        $defaults['_middleware'] = $this->middleware;
        $this->symfonyRoute->setDefaults($defaults);

        $this->executeAfterModifyCallback();

        return $this;
    }

    /**
     * Assign a name to the route.
     */
    public function name(string $name): self
    {
        $this->name = $name;

        $defaults = $this->symfonyRoute->getDefaults();
        $defaults['_route'] = $name;

        $this->symfonyRoute->setDefaults($defaults);

        $this->executeAfterModifyCallback();

        return $this;
    }

    /**
     * Add constraints to route parameters.
     */
    public function where(string|array $parameter, string|null $pattern = null): self
    {
        if (is_array($parameter)) {
            $this->requirements = array_merge($this->requirements, $parameter);
        } else {
            $this->requirements[$parameter] = $pattern;
        }

        $this->symfonyRoute->setRequirements($this->requirements);

        $this->executeAfterModifyCallback();

        return $this;
    }

    /**
     * Get the route name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the route URI.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set route parameters.
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * Get middleware assigned to the route.
     */
    public function getMiddleware(): array
    {
        $middleware = $this->middleware;

        if (isset($this->groupAttributes['middleware'])) {
            $groupMiddleware = (array) $this->groupAttributes['middleware'];
            $middleware = array_merge($groupMiddleware, $middleware);
        }

        return array_unique($middleware);
    }

    /**
     * Get excluded middleware for the route.
     *
     * @return array
     */
    public function getExcludedMiddleware(): array
    {
        return $this->groupAttributes['excluded_middleware'] ?? [];
    }

    /**
     * Get parameter constraints.
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Get the route action.
     */
    public function getAction(): mixed
    {
        return $this->action;
    }

    /**
     * Set group attributes for the route.
     */
    public function setGroupAttributes(array $attributes): void
    {
        $this->groupAttributes = $attributes;

        if (isset($attributes['prefix'])) {
            $prefix = trim($attributes['prefix'], '/');
            if (!empty($prefix)) {
                $this->uri = '/'.trim($prefix, '/').'/'.trim(ltrim($this->uri, '/'), '/');
                $this->symfonyRoute->setPath($this->uri);
            }
        }

        if (isset($attributes['middleware'])) {
            $this->middleware($attributes['middleware']);
        }

        $this->executeAfterModifyCallback();
    }

    /**
     * Set default values for route parameters.
     */
    public function defaults(string $key, mixed $value): self
    {
        $this->defaults[$key] = $value;

        $currentDefaults = $this->symfonyRoute->getDefaults();
        $currentDefaults[$key] = $value;
        $this->symfonyRoute->setDefaults($currentDefaults);

        $this->executeAfterModifyCallback();

        return $this;
    }

    /**
     * Get default parameter values.
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function getSymfonyRoute(): \Symfony\Component\Routing\Route
    {
        return $this->symfonyRoute;
    }

    public function setSymfonyRoute(\Symfony\Component\Routing\Route $symfonyRoute): void
    {
        $this->symfonyRoute = $symfonyRoute;
    }

    /**
     * Exclude middleware from the route.
     *
     * @param array|string $middleware Middleware to exclude
     * @return self
     */
    public function withoutMiddleware(array|string $middleware): self
    {
        $excludedMiddleware = $this->getExcludedMiddleware();
        $newExcluded = is_array($middleware) ? $middleware : [$middleware];

        $this->groupAttributes['excluded_middleware'] = array_merge($excludedMiddleware, $newExcluded);

        $this->executeAfterModifyCallback();

        return $this;
    }

    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }
}
