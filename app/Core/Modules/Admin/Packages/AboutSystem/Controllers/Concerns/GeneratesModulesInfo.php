<?php

namespace Flute\Admin\Packages\AboutSystem\Controllers\Concerns;

use Flute\Core\App;
use Flute\Core\ModulesManager\ModuleRegister;
use Throwable;

trait GeneratesModulesInfo
{
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
                $lines[] = sprintf(
                    '%-15s %-25s %-10s %-12s %-10s %s',
                    'STATUS',
                    'NAME',
                    'VERSION',
                    'KEY',
                    'BOOT TIME',
                    'DEPENDENCIES',
                );
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
                            $deps[] = $dep . ':' . ( is_scalar($ver) ? $ver : json_encode($ver) );
                        }
                    }
                    $depsStr = !empty($deps) ? implode(', ', $deps) : '-';

                    $lines[] = sprintf(
                        '%-15s %-25s %-10s %-12s %-10s %s',
                        $statusIcon,
                        substr($name, 0, 23),
                        $version,
                        $key,
                        $bootTimeStr,
                        $depsStr,
                    );
                }

                $lines[] = str_repeat('-', 110);
                $lines[] = '';
                $lines[] = 'Total modules: ' . $modules->count();
                $lines[] = 'Active: ' . $modules->filter(static fn($m) => ( $m->status ?? '' ) === 'active')->count();
                $lines[] =
                    'Disabled: ' . $modules->filter(static fn($m) => ( $m->status ?? '' ) === 'disabled')->count();
                $lines[] = sprintf('Current request total boot time: %.3fs', $totalBootTime);
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading modules: ' . $this->sanitizeErrorMessage($e->getMessage());
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

                uasort($moduleStats, static fn($a, $b) => $b['avg'] <=> $a['avg']);

                $lines[] = '';
                $lines[] = 'Per-Module Statistics (sorted by average boot time):';
                $lines[] = sprintf(
                    '%-20s %10s %10s %10s %10s %10s %8s',
                    'MODULE',
                    'AVG',
                    'MEDIAN',
                    'MIN',
                    'MAX',
                    'IMPACT',
                    'SAMPLES',
                );
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
                        $stat['samples'],
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
                        $pctOfTotal = $avgTotal > 0 ? ( $stat['avg'] / $avgTotal ) * 100 : 0;
                    }
                    $lines[] = sprintf(
                        '  %d. %-25s avg: %.3fs (%.1f%% of total)',
                        $rank++,
                        $module,
                        $stat['avg'],
                        $pctOfTotal,
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
                        $cv = $avg > 0 ? ( $stdDev / $avg ) * 100 : 0;
                        $lines[] = sprintf('  %-25s std dev: %.3fs (CV: %.1f%%)', $module, $stdDev, $cv);
                    }
                } else {
                    $lines[] = '  Not enough data (need at least 3 samples per module)';
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading statistics: ' . $this->sanitizeErrorMessage($e->getMessage());
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

                uasort($providerStats, static fn($a, $b) => $b['avg'] <=> $a['avg']);

                $lines[] = '';
                $lines[] = 'Per-Provider Statistics (sorted by average, top 15):';
                $lines[] = sprintf(
                    '%-35s %10s %10s %10s %10s %8s',
                    'PROVIDER',
                    'AVG',
                    'MEDIAN',
                    'MIN',
                    'MAX',
                    'SAMPLES',
                );
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
                        $stat['samples'],
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
                        $pctOfTotal = $avgTotal > 0 ? ( $stat['avg'] / $avgTotal ) * 100 : 0;
                    }
                    $lines[] = sprintf(
                        '  %d. %-30s avg: %.3fs (%.1f%% of total)',
                        $rank++,
                        $provider,
                        $stat['avg'],
                        $pctOfTotal,
                    );
                }
            }
        } catch (Throwable $e) {
            $lines[] = 'Error loading statistics: ' . $this->sanitizeErrorMessage($e->getMessage());
        }

        return implode("\n", $lines);
    }
}
