<?php

namespace Flute\Core\Modules\Page\Services;

class WidgetRenderTiming
{
    protected static array $times = [];

    protected static array $counts = [];

    public static function add(string $widget, float $seconds): void
    {
        if (!isset(self::$times[$widget])) {
            self::$times[$widget] = 0.0;
            self::$counts[$widget] = 0;
        }
        self::$times[$widget] += $seconds;
        self::$counts[$widget]++;
    }

    public static function all(): array
    {
        return self::$times;
    }

    public static function counts(): array
    {
        return self::$counts;
    }

    public static function getTotalTime(): float
    {
        return array_sum(self::$times);
    }

    public static function getTotalCount(): int
    {
        return array_sum(self::$counts);
    }
}
