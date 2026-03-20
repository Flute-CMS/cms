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
}
