<?php

namespace Flute\Core\Cache;

use Throwable;

/**
 * Simple in-request queue for "stale-while-revalidate" tasks.
 * Tasks are executed after response is sent (see App::run()).
 */
final class SWRQueue
{
    /** @var array<string,callable> */
    private static array $tasks = [];

    private static bool $ran = false;

    public static function queue(string $id, callable $task): void
    {
        if (self::$ran) {
            return;
        }

        if (isset(self::$tasks[$id])) {
            return;
        }

        self::$tasks[$id] = $task;
    }

    public static function hasTasks(): bool
    {
        return !empty(self::$tasks);
    }

    public static function run(int $limit = 25): void
    {
        if (self::$ran) {
            return;
        }

        self::$ran = true;

        if (empty(self::$tasks)) {
            return;
        }

        // Best-effort: only one worker should run SWR tasks at a time.
        $lockFile = function_exists('storage_path')
            ? storage_path('app/swr_queue.lock')
            : (defined('BASE_PATH') ? BASE_PATH . 'storage/app/swr_queue.lock' : 'swr_queue.lock');

        $handle = @fopen($lockFile, 'w+');
        if ($handle === false) {
            return;
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return;
        }

        try {
            @ignore_user_abort(true);
            @set_time_limit(0);

            $startedAt = microtime(true);
            $timings = [];

            $count = 0;
            foreach (self::$tasks as $id => $task) {
                if ($count >= $limit) {
                    break;
                }

                try {
                    $t0 = microtime(true);
                    $task();
                    $timings[$id] = microtime(true) - $t0;
                } catch (Throwable $e) {
                    if (function_exists('logs')) {
                        logs()->warning($e);
                    }
                }

                $count++;
            }

            $total = microtime(true) - $startedAt;
            if ($total >= 0.2 && function_exists('logs')) {
                arsort($timings);
                logs()->info('SWR queue executed', [
                    'total_sec' => $total,
                    'tasks' => $count,
                    'slowest' => array_slice($timings, 0, 10, true),
                ]);
            }
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }
}
