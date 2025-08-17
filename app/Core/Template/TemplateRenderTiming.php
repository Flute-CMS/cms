<?php

namespace Flute\Core\Template;

/**
 * Collects Blade view rendering timings per template for the current request.
 */
class TemplateRenderTiming
{
    /** @var array<string,float> */
    private static array $times = [];

    /**
     * Add elapsed time for a specific view.
     */
    public static function add(string $view, float $seconds): void
    {
        if (!isset(self::$times[$view])) {
            self::$times[$view] = 0.0;
        }
        self::$times[$view] += $seconds;
    }

    /**
     * Return collected timings.
     * @return array<string,float>
     */
    public static function all(): array
    {
        return self::$times;
    }
}
