<?php

namespace Flute\Core\Router;

class RoutingTiming
{
    /** @var array<string,float> */
    private static array $segments = [];

    public static function add(string $segment, float $seconds): void
    {
        if (!isset(self::$segments[$segment])) {
            self::$segments[$segment] = 0.0;
        }
        self::$segments[$segment] += $seconds;
    }

    public static function all(): array
    {
        return self::$segments;
    }
}
