<?php

namespace Flute\Admin\Packages\Logs\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Packages\Logs\Services\LogViewerService;

class LogsScreen extends Screen
{
    /**
     * @var LogViewerService
     */
    protected LogViewerService $logService;

    /**
     * Screen name
     * 
     * @var string
     */
    public $name = 'admin-logs.title';

    /**
     * Screen description
     * 
     * @var string
     */
    public $description = 'admin-logs.description';

    /**
     * Selected log file
     * 
     * @var string|null
     */
    public $logger;

    /**
     * Selected log level for filtering
     * 
     * @var string|null
     */
    public $level = '';

    /**
     * Number of records to display
     * 
     * @var int
     */
    public $limit = 500;

    /**
     * Screen initialization
     * 
     * @return void
     */
    public function mount(): void
    {
        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-logs.title'));

        $this->logService = app(LogViewerService::class);
    }

    /**
     * Get screen data
     * 
     * @return array
     */
    public function query(): array
    {
        $logFiles = $this->logService->getLogFiles();

        if (!$this->logger) {
            $this->logger = array_key_first($logFiles);
        }

        $logContent = [];

        if ($this->logger) {
            $logContent = $this->logService->getLogContent($this->logger, $this->limit);

            if (!empty($this->level)) {
                $logContent = array_filter($logContent, function ($entry) {
                    return $entry['level'] === $this->level;
                });
            }
        }

        $levels = [
            '' => __('admin-logs.all_levels'),
            'debug' => __('admin-logs.level_labels.debug'),
            'info' => __('admin-logs.level_labels.info'),
            'notice' => __('admin-logs.level_labels.notice'),
            'warning' => __('admin-logs.level_labels.warning'),
            'error' => __('admin-logs.level_labels.error'),
            'critical' => __('admin-logs.level_labels.critical'),
            'alert' => __('admin-logs.level_labels.alert'),
            'emergency' => __('admin-logs.level_labels.emergency'),
        ];

        return [
            'loggers' => $logFiles,
            'selectedLogger' => $this->logger,
            'logContent' => $logContent,
            'levels' => $levels,
            'selectedLevel' => $this->level,
        ];
    }

    /**
     * Get screen layouts
     * 
     * @return array
     */
    public function layout(): array
    {
        return [
            LayoutFactory::view('admin-logs::layouts.logs-layout', $this->query()),
        ];
    }

    /**
     * Get screen commands
     * 
     * @return array
     */
    public function commandBar(): array
    {
        return [
            Button::make(__('admin-logs.refresh'))
                ->icon('ph.bold.arrows-clockwise-bold')
                ->method('render'),
                
            // Button::make(__('admin-logs.download'))
            //     ->icon('ph.bold.download-bold')
            //     ->method('downloadLog'),
        ];
    }

    /**
     * Filter logs by level
     * 
     * @param string $level
     * @return void
     */
    public function filterByLevel(string $level): void
    {
        $this->level = $level;
    }

    /**
     * Handle log clearing
     * 
     * @return void
     */
    public function handleClearLog(): void
    {
        if ($this->logService->clearLog($this->logger)) {
            $this->flashMessage(__('admin-logs.cleared_success'), 'success');
        } else {
            $this->flashMessage(__('admin-logs.cleared_error'), 'error');
        }
    }
}
