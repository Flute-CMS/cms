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

    public static function run(int $limit = 25, int $maxTotalSeconds = 60, int $maxTaskSeconds = 30): void
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
            // Set overall time limit instead of unlimited
            if (function_exists('set_time_limit')) {
                @set_time_limit($maxTotalSeconds + 10);
            }

            $startedAt = microtime(true);
            $timings = [];

            $count = 0;
            foreach (self::$tasks as $id => $task) {
                if ($count >= $limit) {
                    break;
                }

                $elapsed = microtime(true) - $startedAt;
                if ($elapsed >= $maxTotalSeconds) {
                    if (function_exists('logs')) {
                        logs()->warning('SWR queue: total time limit reached', [
                            'elapsed' => $elapsed,
                            'limit' => $maxTotalSeconds,
                            'remaining_tasks' => count(self::$tasks) - $count,
                        ]);
                    }
                    break;
                }

                try {
                    $t0 = microtime(true);
                    $task();
                    $taskTime = microtime(true) - $t0;
                    $timings[$id] = $taskTime;

                    if ($taskTime > $maxTaskSeconds && function_exists('logs')) {
                        logs()->warning('SWR task exceeded time limit', [
                            'task' => $id,
                            'duration' => $taskTime,
                            'limit' => $maxTaskSeconds,
                        ]);
                    }
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
