<?php

namespace Flute\Core\Database;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Decorator for PSR logger used by Cycle DBAL to accumulate total SQL execution time.
 * Always tracks timing for performance stats, but only logs to file when database.debug is enabled.
 */
class DatabaseTimingLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * Max queries to track per request
     */
    private const MAX_QUERIES_PER_REQUEST = 100;

    private LoggerInterface $inner;

    private bool $logToFile;

    /**
     * Total time spent on SQL queries during current request (seconds)
     */
    private static float $totalTime = 0.0;

    /**
     * Total number of SQL statements executed during current request
     */
    private static int $totalCount = 0;

    /**
     * Individual query timings for current request
     * @var array<int, array{query: string, time: float}>
     */
    private static array $queries = [];

    public function __construct(LoggerInterface $inner, bool $logToFile = true)
    {
        $this->inner = $inner;
        $this->logToFile = $logToFile;
    }

    /**
     * Retrieve accumulated database time in seconds.
     */
    public static function getTotalTime(): float
    {
        return self::$totalTime;
    }

    /**
     * Retrieve total query count for current request.
     */
    public static function getTotalCount(): int
    {
        return self::$totalCount;
    }

    /**
     * Get all tracked queries for current request
     * @return array<int, array{query: string, time: float}>
     */
    public static function getQueries(): array
    {
        return self::$queries;
    }

    /**
     * Get slowest queries from current request
     * @return array<int, array{query: string, time: float}>
     */
    public static function getSlowestQueries(int $limit = 10): array
    {
        $queries = self::$queries;
        usort($queries, static fn ($a, $b) => $b['time'] <=> $a['time']);

        return array_slice($queries, 0, $limit);
    }

    /**
     * Intercept log records coming from Cycle DBAL and try to extract elapsed time.
     * Cycle sends messages like "SELECT ... {elapsed: 2.34ms}" or "... 2.34 ms".
     * We parse numeric value and convert ms → s.
     */
    public function log($level, $message, array $context = []): void
    {
        self::$totalCount++;
        $elapsed = 0.0;

        if (isset($context['elapsed'])) {
            $elapsed = (float) $context['elapsed'];
            if ($elapsed > 1) {
                $elapsed /= 1000;
            }
            self::$totalTime += $elapsed;
        } else {
            if (preg_match('/([0-9.]+)\s*ms/iu', (string) $message, $m)) {
                $elapsed = ((float) $m[1]) / 1000;
                self::$totalTime += $elapsed;
            } elseif (preg_match('/([0-9.]+)\s*s/iu', (string) $message, $m)) {
                $elapsed = (float) $m[1];
                self::$totalTime += $elapsed;
            }
        }

        if ($elapsed > 0 && count(self::$queries) < self::MAX_QUERIES_PER_REQUEST) {
            $query = self::normalizeQuery((string) $message);
            self::$queries[] = [
                'query' => $query,
                'time' => $elapsed,
            ];
        }

        if ($this->logToFile) {
            $this->inner->log($level, $message, $context);
        }
    }

    /**
     * Normalize query for grouping (remove specific values)
     */
    private static function normalizeQuery(string $query): string
    {
        $query = preg_replace('/\s+/', ' ', trim($query));

        $query = preg_replace('/\d+(\.\d+)?\s*ms$/i', '', $query);

        if (strlen($query) > 200) {
            $query = substr($query, 0, 197) . '...';
        }

        return $query;
    }
}
