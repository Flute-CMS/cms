<?php

namespace Flute\Admin\Packages\AboutSystem\Controllers\Concerns;

use Flute\Core\Services\PerformanceStatsService;
use Throwable;

trait GeneratesRoutesStats
{
    protected function generateRoutesPerformanceStatsSection(): string
    {
        $lines = [
            $this->sectionTitle('ROUTES/PAGES PERFORMANCE STATISTICS'),
        ];

        try {
            $stats = PerformanceStatsService::getRouteStats();

            if (empty($stats) || empty($stats['routes'])) {
                $lines[] = '';
                $lines[] = 'No route performance data collected yet.';
                $lines[] = 'Statistics are gathered automatically from regular page requests.';
                $lines[] = 'Check back after the site has been used for a while.';

                return implode("\n", $lines);
            }

            $routes = $stats['routes'];
            $totalRequests = $stats['total_requests'] ?? 0;

            $lines[] = '';
            $lines[] = 'Total tracked requests: ' . number_format($totalRequests);
            $lines[] = 'Unique routes tracked: ' . count($routes);

            if (!empty($stats['last_updated'])) {
                $lines[] = 'Last updated: ' . date('Y-m-d H:i:s', $stats['last_updated']);
            }

            $routeStats = $this->collectRouteStats($routes);

            uasort($routeStats, static fn($a, $b) => $b['avg_time'] <=> $a['avg_time']);

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'SLOWEST ROUTES (by average response time)';
            $lines[] = str_repeat('-', 120);
            $lines[] = sprintf(
                '%-7s %-45s %10s %10s %10s %10s %8s %10s',
                'METHOD',
                'ROUTE',
                'AVG',
                'MEDIAN',
                'MAX',
                'DB TIME',
                'DB %',
                'HITS',
            );
            $lines[] = str_repeat('-', 120);

            $top20 = array_slice($routeStats, 0, 20, true);
            foreach ($top20 as $stat) {
                $lines[] = sprintf(
                    '%-7s %-45s %9.0fms %9.0fms %9.0fms %9.0fms %7.1f%% %10d',
                    $stat['method'],
                    substr($stat['path'], 0, 43),
                    $stat['avg_time'] * 1000,
                    $stat['median_time'] * 1000,
                    $stat['max_time'] * 1000,
                    $stat['avg_db_time'] * 1000,
                    $stat['db_pct'],
                    $stat['hits'],
                );
            }

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'MOST DATABASE-HEAVY ROUTES (by DB time percentage)';
            $lines[] = str_repeat('-', 120);

            uasort($routeStats, static fn($a, $b) => $b['db_pct'] <=> $a['db_pct']);
            $topDbHeavy = array_slice($routeStats, 0, 10, true);

            $lines[] = sprintf(
                '%-7s %-45s %10s %10s %12s %10s',
                'METHOD',
                'ROUTE',
                'DB TIME',
                'DB %',
                'AVG QUERIES',
                'TOTAL TIME',
            );
            $lines[] = str_repeat('-', 100);

            foreach ($topDbHeavy as $stat) {
                $lines[] = sprintf(
                    '%-7s %-45s %9.0fms %9.1f%% %12.1f %9.0fms',
                    $stat['method'],
                    substr($stat['path'], 0, 43),
                    $stat['avg_db_time'] * 1000,
                    $stat['db_pct'],
                    $stat['avg_db_queries'],
                    $stat['avg_time'] * 1000,
                );
            }

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'MOST MEMORY-INTENSIVE ROUTES';
            $lines[] = str_repeat('-', 120);

            uasort($routeStats, static fn($a, $b) => $b['avg_memory'] <=> $a['avg_memory']);
            $topMemory = array_slice($routeStats, 0, 10, true);

            $lines[] = sprintf('%-7s %-45s %12s %12s %10s', 'METHOD', 'ROUTE', 'AVG MEMORY', 'MAX MEMORY', 'HITS');
            $lines[] = str_repeat('-', 90);

            foreach ($topMemory as $stat) {
                $lines[] = sprintf(
                    '%-7s %-45s %12s %12s %10d',
                    $stat['method'],
                    substr($stat['path'], 0, 43),
                    $this->formatBytes($stat['avg_memory']),
                    $this->formatBytes($stat['max_memory']),
                    $stat['hits'],
                );
            }

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'MOST FREQUENTLY ACCESSED ROUTES';
            $lines[] = str_repeat('-', 120);

            uasort($routeStats, static fn($a, $b) => $b['hits'] <=> $a['hits']);
            $topHits = array_slice($routeStats, 0, 10, true);

            $lines[] = sprintf('%-7s %-50s %10s %10s %10s', 'METHOD', 'ROUTE', 'HITS', 'AVG TIME', 'TOTAL TIME');
            $lines[] = str_repeat('-', 95);

            foreach ($topHits as $stat) {
                $totalTime = $stat['avg_time'] * $stat['hits'];
                $lines[] = sprintf(
                    '%-7s %-50s %10d %9.0fms %9.1fs',
                    $stat['method'],
                    substr($stat['path'], 0, 48),
                    $stat['hits'],
                    $stat['avg_time'] * 1000,
                    $totalTime,
                );
            }

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'ROUTES WITH HIGHEST VARIANCE (unstable performance)';
            $lines[] = str_repeat('-', 120);

            $variance = $this->computeRouteVariance($routes);

            if (!empty($variance)) {
                uasort($variance, static fn($a, $b) => $b['cv'] <=> $a['cv']);
                $topVariance = array_slice($variance, 0, 10, true);

                $lines[] = sprintf(
                    '%-7s %-45s %12s %10s %10s %8s',
                    'METHOD',
                    'ROUTE',
                    'STD DEV',
                    'CV %',
                    'AVG TIME',
                    'SAMPLES',
                );
                $lines[] = str_repeat('-', 100);

                foreach ($topVariance as $stat) {
                    $lines[] = sprintf(
                        '%-7s %-45s %11.0fms %9.1f%% %9.0fms %8d',
                        $stat['method'],
                        substr($stat['path'], 0, 43),
                        $stat['std_dev'] * 1000,
                        $stat['cv'],
                        $stat['avg'] * 1000,
                        $stat['samples'],
                    );
                }
            } else {
                $lines[] = 'Not enough data (need at least 5 samples per route)';
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading route statistics: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }

    private function collectRouteStats(array $routes): array
    {
        $routeStats = [];
        foreach ($routes as $routeKey => $routeData) {
            $samples = $routeData['samples'] ?? [];
            if (empty($samples)) {
                continue;
            }

            $times = array_column($samples, 'time');
            $dbTimes = array_column($samples, 'db_time');
            $dbQueries = array_column($samples, 'db_queries');
            $memories = array_column($samples, 'memory');

            $routeStats[$routeKey] = [
                'method' => $routeData['method'],
                'path' => $routeData['path'],
                'hits' => $routeData['hits'] ?? count($samples),
                'samples' => count($samples),
                'avg_time' => array_sum($times) / count($times),
                'median_time' => $this->calculateMedian($times),
                'min_time' => min($times),
                'max_time' => max($times),
                'avg_db_time' => !empty($dbTimes) ? array_sum($dbTimes) / count($dbTimes) : 0,
                'avg_db_queries' => !empty($dbQueries) ? array_sum($dbQueries) / count($dbQueries) : 0,
                'avg_memory' => !empty($memories) ? array_sum($memories) / count($memories) : 0,
                'max_memory' => !empty($memories) ? max($memories) : 0,
                'db_pct' => 0,
            ];

            if ($routeStats[$routeKey]['avg_time'] > 0) {
                $routeStats[$routeKey]['db_pct'] =
                    ( $routeStats[$routeKey]['avg_db_time'] / $routeStats[$routeKey]['avg_time'] ) * 100;
            }
        }

        return $routeStats;
    }

    private function computeRouteVariance(array $routes): array
    {
        $variance = [];
        foreach ($routes as $routeKey => $routeData) {
            $samples = $routeData['samples'] ?? [];
            if (count($samples) >= 5) {
                $times = array_column($samples, 'time');
                $stdDev = $this->calculateStdDev($times);
                $avg = array_sum($times) / count($times);
                $cv = $avg > 0 ? ( $stdDev / $avg ) * 100 : 0;
                $variance[$routeKey] = [
                    'method' => $routeData['method'],
                    'path' => $routeData['path'],
                    'std_dev' => $stdDev,
                    'cv' => $cv,
                    'avg' => $avg,
                    'samples' => count($samples),
                ];
            }
        }

        return $variance;
    }
}
