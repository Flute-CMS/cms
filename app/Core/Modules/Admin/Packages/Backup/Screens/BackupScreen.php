<?php

namespace Flute\Admin\Packages\Backup\Screens;

use Exception;
use Flute\Admin\Packages\Backup\Services\BackupService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;

class BackupScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.system';

    public array $backups = [];

    public array $metrics = [];

    public string $moduleKey = '';

    public string $themeKey = '';

    protected BackupService $backupService;

    public function mount(): void
    {
        $this->backupService = app(BackupService::class);
        $this->name = __('admin-backup.title');
        $this->description = __('admin-backup.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-backup.title'));

        $this->loadBackups();
    }

    public function commandBar(): array
    {
        return [
            DropDown::make()
                ->icon('ph.bold.plus-bold')
                ->type(Color::PRIMARY)
                ->list([
                    DropDownItem::make(__('admin-backup.actions.backup_module'))
                        ->icon('ph.regular.folder')
                        ->class('w-100')
                        ->type(Color::OUTLINE_PRIMARY)
                        ->modal('backupModuleModal'),

                    DropDownItem::make(__('admin-backup.actions.backup_theme'))
                        ->icon('ph.regular.palette')
                        ->class('w-100')
                        ->type(Color::OUTLINE_PRIMARY)
                        ->modal('backupThemeModal'),

                    DropDownItem::make(__('admin-backup.actions.backup_all_modules'))
                        ->icon('ph.regular.folders')
                        ->confirm(__('admin-backup.confirmations.backup_all_modules'))
                        ->class('w-100')
                        ->type(Color::OUTLINE_SUCCESS)
                        ->method('backupAllModules'),

                    DropDownItem::make(__('admin-backup.actions.backup_all_themes'))
                        ->icon('ph.regular.paint-bucket')
                        ->confirm(__('admin-backup.confirmations.backup_all_themes'))
                        ->class('w-100')
                        ->type(Color::OUTLINE_WARNING)
                        ->method('backupAllThemes'),

                    DropDownItem::make(__('admin-backup.actions.backup_cms'))
                        ->icon('ph.regular.cube')
                        ->confirm(__('admin-backup.confirmations.backup_cms'))
                        ->class('w-100')
                        ->type(Color::OUTLINE_DANGER)
                        ->method('backupCms'),

                    DropDownItem::make(__('admin-backup.actions.backup_full'))
                        ->icon('ph.regular.archive')
                        ->confirm(__('admin-backup.confirmations.backup_full'))
                        ->class('w-100')
                        ->type(Color::OUTLINE_ACCENT)
                        ->method('backupFull'),
                ]),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::metrics([
                __('admin-backup.metrics.total_backups') => 'metrics.total_backups',
                __('admin-backup.metrics.total_size') => 'metrics.total_size',
            ])->setIcons([
                __('admin-backup.metrics.total_backups') => 'archive',
                __('admin-backup.metrics.total_size') => 'hard-drives',
            ]),

            LayoutFactory::table('backups', [
                TD::selection('filename'),

                TD::make('type', __('admin-backup.table.type'))
                    ->render(static function (array $backup) {
                        $badges = [
                            'module' => ['class' => 'primary', 'label' => __('admin-backup.types.module')],
                            'theme' => ['class' => 'info', 'label' => __('admin-backup.types.theme')],
                            'modules' => ['class' => 'success', 'label' => __('admin-backup.types.modules')],
                            'themes' => ['class' => 'warning', 'label' => __('admin-backup.types.themes')],
                            'cms' => ['class' => 'error', 'label' => __('admin-backup.types.cms')],
                            'full' => ['class' => 'accent', 'label' => __('admin-backup.types.full')],
                            'vendor' => ['class' => 'accent', 'label' => __('admin-backup.types.vendor')],
                            'composer' => ['class' => 'warning', 'label' => __('admin-backup.types.composer')],
                        ];
                        $badge = $badges[$backup['type']] ?? ['class' => 'secondary', 'label' => $backup['type']];
                        $icon = $backup['is_directory'] ? '<x-icon class="me-1" path="ph.regular.folder" />' : '';

                        return $icon . '<span class="badge ' . $badge['class'] . '">' . $badge['label'] . '</span>';
                    })
                    ->minWidth('140px'),

                TD::make('name', __('admin-backup.table.name'))
                    ->render(static fn (array $backup) => '<strong>' . e($backup['name']) . '</strong>')
                    ->minWidth('150px'),

                TD::make('filename', __('admin-backup.table.filename'))
                    ->render(static fn (array $backup) => '<code class="text-muted">' . e($backup['filename']) . '</code>')
                    ->minWidth('300px'),

                TD::make('size_formatted', __('admin-backup.table.size'))
                    ->minWidth('100px'),

                TD::make('date', __('admin-backup.table.date'))
                    ->render(static fn (array $backup) => date('d.m.Y H:i:s', $backup['date']))
                    ->minWidth('160px'),

                TD::make('actions', __('admin-backup.table.actions'))
                    ->width('180px')
                    ->alignCenter()
                    ->render(static function (array $backup) {
                        $actions = [];

                        // Restore button
                        $actions[] = DropDownItem::make(__('admin-backup.actions.restore'))
                            ->icon('ph.bold.arrow-counter-clockwise-bold')
                            ->confirm(__('admin-backup.confirmations.restore'))
                            ->method('restoreBackup', [
                                'filename' => $backup['filename'],
                                'is_directory' => $backup['is_directory'] ? '1' : '0',
                            ])
                            ->type(Color::OUTLINE_SUCCESS)
                            ->size('small')
                            ->fullWidth();

                        // Download button (only for ZIP files)
                        if (!$backup['is_directory']) {
                            $actions[] = DropDownItem::make(__('admin-backup.actions.download'))
                                ->icon('ph.bold.download-simple-bold')
                                ->redirect(url('/admin/backups/download?filename=' . urlencode($backup['filename'])))
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth();
                        }

                        // Delete button
                        $actions[] = DropDownItem::make(__('admin-backup.actions.delete'))
                            ->icon('ph.bold.trash-bold')
                            ->confirm(__('admin-backup.confirmations.delete'))
                            ->method('deleteBackup', [
                                'filename' => $backup['filename'],
                                'is_directory' => $backup['is_directory'] ? '1' : '0',
                            ])
                            ->type(Color::OUTLINE_DANGER)
                            ->size('small')
                            ->fullWidth();

                        return DropDown::make()
                            ->icon('ph.regular.dots-three-outline-vertical')
                            ->list($actions);
                    }),
            ])
                ->searchable(['filename', 'name', 'type'])
                ->commands([
                    Button::make(__('admin-backup.actions.refresh'))
                        ->icon('ph.regular.arrows-counter-clockwise')
                        ->type(Color::OUTLINE_PRIMARY)
                        ->size('small')
                        ->method('refreshBackups'),
                ])
                ->bulkActions([
                    Button::make(__('admin.bulk.delete_selected'))
                        ->icon('ph.bold.trash-bold')
                        ->type(Color::OUTLINE_DANGER)
                        ->confirm(__('admin.confirms.delete_selected'))
                        ->method('bulkDeleteBackups'),
                ]),
        ];
    }

    public function backupModuleModal(Repository $parameters)
    {
        $modules = $this->backupService->getAvailableModules();
        $options = [];

        foreach ($modules as $module) {
            $options[$module->key] = __($module->name) . ' (' . $module->key . ')';
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Select::make('moduleKey')
                    ->options($options)
                    ->searchable()
                    ->required()
            )->label(__('admin-backup.modal.select_module')),
        ])
            ->title(__('admin-backup.modal.backup_module_title'))
            ->applyButton(__('admin-backup.actions.create_backup'))
            ->method('backupModule');
    }

    public function backupThemeModal(Repository $parameters)
    {
        $themes = $this->backupService->getAvailableThemes();
        $options = [];

        foreach ($themes as $theme) {
            $options[$theme['key']] = $theme['name'] . ' (' . $theme['key'] . ')';
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Select::make('themeKey')
                    ->options($options)
                    ->searchable()
                    ->required()
            )->label(__('admin-backup.modal.select_theme')),
        ])
            ->title(__('admin-backup.modal.backup_theme_title'))
            ->applyButton(__('admin-backup.actions.create_backup'))
            ->method('backupTheme');
    }

    public function backupModule(): void
    {
        try {
            $filename = $this->backupService->backupModule($this->moduleKey);
            $this->flashMessage(__('admin-backup.messages.backup_created', ['filename' => $filename]), 'success');
            $this->closeModal();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-backup.messages.backup_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadBackups();
    }

    public function backupTheme(): void
    {
        try {
            $filename = $this->backupService->backupTheme($this->themeKey);
            $this->flashMessage(__('admin-backup.messages.backup_created', ['filename' => $filename]), 'success');
            $this->closeModal();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-backup.messages.backup_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadBackups();
    }

    public function backupAllModules(): void
    {
        try {
            $filename = $this->backupService->backupAllModules();
            $this->flashMessage(__('admin-backup.messages.backup_created', ['filename' => $filename]), 'success');
        } catch (Exception $e) {
            $this->flashMessage(__('admin-backup.messages.backup_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadBackups();
    }

    public function backupAllThemes(): void
    {
        try {
            $filename = $this->backupService->backupAllThemes();
            $this->flashMessage(__('admin-backup.messages.backup_created', ['filename' => $filename]), 'success');
        } catch (Exception $e) {
            $this->flashMessage(__('admin-backup.messages.backup_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadBackups();
    }

    public function backupCms(): void
    {
        try {
            $filename = $this->backupService->backupCms();
            $this->flashMessage(__('admin-backup.messages.backup_created', ['filename' => $filename]), 'success');
        } catch (Exception $e) {
            $this->flashMessage(__('admin-backup.messages.backup_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadBackups();
    }

    public function backupFull(): void
    {
        try {
            $filename = $this->backupService->backupFull();
            $this->flashMessage(__('admin-backup.messages.backup_created', ['filename' => $filename]), 'success');
        } catch (Exception $e) {
            $this->flashMessage(__('admin-backup.messages.backup_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadBackups();
    }

    public function restoreBackup(): void
    {
        $filename = request()->input('filename');
        $isDirectory = request()->input('is_directory') === '1';

        try {
            $this->backupService->restoreBackup($filename, $isDirectory);
            $this->flashMessage(__('admin-backup.messages.restore_success'), 'success');
        } catch (Exception $e) {
            $this->flashMessage(__('admin-backup.messages.restore_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadBackups();
    }

    public function deleteBackup(): void
    {
        $filename = request()->input('filename');
        $isDirectory = request()->input('is_directory') === '1';

        try {
            $this->backupService->deleteBackup($filename, $isDirectory);
            $this->flashMessage(__('admin-backup.messages.backup_deleted'), 'success');
        } catch (Exception $e) {
            $this->flashMessage(__('admin-backup.messages.delete_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadBackups();
    }

    public function bulkDeleteBackups(): void
    {
        $filenames = request()->input('selected', []);

        if (empty($filenames)) {
            return;
        }

        $backupIndex = [];
        foreach ($this->backups as $backup) {
            $backupIndex[$backup['filename']] = $backup['is_directory'];
        }

        $deleted = 0;
        foreach ($filenames as $filename) {
            $isDirectory = $backupIndex[$filename] ?? false;

            try {
                $this->backupService->deleteBackup($filename, $isDirectory);
                $deleted++;
            } catch (Exception $e) {
                logs()->warning("Bulk delete backup failed for {$filename}: " . $e->getMessage());
            }
        }

        if ($deleted > 0) {
            $this->flashMessage(__('admin-backup.messages.backup_deleted') . " ({$deleted})", 'success');
        }

        $this->loadBackups();
    }

    public function refreshBackups(): void
    {
        $this->loadBackups();
        $this->flashMessage(__('admin-backup.messages.list_refreshed'), 'success');
    }

    protected function loadBackups(): void
    {
        $this->backups = $this->backupService->getBackups();
        $this->metrics = [
            'total_backups' => count($this->backups),
            'total_size' => $this->backupService->getTotalBackupSize(),
        ];
    }
}
