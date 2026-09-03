<?php

namespace Flute\Admin\Packages\AboutSystem\Controllers\Concerns;

use Flute\Core\Services\PerformanceStatsService;
use Throwable;

trait GeneratesQueriesAndViewStats
{
    protected function generateQueriesPerformanceStatsSection(): string
    {
        $lines = [
            $this->sectionTitle('SQL QUERIES PERFORMANCE STATISTICS'),
        ];

        try {
            $stats = PerformanceStatsService::getQueryStats();

            if (empty($stats) || empty($stats['queries'])) {
                $lines[] = 'No SQL query performance data collected yet.';
                $lines[] = 'Statistics are gathered automatically from database queries.';

                return implode("\n", $lines);
            }

            $queries = $stats['queries'];
            $lastUpdated = $stats['last_updated'] ?? null;

            if ($lastUpdated) {
                $lines[] = 'Last Updated: ' . date('Y-m-d H:i:s', $lastUpdated);
            }

            $lines[] = '';

            $processedQueries = [];
            foreach ($queries as $queryKey => $queryData) {
                $samples = $queryData['samples'] ?? [];
                if (empty($samples)) {
                    continue;
                }

                $times = array_column($samples, 'time');
                $query = $queryData['query'] ?? $queryKey;

                if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|SHOW|DESCRIBE)/i', $query, $m)) {
                    $type = strtoupper($m[1]);
                } else {
                    $type = 'SQL';
                }

                $processedQueries[$queryKey] = [
                    'query' => $query,
                    'type' => $type,
                    'hits' => $queryData['hits'] ?? 0,
                    'avg_time' => array_sum($times) / count($times),
                    'median_time' => $this->calculateMedian($times),
                    'min_time' => min($times),
                    'max_time' => max($times),
                    'std_dev' => $this->calculateStdDev($times),
                    'samples' => count($samples),
                ];
            }

            if (empty($processedQueries)) {
                $lines[] = 'No query data available.';

                return implode("\n", $lines);
            }

            uasort($processedQueries, static fn($a, $b) => $b['avg_time'] <=> $a['avg_time']);

            $lines[] = 'Total unique query patterns: ' . count($processedQueries);
            $lines[] = '';

            $lines[] = str_repeat('=', 140);
            $lines[] = 'TOP 20 SLOWEST QUERIES (sorted by avg time)';
            $lines[] = str_repeat('-', 140);

            $topQueries = array_slice($processedQueries, 0, 20, true);
            $i = 1;

            foreach ($topQueries as $stat) {
                $lines[] = sprintf(
                    '%2d. [%s] hits: %d, avg: %.1fms, median: %.1fms, min: %.1fms, max: %.1fms',
                    $i++,
                    $stat['type'],
                    $stat['hits'],
                    $stat['avg_time'] * 1000,
                    $stat['median_time'] * 1000,
                    $stat['min_time'] * 1000,
                    $stat['max_time'] * 1000,
                );

                $queryDisplay = $this->sanitizeSqlQuery((string) $stat['query']);
                $lines[] = '    ' . $queryDisplay;
                $lines[] = '';
            }

            $lines[] = str_repeat('=', 140);
            $lines[] = 'QUERIES BY TYPE';
            $lines[] = str_repeat('-', 140);

            $byType = [];
            foreach ($processedQueries as $stat) {
                $type = $stat['type'];
                if (!isset($byType[$type])) {
                    $byType[$type] = ['count' => 0, 'total_hits' => 0, 'total_time' => 0];
                }
                $byType[$type]['count']++;
                $byType[$type]['total_hits'] += $stat['hits'];
                $byType[$type]['total_time'] += $stat['avg_time'] * $stat['hits'];
            }

            arsort($byType);

            $lines[] = sprintf('%-12s %12s %15s %15s', 'TYPE', 'PATTERNS', 'TOTAL HITS', 'TOTAL TIME');
            $lines[] = str_repeat('-', 60);

            foreach ($byType as $type => $data) {
                $lines[] = sprintf(
                    '%-12s %12d %15d %14.1fms',
                    $type,
                    $data['count'],
                    $data['total_hits'],
                    $data['total_time'] * 1000,
                );
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading query statistics: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }

    protected function generateWidgetsPerformanceStatsSection(): string
    {
        $lines = [
            $this->sectionTitle('WIDGETS PERFORMANCE STATISTICS'),
        ];

        try {
            $stats = PerformanceStatsService::getWidgetStats();

            if (empty($stats) || empty($stats['widgets'])) {
                $lines[] = 'No widget performance data collected yet.';
                $lines[] = 'Statistics are gathered automatically when widgets are rendered.';

                return implode("\n", $lines);
            }

            $widgets = $stats['widgets'];
            $lastUpdated = $stats['last_updated'] ?? null;

            if ($lastUpdated) {
                $lines[] = 'Last Updated: ' . date('Y-m-d H:i:s', $lastUpdated);
            }

            $lines[] = '';

            $processedWidgets = [];
            foreach ($widgets as $widgetName => $widgetData) {
                $samples = $widgetData['samples'] ?? [];
                if (empty($samples)) {
                    continue;
                }

                $times = array_column($samples, 'time');
                $counts = array_column($samples, 'count');
                $totalRenders = array_sum($counts);

                $processedWidgets[$widgetName] = [
                    'name' => $widgetName,
                    'hits' => $widgetData['hits'] ?? 0,
                    'total_renders' => $totalRenders,
                    'avg_time' => array_sum($times) / count($times),
                    'median_time' => $this->calculateMedian($times),
                    'min_time' => min($times),
                    'max_time' => max($times),
                    'std_dev' => $this->calculateStdDev($times),
                    'samples' => count($samples),
                    'last_hit' => $widgetData['last_hit'] ?? null,
                ];
            }

            if (empty($processedWidgets)) {
                $lines[] = 'No widget data available.';

                return implode("\n", $lines);
            }

            uasort($processedWidgets, static fn($a, $b) => $b['avg_time'] <=> $a['avg_time']);

            $lines[] = str_repeat('=', 120);
            $lines[] = 'WIDGET RENDER TIMES (sorted by avg time)';
            $lines[] = str_repeat('-', 120);
            $lines[] = sprintf(
                '%-35s %8s %10s %10s %10s %10s %10s %8s',
                'WIDGET',
                'HITS',
                'AVG',
                'MEDIAN',
                'MIN',
                'MAX',
                'STD DEV',
                'SAMPLES',
            );
            $lines[] = str_repeat('-', 120);

            foreach ($processedWidgets as $widgetName => $stat) {
                $lines[] = sprintf(
                    '%-35s %8d %9.1fms %9.1fms %9.1fms %9.1fms %9.1fms %8d',
                    substr($widgetName, 0, 33),
                    $stat['hits'],
                    $stat['avg_time'] * 1000,
                    $stat['median_time'] * 1000,
                    $stat['min_time'] * 1000,
                    $stat['max_time'] * 1000,
                    $stat['std_dev'] * 1000,
                    $stat['samples'],
                );
            }

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'TOP 10 SLOWEST WIDGETS';
            $lines[] = str_repeat('-', 120);

            $topSlowest = array_slice($processedWidgets, 0, 10, true);
            $i = 1;
            foreach ($topSlowest as $widgetName => $stat) {
                $lines[] = sprintf(
                    '%2d. %-35s avg: %.1fms (max: %.1fms, %d samples)',
                    $i++,
                    $widgetName,
                    $stat['avg_time'] * 1000,
                    $stat['max_time'] * 1000,
                    $stat['samples'],
                );
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading widget statistics: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }

    protected function generateViewsPerformanceStatsSection(): string
    {
        $lines = [
            $this->sectionTitle('VIEWS/TEMPLATES PERFORMANCE STATISTICS'),
        ];

        try {
            $stats = PerformanceStatsService::getViewStats();

            if (empty($stats) || empty($stats['views'])) {
                $lines[] = 'No view performance data collected yet.';
                $lines[] = 'Statistics are gathered automatically when views are rendered.';

                return implode("\n", $lines);
            }

            $views = $stats['views'];
            $lastUpdated = $stats['last_updated'] ?? null;

            if ($lastUpdated) {
                $lines[] = 'Last Updated: ' . date('Y-m-d H:i:s', $lastUpdated);
            }

            $lines[] = '';

            $processedViews = [];
            foreach ($views as $viewName => $viewData) {
                $samples = $viewData['samples'] ?? [];
                if (empty($samples)) {
                    continue;
                }

                $times = array_column($samples, 'time');

                $processedViews[$viewName] = [
                    'name' => $viewName,
                    'hits' => $viewData['hits'] ?? 0,
                    'avg_time' => array_sum($times) / count($times),
                    'median_time' => $this->calculateMedian($times),
                    'min_time' => min($times),
                    'max_time' => max($times),
                    'std_dev' => $this->calculateStdDev($times),
                    'samples' => count($samples),
                    'last_hit' => $viewData['last_hit'] ?? null,
                ];
            }

            if (empty($processedViews)) {
                $lines[] = 'No view data available.';

                return implode("\n", $lines);
            }

            uasort($processedViews, static fn($a, $b) => $b['avg_time'] <=> $a['avg_time']);

            $totalViews = count($processedViews);
            $lines[] = "Total unique views tracked: {$totalViews}";
            $lines[] = '';

            $lines[] = str_repeat('=', 130);
            $lines[] = 'TOP 30 SLOWEST VIEWS (sorted by avg time)';
            $lines[] = str_repeat('-', 130);
            $lines[] = sprintf(
                '%-55s %8s %10s %10s %10s %10s %8s',
                'VIEW',
                'HITS',
                'AVG',
                'MEDIAN',
                'MIN',
                'MAX',
                'SAMPLES',
            );
            $lines[] = str_repeat('-', 130);

            $topViews = array_slice($processedViews, 0, 30, true);

            foreach ($topViews as $viewName => $stat) {
                $displayName = strlen($viewName) > 53 ? '...' . substr($viewName, -50) : $viewName;
                $lines[] = sprintf(
                    '%-55s %8d %9.1fms %9.1fms %9.1fms %9.1fms %8d',
                    $displayName,
                    $stat['hits'],
                    $stat['avg_time'] * 1000,
                    $stat['median_time'] * 1000,
                    $stat['min_time'] * 1000,
                    $stat['max_time'] * 1000,
                    $stat['samples'],
                );
            }

            $totalTime = array_sum(array_column($processedViews, 'avg_time'));
            $lines[] = '';
            $lines[] = sprintf('Total avg view render time: %.1fms', $totalTime * 1000);
        } catch (Throwable $e) {
            $lines[] = 'Error loading view statistics: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }
}
