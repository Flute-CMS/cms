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
            // Avoid binding closures that are already static; binding a static
            // closure to an object will raise a warning in newer PHP versions.
            // If closure is static (no bound $this), keep as-is; otherwise
            // bind to the class so it can access protected/private members.
            $reflection = new ReflectionFunction($macro);
            if ($reflection->getClosureScopeClass() === null && $reflection->isClosure()) {
                // no scope class -> closure is static/unbound, call directly
                return $macro(...$parameters);
            }

            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }
}
