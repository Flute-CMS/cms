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
     * Search query for filtering logs
     * 
     * @var string|null
     */
    public $search = '';

    /**
     * Number of records to display
     * 
     * @var int
     */
    public $limit = 50;

    /**
     * Current page for pagination
     * 
     * @var int
     */
    public $page = 1;

    /**
     * Auto-refresh enabled
     * 
     * @var bool
     */
    public $autoRefresh = false;

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
        $totalEntries = 0;

        if ($this->logger) {
            // Get more entries for better filtering, but with reasonable limit
            $rawLogContent = $this->logService->getLogContent($this->logger, $this->limit * 3);

            // Apply level filter
            if (!empty($this->level)) {
                $rawLogContent = array_filter($rawLogContent, function ($entry) {
                    return $entry['level'] === $this->level;
                });
            }

            // Apply search filter
            if (!empty($this->search)) {
                $searchTerm = strtolower($this->search);
                $rawLogContent = array_filter($rawLogContent, function ($entry) use ($searchTerm) {
                    $searchableText = strtolower(
                        $entry['message'] . ' ' . 
                        $entry['channel'] . ' ' . 
                        $entry['level'] . ' ' .
                        ($entry['file_info']['file_name'] ?? '') . ' ' .
                        ($entry['file_info']['relative_path'] ?? '')
                    );
                    return strpos($searchableText, $searchTerm) !== false;
                });
            }

            $totalEntries = count($rawLogContent);
            
            $offset = ($this->page - 1) * $this->limit;
            $logContent = array_slice($rawLogContent, $offset, $this->limit);

            foreach ($logContent as &$entry) {
                if (empty($entry['code_context']) && !empty($entry['file_info']['file_path']) && !empty($entry['file_info']['line_number'])) {
                    $entry['code_context'] = $this->logService->getFileContext(
                        $entry['file_info']['file_path'], 
                        $entry['file_info']['line_number'],
                        20
                    );
                }
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
            'searchQuery' => $this->search,
            'currentPage' => $this->page,
            'totalEntries' => $totalEntries,
            'hasMorePages' => $totalEntries > ($this->page * $this->limit),
            'autoRefreshEnabled' => $this->autoRefresh,
            'limit' => $this->limit,
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
        $commands = [
            Button::make(__('admin-logs.refresh'))
                ->icon('ph.bold.arrow-clockwise-bold')
                ->method('render'),
        ];

        return $commands;
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
        $this->page = 1;
    }

    /**
     * Search logs by query
     * 
     * @param string $query
     * @return void
     */
    public function searchLogs(string $query): void
    {
        $this->search = $query;
        $this->page = 1;
    }

    /**
     * Load more entries (pagination)
     * 
     * @return void
     */
    public function loadMore(): void
    {
        $this->page++;
    }

    /**
     * Reset pagination to first page
     * 
     * @return void
     */
    public function resetPagination(): void
    {
        $this->page = 1;
    }

    /**
     * Go to next page
     * 
     * @return void
     */
    public function nextPage(): void
    {
        $this->page++;
    }

    /**
     * Go to previous page
     * 
     * @return void
     */
    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    /**
     * Toggle auto-refresh
     * 
     * @return void
     */
    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->flashMessage(__('admin-logs.auto_refresh_enabled'), 'success');
        } else {
            $this->flashMessage(__('admin-logs.auto_refresh_disabled'), 'info');
        }
    }

    /**
     * Handle log clearing
     * 
     * @return void
     */
    public function handleClearLog(): void
    {
        if (!$this->logger) {
            $this->flashMessage(__('admin-logs.no_log_selected'), 'error');
            return;
        }

        if ($this->logService->clearLog($this->logger)) {
            $this->flashMessage(__('admin-logs.cleared_success'), 'success');
            $this->resetPagination();
        } else {
            $this->flashMessage(__('admin-logs.cleared_error'), 'error');
        }
    }

    /**
     * Get log statistics
     * 
     * @return array
     */
    public function getLogStats(): array
    {
        if (!$this->logger) {
            return [];
        }

        $logContent = $this->logService->getLogContent($this->logger, 1000);
        $stats = [
            'total' => count($logContent),
            'levels' => [],
            'files_with_errors' => []
        ];

        foreach ($logContent as $entry) {
            $level = $entry['level'];
            if (!isset($stats['levels'][$level])) {
                $stats['levels'][$level] = 0;
            }
            $stats['levels'][$level]++;

            if (in_array($level, ['error', 'critical', 'alert', 'emergency']) && !empty($entry['file_info']['file_name'])) {
                $fileName = $entry['file_info']['file_name'];
                if (!isset($stats['files_with_errors'][$fileName])) {
                    $stats['files_with_errors'][$fileName] = 0;
                }
                $stats['files_with_errors'][$fileName]++;
            }
        }

        return $stats;
    }
}
