<?php

namespace Flute\Core\Profiling;

use Xhgui\Profiler\Profiler;
use Xhgui\Profiler\ProfilingFlags;

/**
 * Global application profiler that runs for the entire request lifecycle
 * and collects detailed performance data.
 */
class GlobalProfiler
{
    protected static ?Profiler $profiler = null;
    protected static array $profileData = [];
    protected static bool $enabled = false;

    /**
     * Start global profiling for the entire application
     */
    public static function start(): void
    {
        $config = include BASE_PATH . 'config/profiler.php';

        if (self::$enabled || !$config['enabled'] || !is_debug()) {
            return;
        }

        try {
            self::$profiler = new Profiler([
                'profiler.flags' => [ProfilingFlags::CPU, ProfilingFlags::MEMORY, ProfilingFlags::NO_BUILTINS],
                'profiler.options' => [
                    'ignored_functions' => [
                        'call_user_func',
                        'call_user_func_array',
                        'is_callable',
                        'strlen',
                        'array_key_exists',
                        'isset',
                        'empty',
                        'microtime',
                    ],
                ],
                'save.handler' => \Xhgui\Profiler\Profiler::SAVER_UPLOAD,
                'save.handler.upload' => [
                    'url' => $config['url'],
                    'timeout' => 3,
                    'token' => $config['token'],
                    'verify' => $config['verify'],
                ],
            ]);

            self::$profiler->start();

            self::$enabled = true;
        } catch (\Exception $e) {
            logs()->warning('Global profiler failed to start: ' . $e->getMessage());
        }
    }

    /**
     * Stop global profiling and collect data
     */
    public static function stop(): void
    {
        if (!self::$enabled || !self::$profiler) {
            return;
        }

        try {
            self::$profileData = self::$profiler->disable() ?: [];
            self::$enabled = false;

            // Save profile data if configured
            self::saveProfileData();
        } catch (\Exception $e) {
            logs()->warning('Global profiler failed to stop: ' . $e->getMessage());
        }
    }

    /**
     * Get collected profile data
     */
    public static function getProfileData(): array
    {
        return self::$profileData;
    }

    /**
     * Check if profiler is currently enabled
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Get function timings grouped by namespace/module
     */
    public static function getFunctionTimings(): array
    {
        if (empty(self::$profileData['profile'])) {
            return [];
        }

        $timings = [];
        $profile = self::$profileData['profile'];

        foreach ($profile as $function => $data) {
            $wallTime = ($data['wt'] ?? 0) / 1_000_000; // Convert to seconds

            if ($wallTime < 0.001) { // Skip functions under 1ms
                continue;
            }

            // Categorize by namespace
            $category = self::categorizeFunction($function);

            if (!isset($timings[$category])) {
                $timings[$category] = [];
            }

            $timings[$category][$function] = [
                'wall_time' => $wallTime,
                'cpu_time' => ($data['cpu'] ?? 0) / 1_000_000,
                'memory' => $data['mu'] ?? 0,
                'calls' => $data['ct'] ?? 1,
            ];
        }

        // Sort each category by wall time
        foreach ($timings as &$categoryFunctions) {
            uasort($categoryFunctions, function ($a, $b) {
                return $b['wall_time'] <=> $a['wall_time'];
            });
        }

        return $timings;
    }

    /**
     * Categorize function by namespace/origin
     */
    protected static function categorizeFunction(string $function): string
    {
        if (strpos($function, '\\Modules\\') !== false) {
            // Extract module name
            if (preg_match('/\\\\Modules\\\\([^\\\\]+)/', $function, $matches)) {
                return 'Module: ' . $matches[1];
            }

            return 'Modules';
        }

        if (strpos($function, '\\Flute\\Core\\') !== false) {
            return 'Core';
        }

        if (strpos($function, '\\Flute\\') !== false) {
            return 'Application';
        }

        if (strpos($function, '\\Cycle\\') !== false) {
            return 'Database (Cycle)';
        }

        if (strpos($function, '\\Tracy\\') !== false) {
            return 'Debug (Tracy)';
        }

        if (strpos($function, '\\DI\\') !== false || strpos($function, 'Container') !== false) {
            return 'DI Container';
        }

        if (strpos($function, '\\') === false) {
            return 'Global Functions';
        }

        return 'Third Party';
    }

    /**
     * Get top slowest functions overall
     */
    public static function getTopSlowFunctions(int $limit = 20): array
    {
        if (empty(self::$profileData['profile'])) {
            return [];
        }

        $functions = [];
        foreach (self::$profileData['profile'] as $function => $data) {
            $wallTime = ($data['wt'] ?? 0) / 1_000_000;

            if ($wallTime < 0.001) {
                continue;
            }

            $functions[] = [
                'function' => $function,
                'wall_time' => $wallTime,
                'cpu_time' => ($data['cpu'] ?? 0) / 1_000_000,
                'memory' => $data['mu'] ?? 0,
                'calls' => $data['ct'] ?? 1,
                'category' => self::categorizeFunction($function),
            ];
        }

        usort($functions, function ($a, $b) {
            return $b['wall_time'] <=> $a['wall_time'];
        });

        return array_slice($functions, 0, $limit);
    }

    /**
     * Save profile data to storage if enabled
     */
    protected static function saveProfileData(): void
    {
        if (empty(self::$profileData) || !config('app.save_profiling_data', false)) {
            return;
        }

        try {
            $filename = 'xhprof_' . uniqid() . '.json';
            $path = storage_path('logs/' . $filename);

            $data = [
                'timestamp' => time(),
                'url' => $_SERVER['REQUEST_URI'] ?? 'cli',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'total_time' => self::$profileData['meta']['request_ts']['wt'] ?? 0,
                'profile' => self::$profileData['profile'] ?? [],
            ];

            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            logs()->warning('Failed to save profile data: ' . $e->getMessage());
        }
    }
}
