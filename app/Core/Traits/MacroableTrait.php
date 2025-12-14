<?php

namespace Flute\Core\Traits;

use BadMethodCallException;
use Closure;
use ReflectionFunction;

trait MacroableTrait
{
    /**
     * The registered string macros.
     */
    protected static array $macros = [];

    /**
     * Register a custom macro.
     *
     * @param object|callable $macro
     */
    public static function macro(string $name, $macro): void
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Checks if macro is registered.
     */
    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @throws BadMethodCallException
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if (!static::hasMacro($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $reflection = new ReflectionFunction($macro);

            if ($reflection->isStatic()) {
                return $macro(...$parameters);
            }

            return $macro->call($this, ...$parameters);
        }

        return $macro(...$parameters);
    }

    /**
     * Dynamically handle static calls to the class.
     *
     * @throws BadMethodCallException
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters)
    {
        if (!static::hasMacro($method)) {
            throw new BadMethodCallException("Static method {$method} does not exist.");
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $reflection = new ReflectionFunction($macro);

            if (!$reflection->isStatic()) {
                $macro = $macro->bindTo(null, static::class);
            }
        }

        return $macro(...$parameters);
    }
}
