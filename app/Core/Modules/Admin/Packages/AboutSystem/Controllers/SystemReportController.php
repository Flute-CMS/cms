<?php

namespace Flute\Admin\Packages\AboutSystem\Controllers;

use Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper;
use Flute\Admin\Packages\Logs\Services\LogViewerService;
use Flute\Core\App;
use Flute\Core\Database\DatabaseManager;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\ModulesManager\ModuleRegister;
use Flute\Core\Services\PerformanceStatsService;
use Flute\Core\Support\BaseController;
use Flute\Core\Theme\ThemeManager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

class SystemReportController extends BaseController
{
    protected ModuleManager $moduleManager;

    protected ThemeManager $themeManager;

    protected LogViewerService $logViewerService;

    public function __construct(
        ModuleManager $moduleManager,
        ThemeManager $themeManager,
        LogViewerService $logViewerService
    ) {
        $this->moduleManager = $moduleManager;
        $this->themeManager = $themeManager;
        $this->logViewerService = $logViewerService;
    }

    public function download(): Response
    {
        $report = $this->generateReport();

        $filename = 'flute_system_report_' . date('Y-m-d_H-i-s') . '.txt';

        $response = new Response($report);
        $response->headers->set('Content-Type', 'text/plain; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        ));

        return $response;
    }

    protected function generateReport(): string
    {
        $sections = [];

        $sections[] = $this->generateHeader();
        $sections[] = $this->generateSystemSection();
        $sections[] = $this->generatePhpSection();
        $sections[] = $this->generateServerSection();
        $sections[] = $this->generateExtensionsSection();
        $sections[] = $this->generateModulesSection();
        $sections[] = $this->generateModulesPerformanceStatsSection();
        $sections[] = $this->generateProvidersPerformanceStatsSection();
        $sections[] = $this->generateRoutesPerformanceStatsSection();
        $sections[] = $this->generateQueriesPerformanceStatsSection();
        $sections[] = $this->generateWidgetsPerformanceStatsSection();
        $sections[] = $this->generateViewsPerformanceStatsSection();
        $sections[] = $this->generateThemesSection();
        $sections[] = $this->generateDatabaseSection();
        $sections[] = $this->generateCacheSection();
        $sections[] = $this->generateComposerSection();
        $sections[] = $this->generateDirectoriesSection();
        $sections[] = $this->generateConfigSection();
        $sections[] = $this->generateSessionSection();
        $sections[] = $this->generateRequestSection();
        $sections[] = $this->generateFullLogsSection();

        return implode("\n\n", array_filter($sections));
    }

    protected function generateHeader(): string
    {
        $lines = [
            str_repeat('=', 80),
            '                         FLUTE CMS SYSTEM REPORT',
            str_repeat('=', 80),
            '',
            'Generated: ' . date('Y-m-d H:i:s T'),
            'Report Version: 1.0',
            '',
            'This report contains detailed information about your Flute CMS installation.',
            'You can share this report with developers to help diagnose issues.',
            '',
            'IMPORTANT: This report may contain sensitive information.',
            'Review it before sharing and remove any sensitive data if necessary.',
            '',
        ];

        $lines[] = str_repeat('-', 80);
        $lines[] = 'IONCUBE STATUS (IMPORTANT FOR PERFORMANCE)';
        $lines[] = str_repeat('-', 80);

        $ioncubeLoaded = extension_loaded('ionCube Loader');
        $lines[] = 'ionCube Loader: ' . ($ioncubeLoaded ? 'LOADED' : 'NOT LOADED');

        if ($ioncubeLoaded) {
            if (function_exists('ioncube_loader_version')) {
                $lines[] = 'ionCube Version: ' . ioncube_loader_version();
            }

            $encodedPaths = ini_get('ioncube.loader.encoded_paths');
            $modulesPath = path('app/Modules');

            if ($encodedPaths) {
                $lines[] = 'encoded_paths: ' . $encodedPaths;

                $paths = array_filter(array_map('trim', explode(PATH_SEPARATOR, $encodedPaths)));
                $modulesConfigured = false;

                foreach ($paths as $p) {
                    if (str_starts_with($modulesPath, $p) || str_starts_with($p, $modulesPath)) {
                        $modulesConfigured = true;

                        break;
                    }
                }

                if ($modulesConfigured) {
                    $lines[] = 'Status: OK - Modules path is configured in encoded_paths';
                } else {
                    $lines[] = '*** WARNING: Modules path NOT in encoded_paths! ***';
                    $lines[] = '*** This causes ionCube to scan ALL files on EVERY request! ***';
                    $lines[] = '*** Add this to php.ini: ioncube.loader.encoded_paths="' . $modulesPath . '" ***';
                }
            } else {
                $lines[] = 'encoded_paths: NOT SET';
                $lines[] = '*** WARNING: encoded_paths is not configured! ***';
                $lines[] = '*** ionCube will scan ALL PHP files on EVERY request! ***';
                $lines[] = '*** This SIGNIFICANTLY degrades performance! ***';
                $lines[] = '*** Add to php.ini: ioncube.loader.encoded_paths="' . $modulesPath . '" ***';
            }
        }

        $lines[] = '';
        $lines[] = str_repeat('-', 80);
        $lines[] = 'PERFORMANCE TRACKING INFO';
        $lines[] = str_repeat('-', 80);
        $lines[] = 'Performance statistics are collected AUTOMATICALLY in any mode (debug/production).';
        $lines[] = 'Data is gathered from regular page requests and cached for 7 days.';
        $lines[] = '';
        $lines[] = 'What is tracked:';
        $lines[] = '  - Module boot times (collected during service provider registration)';
        $lines[] = '  - Service provider boot times (collected during app bootstrap)';
        $lines[] = '  - Route/page execution times (collected on every HTTP request)';
        $lines[] = '  - Widget render times (collected when widgets are rendered)';
        $lines[] = '  - View/template render times (collected when views are rendered)';
        $lines[] = '  - Database query times and counts (always tracked, logged only if database.debug=true)';
        $lines[] = '';
        $lines[] = 'Statistics include: avg, median, min, max, std dev, hit counts.';
        $lines[] = 'After a few page visits, the statistics will start appearing in this report.';
        $lines[] = str_repeat('-', 80);

        return implode("\n", $lines);
    }

    protected function generateSystemSection(): string
    {
        $systemInfo = AboutSystemHelper::getSystemInfo();
        $resourceUsage = AboutSystemHelper::getResourceUsage();

        $lines = [
            $this->sectionTitle('FLUTE CMS INFORMATION'),
            $this->formatKeyValue('Version', App::VERSION),
            $this->formatKeyValue('Framework', $systemInfo['framework'] ?? 'Flute CMS'),
            $this->formatKeyValue('License', $systemInfo['license'] ?? 'N/A'),
            $this->formatKeyValue('Project URL', $systemInfo['project_link'] ?? 'N/A'),
            '',
            $this->formatKeyValue('Debug Mode', config('app.debug', false) ? 'Enabled' : 'Disabled'),
            $this->formatKeyValue('Environment', config('app.env', 'production')),
            $this->formatKeyValue('Performance Mode', is_performance() ? 'Enabled' : 'Disabled'),
            $this->formatKeyValue('Base URL', config('app.url', 'N/A')),
            $this->formatKeyValue('Timezone', config('app.timezone', date_default_timezone_get())),
            $this->formatKeyValue('Locale', config('app.locale', 'en')),
            '',
            $this->formatKeyValue('CPU Load (1/5/15 min)', sprintf(
                '%.2f / %.2f / %.2f',
                $resourceUsage['cpu_load']['1min'],
                $resourceUsage['cpu_load']['5min'],
                $resourceUsage['cpu_load']['15min']
            )),
            $this->formatKeyValue('RAM Usage', sprintf(
                '%s / %s (%d%%)',
                AboutSystemHelper::formatBytes($resourceUsage['ram']['used']),
                AboutSystemHelper::formatBytes($resourceUsage['ram']['total']),
                $resourceUsage['ram']['percent']
            )),
        ];

        try {
            $bootTimes = app()->getBootTimes();
            if (!empty($bootTimes)) {
                $lines[] = '';
                $lines[] = 'Service Provider Boot Times:';
                arsort($bootTimes);
                $top10 = array_slice($bootTimes, 0, 10, true);
                foreach ($top10 as $provider => $time) {
                    $shortName = substr(strrchr($provider, '\\') ?: $provider, 1);
                    $lines[] = sprintf('  %-40s %.3fs', $shortName, $time);
                }
                $lines[] = sprintf('  %-40s %.3fs', 'TOTAL', array_sum($bootTimes));
            }
        } catch (Throwable $e) {
        }

        return implode("\n", $lines);
    }

    protected function generatePhpSection(): string
    {
        $phpInfo = AboutSystemHelper::getPhpInfo();
        $warnings = AboutSystemHelper::getPhpSettingWarnings();

        $lines = [
            $this->sectionTitle('PHP CONFIGURATION'),
            $this->formatKeyValue('PHP Version', PHP_VERSION),
            $this->formatKeyValue('SAPI', php_sapi_name()),
            $this->formatKeyValue('Memory Limit', $phpInfo['memory_limit'] ?? ini_get('memory_limit')),
            $this->formatKeyValue('Max Execution Time', $phpInfo['max_execution_time'] ?? ini_get('max_execution_time') . 's'),
            $this->formatKeyValue('Upload Max Filesize', $phpInfo['upload_max_filesize'] ?? ini_get('upload_max_filesize')),
            $this->formatKeyValue('Post Max Size', $phpInfo['post_max_size'] ?? ini_get('post_max_size')),
            $this->formatKeyValue('Max Input Vars', ini_get('max_input_vars')),
            $this->formatKeyValue('Display Errors', ini_get('display_errors') ? 'On' : 'Off'),
            $this->formatKeyValue('Error Reporting', $this->getErrorReportingString()),
            '',
            $this->formatKeyValue('OPcache', $phpInfo['opcache'] ?? 'N/A'),
            $this->formatKeyValue('JIT', $phpInfo['jit'] ?? 'N/A'),
            '',
            $this->formatKeyValue('Current Memory Usage', AboutSystemHelper::formatBytes(memory_get_usage(true))),
            $this->formatKeyValue('Peak Memory Usage', AboutSystemHelper::formatBytes(memory_get_peak_usage(true))),
        ];

        if (!empty($warnings)) {
            $lines[] = '';
            $lines[] = 'PHP Warnings:';
            foreach ($warnings as $key => $warning) {
                $lines[] = '  [!] ' . $warning;
            }
        }

        return implode("\n", $lines);
    }

    protected function generateServerSection(): string
    {
        $serverInfo = AboutSystemHelper::getServerInfo();

        $lines = [
            $this->sectionTitle('SERVER INFORMATION'),
            $this->formatKeyValue('Operating System', PHP_OS . ' (' . php_uname('r') . ')'),
            $this->formatKeyValue('Server Software', $serverInfo['server_software'] ?? ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A')),
            $this->formatKeyValue('Server Protocol', $serverInfo['server_protocol'] ?? ($_SERVER['SERVER_PROTOCOL'] ?? 'N/A')),
            $this->formatKeyValue('Server Name', $serverInfo['server_name'] ?? ($_SERVER['SERVER_NAME'] ?? 'N/A')),
            $this->formatKeyValue('Server Port', $serverInfo['server_port'] ?? ($_SERVER['SERVER_PORT'] ?? 'N/A')),
            $this->formatKeyValue('Document Root', $serverInfo['document_root'] ?? ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A')),
            $this->formatKeyValue('Server IP', $_SERVER['SERVER_ADDR'] ?? ($_SERVER['LOCAL_ADDR'] ?? 'N/A')),
            '',
            $this->formatKeyValue('Disk Total', $serverInfo['disk_total_space'] ?? 'N/A'),
            $this->formatKeyValue('Disk Free', $serverInfo['disk_free_space'] ?? 'N/A'),
            $this->formatKeyValue('Disk Used', $serverInfo['disk_used_space'] ?? 'N/A'),
            $this->formatKeyValue('Disk Usage', $serverInfo['disk_usage_percent'] ?? 'N/A'),
        ];

        return implode("\n", $lines);
    }

    protected function generateExtensionsSection(): string
    {
        $extensions = AboutSystemHelper::getRequiredExtensions();
        $loadedExtensions = get_loaded_extensions();

        $lines = [
            $this->sectionTitle('PHP EXTENSIONS'),
            '',
            'Required Extensions:',
        ];

        foreach ($extensions as $name => $info) {
            $status = $info['loaded'] ? '[OK]' : ($info['required'] ? '[MISSING]' : '[NOT LOADED]');
            $required = $info['required'] ? ' (required)' : ' (optional)';
            $lines[] = sprintf('  %s %s%s', $status, $name, $required);
        }

        $lines[] = '';
        $lines[] = 'All Loaded Extensions (' . count($loadedExtensions) . '):';
        $lines[] = '  ' . wordwrap(implode(', ', $loadedExtensions), 70, "\n  ");

        return implode("\n", $lines);
    }

    protected function generateModulesSection(): string
    {
        $lines = [
            $this->sectionTitle('INSTALLED MODULES'),
        ];

        try {
            $modules = $this->moduleManager->getModules();
            $bootTimes = ModuleRegister::getModulesBootTimes();

            if ($modules->isEmpty()) {
                $lines[] = 'No modules installed.';
            } else {
                $lines[] = '';
                $lines[] = sprintf('%-15s %-25s %-10s %-12s %-10s %s', 'STATUS', 'NAME', 'VERSION', 'KEY', 'BOOT TIME', 'DEPENDENCIES');
                $lines[] = str_repeat('-', 110);

                $totalBootTime = 0;

                foreach ($modules as $module) {
                    $status = $module->status ?? 'unknown';
                    $version = $module->version ?? 'N/A';
                    $name = $module->name ?? $module->key ?? 'Unknown';
                    $key = $module->key ?? 'N/A';

                    $statusIcon = match ($status) {
                        'active' => '[ACTIVE]',
                        'disabled' => '[DISABLED]',
                        'notinstalled' => '[NOT INSTALLED]',
                        default => '[' . strtoupper($status) . ']',
                    };

                    $moduleBootTime = $bootTimes[$key] ?? 0;
                    $bootTimeStr = $moduleBootTime > 0 ? sprintf('%.3fs', $moduleBootTime) : '-';
                    $totalBootTime += $moduleBootTime;

                    $deps = [];
                    if (!empty($module->dependencies)) {
                        foreach ($module->dependencies as $dep => $ver) {
                            $deps[] = $dep . ':' . $ver;
                        }
                    }
                    $depsStr = !empty($deps) ? implode(', ', $deps) : '-';

                    $lines[] = sprintf('%-15s %-25s %-10s %-12s %-10s %s', $statusIcon, substr($name, 0, 23), $version, $key, $bootTimeStr, $depsStr);
                }

                $lines[] = str_repeat('-', 110);
                $lines[] = '';
                $lines[] = 'Total modules: ' . $modules->count();
                $lines[] = 'Active: ' . $modules->filter(static fn ($m) => ($m->status ?? '') === 'active')->count();
                $lines[] = 'Disabled: ' . $modules->filter(static fn ($m) => ($m->status ?? '') === 'disabled')->count();
                $lines[] = sprintf('Current request total boot time: %.3fs', $totalBootTime);
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading modules: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function generateModulesPerformanceStatsSection(): string
    {
        $lines = [
            $this->sectionTitle('MODULES PERFORMANCE STATISTICS'),
        ];

        try {
            $stats = ModuleRegister::getBootTimesStats();

            if (empty($stats) || empty($stats['samples'])) {
                $lines[] = '';
                $lines[] = 'No performance data collected yet.';
                $lines[] = 'Statistics are gathered automatically over time from regular page requests.';
                $lines[] = 'Check back after the site has been used for a while.';

                return implode("\n", $lines);
            }

            $samples = $stats['samples'];
            $modulesData = $stats['modules'] ?? [];
            $samplesCount = count($samples);

            $lines[] = '';
            $lines[] = 'Data based on ' . $samplesCount . ' samples';

            if (!empty($stats['last_updated'])) {
                $lines[] = 'Last updated: ' . date('Y-m-d H:i:s', $stats['last_updated']);
                $firstSample = reset($samples);
                if ($firstSample) {
                    $lines[] = 'Data collected since: ' . date('Y-m-d H:i:s', $firstSample['time']);
                }
            }

            $totalTimes = array_column($samples, 'total');
            if (!empty($totalTimes)) {
                $lines[] = '';
                $lines[] = 'Total Modules Boot Time (all modules combined):';
                $lines[] = sprintf('  %-20s: %.3fs', 'Current', end($totalTimes));
                $lines[] = sprintf('  %-20s: %.3fs', 'Average', array_sum($totalTimes) / count($totalTimes));
                $lines[] = sprintf('  %-20s: %.3fs', 'Median', $this->calculateMedian($totalTimes));
                $lines[] = sprintf('  %-20s: %.3fs', 'Min', min($totalTimes));
                $lines[] = sprintf('  %-20s: %.3fs', 'Max', max($totalTimes));
                $lines[] = sprintf('  %-20s: %.3fs', 'Std Dev', $this->calculateStdDev($totalTimes));
            }

            if (!empty($modulesData)) {
                $moduleStats = [];
                foreach ($modulesData as $module => $times) {
                    if (empty($times)) {
                        continue;
                    }
                    $moduleStats[$module] = [
                        'avg' => array_sum($times) / count($times),
                        'median' => $this->calculateMedian($times),
                        'min' => min($times),
                        'max' => max($times),
                        'samples' => count($times),
                        'total_impact' => array_sum($times),
                    ];
                }

                uasort($moduleStats, static fn ($a, $b) => $b['avg'] <=> $a['avg']);

                $lines[] = '';
                $lines[] = 'Per-Module Statistics (sorted by average boot time):';
                $lines[] = sprintf('%-20s %10s %10s %10s %10s %10s %8s', 'MODULE', 'AVG', 'MEDIAN', 'MIN', 'MAX', 'IMPACT', 'SAMPLES');
                $lines[] = str_repeat('-', 88);

                foreach ($moduleStats as $module => $stat) {
                    $lines[] = sprintf(
                        '%-20s %9.3fs %9.3fs %9.3fs %9.3fs %9.3fs %8d',
                        substr($module, 0, 18),
                        $stat['avg'],
                        $stat['median'],
                        $stat['min'],
                        $stat['max'],
                        $stat['total_impact'],
                        $stat['samples']
                    );
                }

                $lines[] = '';
                $lines[] = 'Top 5 Slowest Modules (by average):';
                $top5 = array_slice($moduleStats, 0, 5, true);
                $rank = 1;
                foreach ($top5 as $module => $stat) {
                    $pctOfTotal = 0;
                    if (!empty($totalTimes)) {
                        $avgTotal = array_sum($totalTimes) / count($totalTimes);
                        $pctOfTotal = $avgTotal > 0 ? ($stat['avg'] / $avgTotal) * 100 : 0;
                    }
                    $lines[] = sprintf(
                        '  %d. %-25s avg: %.3fs (%.1f%% of total)',
                        $rank++,
                        $module,
                        $stat['avg'],
                        $pctOfTotal
                    );
                }

                $lines[] = '';
                $lines[] = 'Most Unstable Modules (highest variance):';
                $variance = [];
                foreach ($modulesData as $module => $times) {
                    if (count($times) >= 3) {
                        $variance[$module] = $this->calculateStdDev($times);
                    }
                }
                if (!empty($variance)) {
                    arsort($variance);
                    $topVariance = array_slice($variance, 0, 5, true);
                    foreach ($topVariance as $module => $stdDev) {
                        $avg = $moduleStats[$module]['avg'] ?? 0;
                        $cv = $avg > 0 ? ($stdDev / $avg) * 100 : 0;
                        $lines[] = sprintf(
                            '  %-25s std dev: %.3fs (CV: %.1f%%)',
                            $module,
                            $stdDev,
                            $cv
                        );
                    }
                } else {
                    $lines[] = '  Not enough data (need at least 3 samples per module)';
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading statistics: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function generateProvidersPerformanceStatsSection(): string
    {
        $lines = [
            $this->sectionTitle('SERVICE PROVIDERS PERFORMANCE STATISTICS'),
        ];

        try {
            $currentBootTimes = app()->getBootTimes();

            if (!empty($currentBootTimes)) {
                $lines[] = '';
                $lines[] = 'Current Request Boot Times:';
                arsort($currentBootTimes);
                $lines[] = sprintf('%-45s %10s', 'PROVIDER', 'TIME');
                $lines[] = str_repeat('-', 58);

                foreach ($currentBootTimes as $provider => $time) {
                    $shortName = substr(strrchr($provider, '\\') ?: $provider, 1);
                    $lines[] = sprintf('%-45s %9.3fs', substr($shortName, 0, 43), $time);
                }
                $lines[] = str_repeat('-', 58);
                $lines[] = sprintf('%-45s %9.3fs', 'TOTAL', array_sum($currentBootTimes));
            }

            $stats = App::getProviderBootTimesStats();

            if (empty($stats) || empty($stats['samples'])) {
                $lines[] = '';
                $lines[] = 'Historical statistics: No data collected yet.';
                $lines[] = 'Statistics are gathered automatically over time from regular page requests.';

                return implode("\n", $lines);
            }

            $samples = $stats['samples'];
            $providersData = $stats['providers'] ?? [];
            $samplesCount = count($samples);

            $lines[] = '';
            $lines[] = str_repeat('=', 80);
            $lines[] = 'Historical Statistics (based on ' . $samplesCount . ' samples)';
            $lines[] = str_repeat('-', 80);

            if (!empty($stats['last_updated'])) {
                $lines[] = 'Last updated: ' . date('Y-m-d H:i:s', $stats['last_updated']);
                $firstSample = reset($samples);
                if ($firstSample) {
                    $lines[] = 'Data collected since: ' . date('Y-m-d H:i:s', $firstSample['time']);
                }
            }

            $totalTimes = array_column($samples, 'total');
            if (!empty($totalTimes)) {
                $lines[] = '';
                $lines[] = 'Total Providers Boot Time:';
                $lines[] = sprintf('  %-20s: %.3fs', 'Current', end($totalTimes));
                $lines[] = sprintf('  %-20s: %.3fs', 'Average', array_sum($totalTimes) / count($totalTimes));
                $lines[] = sprintf('  %-20s: %.3fs', 'Median', $this->calculateMedian($totalTimes));
                $lines[] = sprintf('  %-20s: %.3fs', 'Min', min($totalTimes));
                $lines[] = sprintf('  %-20s: %.3fs', 'Max', max($totalTimes));
            }

            if (!empty($providersData)) {
                $providerStats = [];
                foreach ($providersData as $provider => $times) {
                    if (empty($times)) {
                        continue;
                    }
                    $providerStats[$provider] = [
                        'avg' => array_sum($times) / count($times),
                        'median' => $this->calculateMedian($times),
                        'min' => min($times),
                        'max' => max($times),
                        'samples' => count($times),
                    ];
                }

                uasort($providerStats, static fn ($a, $b) => $b['avg'] <=> $a['avg']);

                $lines[] = '';
                $lines[] = 'Per-Provider Statistics (sorted by average, top 15):';
                $lines[] = sprintf('%-35s %10s %10s %10s %10s %8s', 'PROVIDER', 'AVG', 'MEDIAN', 'MIN', 'MAX', 'SAMPLES');
                $lines[] = str_repeat('-', 93);

                $top15 = array_slice($providerStats, 0, 15, true);
                foreach ($top15 as $provider => $stat) {
                    $lines[] = sprintf(
                        '%-35s %9.3fs %9.3fs %9.3fs %9.3fs %8d',
                        substr($provider, 0, 33),
                        $stat['avg'],
                        $stat['median'],
                        $stat['min'],
                        $stat['max'],
                        $stat['samples']
                    );
                }

                $lines[] = '';
                $lines[] = 'Top 5 Slowest Providers (by average):';
                $top5 = array_slice($providerStats, 0, 5, true);
                $rank = 1;
                foreach ($top5 as $provider => $stat) {
                    $pctOfTotal = 0;
                    if (!empty($totalTimes)) {
                        $avgTotal = array_sum($totalTimes) / count($totalTimes);
                        $pctOfTotal = $avgTotal > 0 ? ($stat['avg'] / $avgTotal) * 100 : 0;
                    }
                    $lines[] = sprintf(
                        '  %d. %-30s avg: %.3fs (%.1f%% of total)',
                        $rank++,
                        $provider,
                        $stat['avg'],
                        $pctOfTotal
                    );
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading statistics: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

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
                    $routeStats[$routeKey]['db_pct'] = ($routeStats[$routeKey]['avg_db_time'] / $routeStats[$routeKey]['avg_time']) * 100;
                }
            }

            uasort($routeStats, static fn ($a, $b) => $b['avg_time'] <=> $a['avg_time']);

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
                'HITS'
            );
            $lines[] = str_repeat('-', 120);

            $top20 = array_slice($routeStats, 0, 20, true);
            foreach ($top20 as $routeKey => $stat) {
                $lines[] = sprintf(
                    '%-7s %-45s %9.0fms %9.0fms %9.0fms %9.0fms %7.1f%% %10d',
                    $stat['method'],
                    substr($stat['path'], 0, 43),
                    $stat['avg_time'] * 1000,
                    $stat['median_time'] * 1000,
                    $stat['max_time'] * 1000,
                    $stat['avg_db_time'] * 1000,
                    $stat['db_pct'],
                    $stat['hits']
                );
            }

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'MOST DATABASE-HEAVY ROUTES (by DB time percentage)';
            $lines[] = str_repeat('-', 120);

            uasort($routeStats, static fn ($a, $b) => $b['db_pct'] <=> $a['db_pct']);
            $topDbHeavy = array_slice($routeStats, 0, 10, true);

            $lines[] = sprintf(
                '%-7s %-45s %10s %10s %12s %10s',
                'METHOD',
                'ROUTE',
                'DB TIME',
                'DB %',
                'AVG QUERIES',
                'TOTAL TIME'
            );
            $lines[] = str_repeat('-', 100);

            foreach ($topDbHeavy as $routeKey => $stat) {
                $lines[] = sprintf(
                    '%-7s %-45s %9.0fms %9.1f%% %12.1f %9.0fms',
                    $stat['method'],
                    substr($stat['path'], 0, 43),
                    $stat['avg_db_time'] * 1000,
                    $stat['db_pct'],
                    $stat['avg_db_queries'],
                    $stat['avg_time'] * 1000
                );
            }

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'MOST MEMORY-INTENSIVE ROUTES';
            $lines[] = str_repeat('-', 120);

            uasort($routeStats, static fn ($a, $b) => $b['avg_memory'] <=> $a['avg_memory']);
            $topMemory = array_slice($routeStats, 0, 10, true);

            $lines[] = sprintf(
                '%-7s %-45s %12s %12s %10s',
                'METHOD',
                'ROUTE',
                'AVG MEMORY',
                'MAX MEMORY',
                'HITS'
            );
            $lines[] = str_repeat('-', 90);

            foreach ($topMemory as $routeKey => $stat) {
                $lines[] = sprintf(
                    '%-7s %-45s %12s %12s %10d',
                    $stat['method'],
                    substr($stat['path'], 0, 43),
                    $this->formatBytes($stat['avg_memory']),
                    $this->formatBytes($stat['max_memory']),
                    $stat['hits']
                );
            }

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'MOST FREQUENTLY ACCESSED ROUTES';
            $lines[] = str_repeat('-', 120);

            uasort($routeStats, static fn ($a, $b) => $b['hits'] <=> $a['hits']);
            $topHits = array_slice($routeStats, 0, 10, true);

            $lines[] = sprintf(
                '%-7s %-50s %10s %10s %10s',
                'METHOD',
                'ROUTE',
                'HITS',
                'AVG TIME',
                'TOTAL TIME'
            );
            $lines[] = str_repeat('-', 95);

            foreach ($topHits as $routeKey => $stat) {
                $totalTime = $stat['avg_time'] * $stat['hits'];
                $lines[] = sprintf(
                    '%-7s %-50s %10d %9.0fms %9.1fs',
                    $stat['method'],
                    substr($stat['path'], 0, 48),
                    $stat['hits'],
                    $stat['avg_time'] * 1000,
                    $totalTime
                );
            }

            $lines[] = '';
            $lines[] = str_repeat('=', 120);
            $lines[] = 'ROUTES WITH HIGHEST VARIANCE (unstable performance)';
            $lines[] = str_repeat('-', 120);

            $variance = [];
            foreach ($routes as $routeKey => $routeData) {
                $samples = $routeData['samples'] ?? [];
                if (count($samples) >= 5) {
                    $times = array_column($samples, 'time');
                    $stdDev = $this->calculateStdDev($times);
                    $avg = array_sum($times) / count($times);
                    $cv = $avg > 0 ? ($stdDev / $avg) * 100 : 0;
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

            if (!empty($variance)) {
                uasort($variance, static fn ($a, $b) => $b['cv'] <=> $a['cv']);
                $topVariance = array_slice($variance, 0, 10, true);

                $lines[] = sprintf(
                    '%-7s %-45s %12s %10s %10s %8s',
                    'METHOD',
                    'ROUTE',
                    'STD DEV',
                    'CV %',
                    'AVG TIME',
                    'SAMPLES'
                );
                $lines[] = str_repeat('-', 100);

                foreach ($topVariance as $routeKey => $stat) {
                    $lines[] = sprintf(
                        '%-7s %-45s %11.0fms %9.1f%% %9.0fms %8d',
                        $stat['method'],
                        substr($stat['path'], 0, 43),
                        $stat['std_dev'] * 1000,
                        $stat['cv'],
                        $stat['avg'] * 1000,
                        $stat['samples']
                    );
                }
            } else {
                $lines[] = 'Not enough data (need at least 5 samples per route)';
            }

        } catch (Throwable $e) {
            $lines[] = 'Error loading route statistics: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

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

            uasort($processedQueries, static fn ($a, $b) => $b['avg_time'] <=> $a['avg_time']);

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
                    $stat['max_time'] * 1000
                );

                $queryDisplay = $stat['query'];
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
                    $data['total_time'] * 1000
                );
            }

        } catch (Throwable $e) {
            $lines[] = 'Error loading query statistics: ' . $e->getMessage();
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

            uasort($processedWidgets, static fn ($a, $b) => $b['avg_time'] <=> $a['avg_time']);

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
                'SAMPLES'
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
                    $stat['samples']
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
                    $stat['samples']
                );
            }

        } catch (Throwable $e) {
            $lines[] = 'Error loading widget statistics: ' . $e->getMessage();
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

            uasort($processedViews, static fn ($a, $b) => $b['avg_time'] <=> $a['avg_time']);

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
                'SAMPLES'
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
                    $stat['samples']
                );
            }

            $totalTime = array_sum(array_column($processedViews, 'avg_time'));
            $lines[] = '';
            $lines[] = sprintf('Total avg view render time: %.1fms', $totalTime * 1000);

        } catch (Throwable $e) {
            $lines[] = 'Error loading view statistics: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 1) . ' ' . $units[$pow];
    }

    protected function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $middle = (int) floor($count / 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    protected function calculateStdDev(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(static fn ($v) => pow($v - $mean, 2), $values);

        return sqrt(array_sum($squaredDiffs) / count($values));
    }

    protected function generateThemesSection(): string
    {
        $lines = [
            $this->sectionTitle('INSTALLED THEMES'),
        ];

        try {
            $themes = $this->themeManager->getAllThemes();
            $currentTheme = $this->themeManager->getCurrentTheme();

            if (empty($themes)) {
                $lines[] = 'No themes installed.';
            } else {
                foreach ($themes as $theme) {
                    $name = $theme->name ?? 'Unknown';
                    $version = $theme->version ?? 'N/A';
                    $status = $theme->status ?? 'unknown';
                    $isCurrent = ($theme->name === $currentTheme) ? ' [CURRENT]' : '';

                    $statusIcon = match ($status) {
                        'active' => '[ACTIVE]',
                        'disabled' => '[DISABLED]',
                        default => '[' . strtoupper($status) . ']',
                    };

                    $lines[] = sprintf('  %s %s (v%s)%s', $statusIcon, $name, $version, $isCurrent);
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading themes: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function generateDatabaseSection(): string
    {
        $lines = [
            $this->sectionTitle('DATABASE INFORMATION'),
        ];

        try {
            $dbConfig = config('database.connections.default');
            $driver = $dbConfig->driver ?? 'N/A';
            $driverName = str_replace(['Driver', 'Cycle\\Database\\Driver\\'], '', $driver);

            $lines[] = $this->formatKeyValue('Driver', $driverName);
            $lines[] = $this->formatKeyValue('Host', $dbConfig->connection ?? 'N/A');
            $lines[] = $this->formatKeyValue('Database', $dbConfig->database ?? 'N/A');

            $dbal = app(DatabaseManager::class)->getDbal();
            $database = $dbal->database();
            if ($database) {
                $tables = $database->getTables();
                $lines[] = $this->formatKeyValue('Tables Count', (string) count($tables));
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading database info: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function generateCacheSection(): string
    {
        $lines = [
            $this->sectionTitle('CACHE INFORMATION'),
        ];

        try {
            $cacheConfig = config('cache');
            $driver = $cacheConfig['default'] ?? 'file';

            $lines[] = $this->formatKeyValue('Cache Driver', $driver);

            if ($driver === 'file') {
                $cachePath = storage_path('app/cache');
                if (is_dir($cachePath)) {
                    $lines[] = $this->formatKeyValue('Cache Path', $cachePath);
                    $lines[] = $this->formatKeyValue('Cache Writable', is_writable($cachePath) ? 'Yes' : 'No');
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading cache info: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function generateComposerSection(): string
    {
        $lines = [
            $this->sectionTitle('COMPOSER PACKAGES'),
        ];

        try {
            $composerLock = path('composer.lock');
            if (file_exists($composerLock)) {
                $lockData = json_decode(file_get_contents($composerLock), true);

                if (!empty($lockData['packages'])) {
                    $lines[] = '';
                    $lines[] = 'Installed Packages (' . count($lockData['packages']) . '):';
                    $lines[] = '';

                    foreach ($lockData['packages'] as $package) {
                        $name = $package['name'] ?? 'unknown';
                        $version = $package['version'] ?? 'N/A';
                        $lines[] = sprintf('  %-40s %s', $name, $version);
                    }
                }

                if (!empty($lockData['packages-dev'])) {
                    $lines[] = '';
                    $lines[] = 'Dev Packages (' . count($lockData['packages-dev']) . '):';
                    $lines[] = '';

                    foreach ($lockData['packages-dev'] as $package) {
                        $name = $package['name'] ?? 'unknown';
                        $version = $package['version'] ?? 'N/A';
                        $lines[] = sprintf('  %-40s %s', $name, $version);
                    }
                }
            } else {
                $lines[] = 'composer.lock not found';
            }
        } catch (Throwable $e) {
            $lines[] = 'Error reading composer.lock: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function generateDirectoriesSection(): string
    {
        $lines = [
            $this->sectionTitle('DIRECTORY PERMISSIONS & SIZES'),
        ];

        $directories = [
            'storage' => storage_path(),
            'storage/logs' => storage_path('logs'),
            'storage/app' => storage_path('app'),
            'storage/app/cache' => storage_path('app/cache'),
            'storage/app/temp' => storage_path('app/temp'),
            'public/assets' => path('public/assets'),
            'app/Modules' => path('app/Modules'),
            'app/Themes' => path('app/Themes'),
            'config' => path('config'),
            'config-dev' => path('config-dev'),
        ];

        foreach ($directories as $name => $dirPath) {
            if (is_dir($dirPath)) {
                $writable = is_writable($dirPath) ? 'writable' : 'not writable';
                $readable = is_readable($dirPath) ? 'readable' : 'not readable';
                $size = $this->getDirectorySize($dirPath);
                $perms = substr(sprintf('%o', fileperms($dirPath)), -4);

                $lines[] = sprintf(
                    '  %-25s [%s] %s, %s, %s',
                    $name,
                    $perms,
                    $readable,
                    $writable,
                    AboutSystemHelper::formatBytes($size)
                );
            } else {
                $lines[] = sprintf('  %-25s [NOT EXISTS]', $name);
            }
        }

        return implode("\n", $lines);
    }

    protected function generateConfigSection(): string
    {
        $lines = [
            $this->sectionTitle('CONFIGURATION (sanitized)'),
        ];

        $configKeys = [
            'app.name',
            'app.url',
            'app.env',
            'app.debug',
            'app.timezone',
            'app.locale',
            'app.steam_api',
            'app.mode',
            'app.tips',
            'cache.default',
            'database.default',
            'logging.default',
            'logging.level',
            'mail.driver',
            'mail.host',
            'mail.port',
            'view.cache',
            'view.debug',
            'auth.remember_me',
            'auth.csrf_enabled',
            'auth.security_token',
        ];

        foreach ($configKeys as $key) {
            try {
                $value = config($key);

                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    $value = json_encode($value);
                } elseif (is_null($value)) {
                    $value = 'null';
                } elseif (is_object($value)) {
                    $value = $value::class;
                }

                if ($this->isSensitiveKey($key)) {
                    $value = $value ? '***SET***' : '***NOT SET***';
                }

                $lines[] = $this->formatKeyValue($key, (string) $value);
            } catch (Throwable $e) {
                $lines[] = $this->formatKeyValue($key, 'ERROR: ' . $e->getMessage());
            }
        }

        return implode("\n", $lines);
    }

    protected function generateSessionSection(): string
    {
        $lines = [
            $this->sectionTitle('SESSION CONFIGURATION'),
        ];

        try {
            $lines[] = $this->formatKeyValue('Session Handler', ini_get('session.save_handler'));
            $lines[] = $this->formatKeyValue('Session Path', ini_get('session.save_path') ?: 'default');
            $lines[] = $this->formatKeyValue('Session Name', ini_get('session.name'));
            $lines[] = $this->formatKeyValue('Session Lifetime', ini_get('session.gc_maxlifetime') . 's');
            $lines[] = $this->formatKeyValue('Cookie Lifetime', ini_get('session.cookie_lifetime') . 's');
            $lines[] = $this->formatKeyValue('Cookie Secure', ini_get('session.cookie_secure') ? 'Yes' : 'No');
            $lines[] = $this->formatKeyValue('Cookie HttpOnly', ini_get('session.cookie_httponly') ? 'Yes' : 'No');
            $lines[] = $this->formatKeyValue('Cookie SameSite', ini_get('session.cookie_samesite') ?: 'Not set');
            $lines[] = $this->formatKeyValue('Use Strict Mode', ini_get('session.use_strict_mode') ? 'Yes' : 'No');

            if (session_status() === PHP_SESSION_ACTIVE) {
                $lines[] = '';
                $lines[] = $this->formatKeyValue('Current Session ID', substr(session_id(), 0, 8) . '...');
            }
        } catch (Throwable $e) {
            $lines[] = 'Error getting session info: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function generateRequestSection(): string
    {
        $lines = [
            $this->sectionTitle('CURRENT REQUEST INFO'),
        ];

        try {
            $request = request();

            $lines[] = $this->formatKeyValue('Method', $request->getMethod());
            $lines[] = $this->formatKeyValue('URI', $request->getRequestUri());
            $lines[] = $this->formatKeyValue('Host', $request->getHost());
            $lines[] = $this->formatKeyValue('Scheme', $request->getScheme());
            $lines[] = $this->formatKeyValue('Is Secure', $request->isSecure() ? 'Yes' : 'No');
            $lines[] = $this->formatKeyValue('Client IP', $request->getClientIp() ?? 'N/A');
            $lines[] = $this->formatKeyValue('User Agent', substr($request->headers->get('User-Agent', 'N/A'), 0, 80));

            $lines[] = '';
            $lines[] = 'Request Headers:';
            foreach ($request->headers->all() as $name => $values) {
                $value = implode(', ', $values);
                if ($this->isSensitiveKey($name)) {
                    $value = '***';
                }
                $lines[] = sprintf('  %-25s: %s', $name, substr($value, 0, 100));
            }

            $lines[] = '';
            $lines[] = 'Server Variables (selected):';
            $serverVars = ['REQUEST_TIME', 'REQUEST_TIME_FLOAT', 'REMOTE_ADDR', 'REMOTE_PORT', 'SERVER_PROTOCOL', 'GATEWAY_INTERFACE', 'HTTPS'];
            foreach ($serverVars as $var) {
                if (isset($_SERVER[$var])) {
                    $lines[] = sprintf('  %-25s: %s', $var, $_SERVER[$var]);
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error getting request info: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function generateFullLogsSection(): string
    {
        $lines = [
            $this->sectionTitle('FULL LOG FILES'),
        ];

        try {
            $logFiles = $this->logViewerService->getLogFiles();

            if (empty($logFiles)) {
                $lines[] = 'No log files found.';

                return implode("\n", $lines);
            }

            foreach ($logFiles as $fileName => $fileInfo) {
                $lines[] = '';
                $lines[] = str_repeat('-', 80);
                $lines[] = 'LOG FILE: ' . $fileName;
                $lines[] = 'Size: ' . ($fileInfo['size'] ?? 'N/A');
                $lines[] = 'Modified: ' . ($fileInfo['modified'] ?? 'N/A');
                $lines[] = str_repeat('-', 80);
                $lines[] = '';

                $logPath = path('storage/logs/' . $fileName);
                if (file_exists($logPath)) {
                    $content = file_get_contents($logPath);

                    $maxSize = 200 * 1024;
                    if (strlen($content) > $maxSize) {
                        $content = "... [TRUNCATED - showing last 200KB] ...\n\n" . substr($content, -$maxSize);
                    }

                    $content = $this->sanitizeLogContent($content);
                    $lines[] = $content;
                } else {
                    $lines[] = 'File not found: ' . $logPath;
                }

                $lines[] = '';
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading logs: ' . $e->getMessage();
        }

        return implode("\n", $lines);
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $count = 0;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                    $count++;
                    if ($count > 10000) {
                        break;
                    }
                }
            }
        } catch (Throwable $e) {
        }

        return $size;
    }

    protected function isSensitiveKey(string $key): bool
    {
        $sensitivePatterns = ['key', 'secret', 'password', 'token', 'api', 'steam_api'];

        foreach ($sensitivePatterns as $pattern) {
            if (stripos($key, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function sanitizeLogContent(string $content): string
    {
        $content = preg_replace('/password["\']?\s*[:=]\s*["\']?[^"\'\s,}\n]+/i', 'password=***', $content);
        $content = preg_replace('/token["\']?\s*[:=]\s*["\']?[^"\'\s,}\n]+/i', 'token=***', $content);
        $content = preg_replace('/(\?|&)accessKey=([^&\s\n]+)/i', '$1accessKey=***', $content);
        $content = preg_replace('/api[_-]?key["\']?\s*[:=]\s*["\']?[^"\'\s,}\n]+/i', 'api_key=***', $content);
        $content = preg_replace('/secret["\']?\s*[:=]\s*["\']?[^"\'\s,}\n]+/i', 'secret=***', $content);
        $content = preg_replace('/Authorization:\s*Bearer\s+[^\s\n]+/i', 'Authorization: Bearer ***', $content);

        return $content;
    }

    protected function sectionTitle(string $title): string
    {
        return "\n" . str_repeat('=', 80) . "\n" . $title . "\n" . str_repeat('-', 80);
    }

    protected function formatKeyValue(string $key, string $value): string
    {
        return sprintf('%-25s: %s', $key, $value);
    }

    protected function getErrorReportingString(): string
    {
        $level = error_reporting();
        $flags = [];

        if ($level & E_ERROR) {
            $flags[] = 'E_ERROR';
        }
        if ($level & E_WARNING) {
            $flags[] = 'E_WARNING';
        }
        if ($level & E_PARSE) {
            $flags[] = 'E_PARSE';
        }
        if ($level & E_NOTICE) {
            $flags[] = 'E_NOTICE';
        }
        if ($level & E_STRICT) {
            $flags[] = 'E_STRICT';
        }
        if ($level & E_DEPRECATED) {
            $flags[] = 'E_DEPRECATED';
        }

        if ($level === E_ALL) {
            return 'E_ALL';
        }

        return implode(' | ', array_slice($flags, 0, 4)) . (count($flags) > 4 ? '...' : '');
    }
}
