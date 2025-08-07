<?php

namespace Flute\Core\Router\Annotations;

use Attribute;
use Closure;

#[Attribute(
    Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE,
    flags: Attribute::TARGET_CLASS | Attribute::TARGET_METHOD
)]
class Route
{
    /**
     * @var string The route URI pattern
     */
    private string $uri;

    /**
     * @var array The HTTP methods this route responds to
     */
    private array $methods;

    /**
     * @var string|null The name of the route
     */
    private ?string $name;

    /**
     * @var array The middleware that should be applied to the route
     */
    private array $middleware;

    /**
     * @var array The route parameters constraints (regex patterns)
     */
    private array $where;

    /**
     * @var array Default values for route parameters
     */
    private array $defaults;

    /**
     * @var bool Whether this route is inherited from parent class
     */
    private bool $inherited;

    /**
     * @var Closure|null Callback to be executed after route is modified
     */
    private ?Closure $afterModifyCallback = null;

    /**
     * Constructor for the Route attribute
     *
     * @param string $uri The URI pattern for this route
     * @param array|string $methods HTTP methods this route responds to (GET, POST, etc.)
     * @param string|null $name Optional route name for URL generation
     * @param array|string|null $middleware Optional middleware to be applied
     * @param array $where Optional parameter constraints
     * @param array $defaults Optional default values for route parameters
     * @param bool $inherited Whether this route is inherited from parent class
     */
    public function __construct(
        string $uri,
        array|string $methods = ['GET'],
        ?string $name = null,
        array|string|null $middleware = null,
        array $where = [],
        array $defaults = [],
        bool $inherited = false
    ) {
        $this->uri = $uri;
        $this->methods = is_string($methods) ? [$methods] : $methods;
        $this->name = $name;
        $this->middleware = is_string($middleware) ? [$middleware] : ($middleware ?? []);
        $this->where = $where;
        $this->defaults = $defaults;
        $this->inherited = $inherited;
    }

    /**
     * Set a callback to be executed after the route is modified
     *
     * @param Closure $callback Callback function with the route as parameter
     * @return self
     */
    public function setAfterModifyCallback(Closure $callback): self
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
     * Set the route name
     *
     * @param string $name The name for the route
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        $this->executeAfterModifyCallback();

        return $this;
    }

    /**
     * Add middleware to the route
     *
     * @param array|string $middleware The middleware to add
     * @return self
     */
    public function addMiddleware(array|string $middleware): self
    {
        $newMiddleware = is_string($middleware) ? [$middleware] : $middleware;
        $this->middleware = array_merge($this->middleware, $newMiddleware);
        $this->executeAfterModifyCallback();

        return $this;
    }

    /**
     * Add parameter constraints to the route
     *
     * @param string|array $parameter Parameter name or array of parameter-pattern pairs
     * @param string|null $pattern Regex pattern for the parameter
     * @return self
     */
    public function where(string|array $parameter, ?string $pattern = null): self
    {
        if (is_array($parameter)) {
            $this->where = array_merge($this->where, $parameter);
        } else {
            $this->where[$parameter] = $pattern;
        }
        $this->executeAfterModifyCallback();

        return $this;
    }

    /**
     * Set default values for route parameters
     *
     * @param string $key Parameter name
     * @param mixed $value Default value
     * @return self
     */
    public function defaults(string $key, mixed $value): self
    {
        $this->defaults[$key] = $value;
        $this->executeAfterModifyCallback();

        return $this;
    }

    /**
     * Get the route URI
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the HTTP methods
     *
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the route name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the middleware list
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get the parameter constraints
     *
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Get the default values
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Check if route is inherited
     *
     * @return bool
     */
    public function isInherited(): bool
    {
        return $this->inherited;
    }

    /**
     * Create a new route instance with inherited properties
     *
     * @param Route $parent Parent route
     * @param Route $child Child route
     * @return Route
     */
    public static function inherit(Route $parent, Route $child): Route
    {
        // Combine URIs
        $uri = rtrim($parent->getUri(), '/') . '/' . ltrim($child->getUri(), '/');
        $uri = '/' . trim($uri, '/');

        // Combine names
        $name = $parent->getName() . ($child->getName() ?? '');

        // Merge middleware
        $middleware = array_merge($parent->getMiddleware(), $child->getMiddleware());

        $route = new self(
            $uri,
            $child->getMethods(),
            $name,
            $middleware,
            array_merge($parent->getWhere(), $child->getWhere()),
            array_merge($parent->getDefaults(), $child->getDefaults()),
            true
        );

        if ($child->afterModifyCallback !== null) {
            $route->setAfterModifyCallback($child->afterModifyCallback);
        }

        return $route;
    }
}
