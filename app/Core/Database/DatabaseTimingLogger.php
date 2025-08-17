<?php

namespace Flute\Core\Database;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Decorator for PSR logger used by Cycle DBAL to accumulate total SQL execution time.
 */
class DatabaseTimingLogger implements LoggerInterface
{
    use LoggerTrait;

    private LoggerInterface $inner;

    /**
     * Total time spent on SQL queries during current request (seconds)
     * @var float
     */
    private static float $totalTime = 0.0;

    public function __construct(LoggerInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * Retrieve accumulated database time in seconds.
     */
    public static function getTotalTime(): float
    {
        return self::$totalTime;
    }

    /**
     * Intercept log records coming from Cycle DBAL and try to extract elapsed time.
     * Cycle sends messages like "SELECT ... {elapsed: 2.34ms}" or "... 2.34 ms".
     * We parse numeric value and convert ms → s.
     */
    public function log($level, $message, array $context = []): void
    {
        if (isset($context['elapsed'])) {
            $elapsed = (float) $context['elapsed'];
            if ($elapsed > 1) {
                $elapsed /= 1000;
            }
            self::$totalTime += $elapsed;
        } else {
            if (preg_match('/([0-9.]+)\s*ms/iu', (string) $message, $m)) {
                self::$totalTime += ((float) $m[1]) / 1000;
            } elseif (preg_match('/([0-9.]+)\s*s/iu', (string) $message, $m)) {
                self::$totalTime += (float) $m[1];
            }
        }

        $this->inner->log($level, $message, $context);
    }
}
