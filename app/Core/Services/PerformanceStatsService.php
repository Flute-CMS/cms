<?php

namespace Flute\Core\Services;

use Flute\Core\Database\DatabaseTimingLogger;
use Flute\Core\Modules\Page\Services\WidgetRenderTiming;
use Flute\Core\Template\TemplateRenderTiming;
use Throwable;

class PerformanceStatsService
{
    protected const ROUTES_CACHE_KEY = 'performance.routes_stats';

    protected const WIDGETS_CACHE_KEY = 'performance.widgets_stats';

    protected const VIEWS_CACHE_KEY = 'performance.views_stats';

    protected const QUERIES_CACHE_KEY = 'performance.queries_stats';

    protected const MAX_ROUTES = 200;

    protected const MAX_WIDGETS = 100;

    protected const MAX_VIEWS = 100;

    protected const MAX_QUERIES = 100;

    protected const MAX_SAMPLES_PER_ROUTE = 50;

    protected const MAX_SAMPLES_PER_WIDGET = 50;

    protected const MAX_SAMPLES_PER_VIEW = 50;

    protected const MAX_SAMPLES_PER_QUERY = 50;

    protected static bool $saved = false;

    public static function saveRouteStats(): void
    {
        if (self::$saved || !function_exists('cache') || is_cli()) {
            return;
        }

        self::$saved = true;

        try {
            $req = request();
            $path = $req->getPathInfo();
            $method = $req->getMethod();

            if (str_starts_with($path, '/assets/') || str_starts_with($path, '/_')) {
                return;
            }

            $routeKey = $method . ':' . self::normalizeRoute($path);

            $now = microtime(true);
            $start = defined('FLUTE_ROUTER_START') ? (float) constant('FLUTE_ROUTER_START') : $now;
            $totalTime = max(0, $now - $start);

            $dbTime = DatabaseTimingLogger::getTotalTime();
            $dbCount = DatabaseTimingLogger::getTotalCount();
            $memoryPeak = memory_get_peak_usage(true);

            $stats = cache()->get(self::ROUTES_CACHE_KEY, [
                'routes' => [],
                'last_updated' => null,
                'total_requests' => 0,
            ]);

            $stats['total_requests']++;
            $stats['last_updated'] = time();

            if (!isset($stats['routes'][$routeKey])) {
                if (count($stats['routes']) >= self::MAX_ROUTES) {
                    uasort($stats['routes'], static fn ($a, $b) => ($a['last_hit'] ?? 0) <=> ($b['last_hit'] ?? 0));
                    array_shift($stats['routes']);
                }

                $stats['routes'][$routeKey] = [
                    'method' => $method,
                    'path' => $path,
                    'samples' => [],
                    'hits' => 0,
                    'last_hit' => null,
                ];
            }

            $stats['routes'][$routeKey]['hits']++;
            $stats['routes'][$routeKey]['last_hit'] = time();
            $stats['routes'][$routeKey]['samples'][] = [
                'time' => $totalTime,
                'db_time' => $dbTime,
                'db_queries' => $dbCount,
                'memory' => $memoryPeak,
                'timestamp' => time(),
            ];

            if (count($stats['routes'][$routeKey]['samples']) > self::MAX_SAMPLES_PER_ROUTE) {
                $stats['routes'][$routeKey]['samples'] = array_slice(
                    $stats['routes'][$routeKey]['samples'],
                    -self::MAX_SAMPLES_PER_ROUTE
                );
            }

            cache()->set(self::ROUTES_CACHE_KEY, $stats, 86400 * 7);

            self::saveWidgetStats();
            self::saveViewStats();
            self::saveQueryStats();
        } catch (Throwable $e) {
        }
    }

    public static function saveWidgetStats(): void
    {
        try {
            $widgetTimes = WidgetRenderTiming::all();
            $widgetCounts = WidgetRenderTiming::counts();

            if (empty($widgetTimes)) {
                return;
            }

            $stats = cache()->get(self::WIDGETS_CACHE_KEY, [
                'widgets' => [],
                'last_updated' => null,
            ]);

            $stats['last_updated'] = time();

            foreach ($widgetTimes as $widgetName => $time) {
                if (!isset($stats['widgets'][$widgetName])) {
                    if (count($stats['widgets']) >= self::MAX_WIDGETS) {
                        uasort($stats['widgets'], static fn ($a, $b) => ($a['last_hit'] ?? 0) <=> ($b['last_hit'] ?? 0));
                        array_shift($stats['widgets']);
                    }

                    $stats['widgets'][$widgetName] = [
                        'samples' => [],
                        'hits' => 0,
                        'last_hit' => null,
                    ];
                }

                $count = $widgetCounts[$widgetName] ?? 1;
                $stats['widgets'][$widgetName]['hits'] += $count;
                $stats['widgets'][$widgetName]['last_hit'] = time();
                $stats['widgets'][$widgetName]['samples'][] = [
                    'time' => $time,
                    'count' => $count,
                    'timestamp' => time(),
                ];

                if (count($stats['widgets'][$widgetName]['samples']) > self::MAX_SAMPLES_PER_WIDGET) {
                    $stats['widgets'][$widgetName]['samples'] = array_slice(
                        $stats['widgets'][$widgetName]['samples'],
                        -self::MAX_SAMPLES_PER_WIDGET
                    );
                }
            }

            cache()->set(self::WIDGETS_CACHE_KEY, $stats, 86400 * 7);
        } catch (Throwable $e) {
        }
    }

    public static function saveViewStats(): void
    {
        try {
            $viewTimes = TemplateRenderTiming::all();

            if (empty($viewTimes)) {
                return;
            }

            $stats = cache()->get(self::VIEWS_CACHE_KEY, [
                'views' => [],
                'last_updated' => null,
            ]);

            $stats['last_updated'] = time();

            foreach ($viewTimes as $viewName => $time) {
                if (!isset($stats['views'][$viewName])) {
                    if (count($stats['views']) >= self::MAX_VIEWS) {
                        uasort($stats['views'], static fn ($a, $b) => ($a['last_hit'] ?? 0) <=> ($b['last_hit'] ?? 0));
                        array_shift($stats['views']);
                    }

                    $stats['views'][$viewName] = [
                        'samples' => [],
                        'hits' => 0,
                        'last_hit' => null,
                    ];
                }

                $stats['views'][$viewName]['hits']++;
                $stats['views'][$viewName]['last_hit'] = time();
                $stats['views'][$viewName]['samples'][] = [
                    'time' => $time,
                    'timestamp' => time(),
                ];

                if (count($stats['views'][$viewName]['samples']) > self::MAX_SAMPLES_PER_VIEW) {
                    $stats['views'][$viewName]['samples'] = array_slice(
                        $stats['views'][$viewName]['samples'],
                        -self::MAX_SAMPLES_PER_VIEW
                    );
                }
            }

            cache()->set(self::VIEWS_CACHE_KEY, $stats, 86400 * 7);
        } catch (Throwable $e) {
        }
    }

    public static function saveQueryStats(): void
    {
        try {
            $queries = DatabaseTimingLogger::getQueries();

            if (empty($queries)) {
                return;
            }

            $stats = cache()->get(self::QUERIES_CACHE_KEY, [
                'queries' => [],
                'last_updated' => null,
            ]);

            $stats['last_updated'] = time();

            foreach ($queries as $queryData) {
                $queryKey = self::normalizeQueryForKey($queryData['query']);

                if (!isset($stats['queries'][$queryKey])) {
                    if (count($stats['queries']) >= self::MAX_QUERIES) {
                        uasort($stats['queries'], static fn ($a, $b) => ($a['last_hit'] ?? 0) <=> ($b['last_hit'] ?? 0));
                        array_shift($stats['queries']);
                    }

                    $stats['queries'][$queryKey] = [
                        'query' => $queryData['query'],
                        'samples' => [],
                        'hits' => 0,
                        'last_hit' => null,
                    ];
                }

                $stats['queries'][$queryKey]['hits']++;
                $stats['queries'][$queryKey]['last_hit'] = time();
                $stats['queries'][$queryKey]['samples'][] = [
                    'time' => $queryData['time'],
                    'timestamp' => time(),
                ];

                if (count($stats['queries'][$queryKey]['samples']) > self::MAX_SAMPLES_PER_QUERY) {
                    $stats['queries'][$queryKey]['samples'] = array_slice(
                        $stats['queries'][$queryKey]['samples'],
                        -self::MAX_SAMPLES_PER_QUERY
                    );
                }
            }

            cache()->set(self::QUERIES_CACHE_KEY, $stats, 86400 * 7);
        } catch (Throwable $e) {
        }
    }

    public static function getRouteStats(): array
    {
        try {
            if (!function_exists('cache')) {
                return [];
            }

            return cache()->get(self::ROUTES_CACHE_KEY, []);
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function getWidgetStats(): array
    {
        try {
            if (!function_exists('cache')) {
                return [];
            }

            return cache()->get(self::WIDGETS_CACHE_KEY, []);
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function getViewStats(): array
    {
        try {
            if (!function_exists('cache')) {
                return [];
            }

            return cache()->get(self::VIEWS_CACHE_KEY, []);
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function getQueryStats(): array
    {
        try {
            if (!function_exists('cache')) {
                return [];
            }

            return cache()->get(self::QUERIES_CACHE_KEY, []);
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function clearRouteStats(): void
    {
        try {
            if (function_exists('cache')) {
                cache()->delete(self::ROUTES_CACHE_KEY);
            }
        } catch (Throwable $e) {
        }
    }

    public static function clearWidgetStats(): void
    {
        try {
            if (function_exists('cache')) {
                cache()->delete(self::WIDGETS_CACHE_KEY);
            }
        } catch (Throwable $e) {
        }
    }

    public static function clearViewStats(): void
    {
        try {
            if (function_exists('cache')) {
                cache()->delete(self::VIEWS_CACHE_KEY);
            }
        } catch (Throwable $e) {
        }
    }

    public static function clearQueryStats(): void
    {
        try {
            if (function_exists('cache')) {
                cache()->delete(self::QUERIES_CACHE_KEY);
            }
        } catch (Throwable $e) {
        }
    }

    public static function clearAllStats(): void
    {
        self::clearRouteStats();
        self::clearWidgetStats();
        self::clearViewStats();
        self::clearQueryStats();
    }

    protected static function normalizeRoute(string $path): string
    {
        $path = preg_replace('/\/\d+/', '/{id}', $path);

        $path = preg_replace('/\/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', '/{uuid}', $path);

        $path = preg_replace('/\/[a-f0-9]{32,64}/i', '/{hash}', $path);

        return $path ?: '/';
    }

    protected static function normalizeQueryForKey(string $query): string
    {
        $query = preg_replace('/\s+/', ' ', trim($query));

        $query = preg_replace('/= ?\d+/', '= ?', $query);
        $query = preg_replace("/= ?'[^']*'/", "= '?'", $query);
        $query = preg_replace('/= ?"[^"]*"/', '= "?"', $query);

        $query = preg_replace('/IN\s*\([^)]+\)/i', 'IN (?)', $query);

        $query = preg_replace('/LIMIT\s+\d+/i', 'LIMIT ?', $query);
        $query = preg_replace('/OFFSET\s+\d+/i', 'OFFSET ?', $query);

        return substr($query, 0, 200);
    }
}
