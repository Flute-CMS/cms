<?php

namespace Flute\Core\Router\Annotations;

use Attribute;

#[Attribute(
    Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE,
    flags: Attribute::TARGET_CLASS | Attribute::TARGET_METHOD
)]
class Middleware
{
    /**
     * @var array The middleware to apply
     */
    private array $middleware;

    /**
     * @var bool Whether this middleware is inherited
     */
    private bool $inherited;

    /**
     * Constructor for the Middleware attribute
     *
     * @param array|string $middleware Middleware to apply to the route or controller
     * @param bool $inherited Whether this middleware is inherited
     */
    public function __construct(array|string $middleware, bool $inherited = false)
    {
        $this->middleware = is_string($middleware) ? [$middleware] : $middleware;
        $this->inherited = $inherited;
    }

    /**
     * Get the middleware array
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Check if middleware is inherited
     *
     * @return bool
     */
    public function isInherited(): bool
    {
        return $this->inherited;
    }

    /**
     * Create a new middleware instance with inherited properties
     *
     * @param Middleware $parent Parent middleware
     * @param Middleware $child Child middleware
     * @return Middleware
     */
    public static function inherit(Middleware $parent, Middleware $child): Middleware
    {
        return new self(
            array_merge($parent->getMiddleware(), $child->getMiddleware()),
            true
        );
    }
}
