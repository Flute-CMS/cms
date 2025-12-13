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

            $count = 0;
            foreach (self::$tasks as $task) {
                if ($count >= $limit) {
                    break;
                }

                try {
                    $task();
                } catch (Throwable $e) {
                    if (function_exists('logs')) {
                        logs()->warning($e);
                    }
                }

                $count++;
            }
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }
}
