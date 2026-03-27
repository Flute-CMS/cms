<?php

namespace Flute\Admin\Packages\Dashboard\Services;

use Flute\Core\Update\Services\UpdateService;
use Throwable;

class AttentionService
{
    protected array $items = [];

    public function getItems(): array
    {
        if (empty($this->items)) {
            $this->buildItems();
        }

        return $this->items;
    }

    public function hasItems(): bool
    {
        return count($this->getItems()) > 0;
    }

    protected function buildItems(): void
    {
        $this->items = [];

        $this->checkUpdates();
        $this->checkDebugMode();
        $this->checkCron();
        $this->checkPerformanceSettings();
        $this->checkDirectoryPermissions();
    }

    protected function checkUpdates(): void
    {
        try {
            $updateService = app(UpdateService::class);
            $updates = $updateService->getAvailableUpdates();

            $count = 0;

            if (!empty($updates['cms'])) {
                $count++;
            }

            $count += count($updates['modules'] ?? []);
            $count += count($updates['themes'] ?? []);

            if ($count > 0) {
                $this->items[] = [
                    'key' => 'updates',
                    'type' => 'warning',
                    'icon' => 'ph.bold.arrow-circle-up-bold',
                    'count' => $count,
                    'url' => url('/admin/update'),
                ];
            }
        } catch (Throwable $e) {
            // Ignore update check failures
        }
    }

    protected function checkDebugMode(): void
    {
        if (config('app.debug') && !config('app.development_mode')) {
            $this->items[] = [
                'key' => 'debug',
                'type' => 'error',
                'icon' => 'ph.bold.bug-bold',
                'url' => url('/admin/main-settings'),
            ];
        }
    }

    protected function checkCron(): void
    {
        if (!config('app.cron_mode')) {
            return;
        }

        $logDir = BASE_PATH . '/storage/logs';
        $logFiles = glob($logDir . '/cron-*.log');

        if (empty($logFiles) || ( time() - max(array_map('filemtime', $logFiles)) ) > 3600) {
            $this->items[] = [
                'key' => 'cron',
                'type' => 'warning',
                'icon' => 'ph.bold.clock-countdown-bold',
                'url' => url('/admin/servers'),
            ];
        }
    }

    protected function checkDirectoryPermissions(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $dirs = [
            BASE_PATH . 'storage',
            BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'logs',
            BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache',
            BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views',
            BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'app',
        ];

        $problems = 0;

        foreach ($dirs as $dir) {
            if (is_dir($dir) && !is_writable($dir)) {
                $problems++;
            }
        }

        if ($problems > 0) {
            $this->items[] = [
                'key' => 'permissions',
                'type' => 'error',
                'icon' => 'ph.bold.folder-lock-bold',
                'count' => $problems,
                'url' => url('/admin/about-system'),
            ];
        }
    }

    protected function checkPerformanceSettings(): void
    {
        if (config('app.development_mode')) {
            return;
        }

        $issues = [];

        if (!extension_loaded('Zend OPcache') || !filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
            $issues[] = 'opcache';
        }

        if (extension_loaded('Zend OPcache') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
            if (filter_var(ini_get('opcache.validate_timestamps'), FILTER_VALIDATE_BOOLEAN)) {
                $issues[] = 'opcache_validate';
            }

            $jitBuffer = (int) ini_get('opcache.jit_buffer_size');
            if ($jitBuffer < 1) {
                $issues[] = 'jit';
            }
        }

        if (!config('app.is_performance')) {
            $issues[] = 'performance_mode';
        }

        if (!empty($issues)) {
            $this->items[] = [
                'key' => 'performance',
                'type' => 'info',
                'icon' => 'ph.bold.lightning-bold',
                'url' => url('/admin/about-system'),
                'count' => count($issues),
                'details' => $issues,
            ];
        }
    }
}
