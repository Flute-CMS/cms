<?php

namespace Flute\Admin\Packages\Update\Screens;

use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Core\App;
use Flute\Core\Database\Entities\Theme;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Theme\ThemeManager;
use Flute\Core\Update\Services\UpdateService;
use Flute\Core\Update\Updaters\CmsUpdater;
use Flute\Core\Update\Updaters\ModuleUpdater;
use Flute\Core\Update\Updaters\ThemeUpdater;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class UpdateScreen extends Screen
{
    public $name = 'admin-update.title';

    public $description = 'admin-update.description';

    public ?string $permission = 'admin.system';

    /**
     */
    protected UpdateService $updateService;

    public function mount(): void
    {
        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-update.title'));

        $this->updateService = app(UpdateService::class);
        $savedChannel = config('app.update_channel', 'stable');
        $channel = request()->get('channel') ?? $savedChannel;
        $this->updateService->setChannel($channel);

        if (request()->get('mock') === '1') {
            $this->updateService->enableMockData(true);
        }
    }

    /**
     * Get screen layouts
     */
    public function layout(): array
    {
        $activeChannel = $this->updateService->getChannel();
        $otherChannel = $activeChannel === 'stable' ? 'early' : 'stable';

        $updates = $this->updateService->getAvailableUpdates();
        $otherUpdates = $this->updateService->getAllVersionsForChannel($otherChannel);

        return [
            LayoutFactory::view('admin-update::components.javascript'),

            LayoutFactory::view('admin-update::layouts.update-center', [
                'current_version' => App::VERSION,
                'active_channel' => $activeChannel,
                'update' => $updates['cms'] ?? null,
                'modules' => $updates['modules'] ?? [],
                'themes' => $updates['themes'] ?? [],
                'other_channel' => $otherChannel,
                'other_update' => $otherUpdates['cms'] ?? null,
                'other_modules' => $otherUpdates['modules'] ?? [],
                'other_themes' => $otherUpdates['themes'] ?? [],
            ]),
        ];
    }

    /**
     * Get screen commands
     */
    public function commandBar(): array
    {
        return [];
    }

    /**
     * Handle check updates command
     */
    public function handleCheckUpdates(): void
    {
        $this->updateService->clearCache();
        $this->updateService->getAvailableUpdates(true);

        $this->flashMessage(__('admin-update.check_complete'));
    }

    public function switchChannel(): void
    {
        $data = request()->all();
        $channel = in_array($data['channel'] ?? '', ['stable', 'early'], true) ? $data['channel'] : 'stable';
        $currentAppConfig = config('app');
        $currentAppConfig['update_channel'] = $channel;
        config()->set('app', $currentAppConfig);
        config()->save();
        $this->updateService->setChannel($channel);
        $this->updateService->clearCache();
        $this->updateService->getAvailableUpdates(true);
        $this->flashMessage(__('admin-update.check_complete'));
    }

    /**
     * Handle update command
     */
    public function handleUpdate(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        try {
            $data = request()->all();

            $type = $data['type'] ?? '';
            $id = $data['id'] ?? null;
            $version = $data['version'] ?? null;

            $this->flashMessage(__('admin-update.update_preparing'));

            $this->updateService->clearCache();
            $updates = $this->updateService->getAvailableUpdates(true);

            if (
                $type === 'cms' && empty($updates['cms'])
                || $type === 'module' && ( empty($id) || empty($updates['modules'][$id]) )
                || $type === 'theme' && ( empty($id) || empty($updates['themes'][$id]) )
            ) {
                throw new InvalidArgumentException(__('admin-update.no_updates'));
            }

            $this->flashMessage(__('admin-update.update_downloading'));
            $packageFile = $this->updateService->downloadUpdate($type, $id, $version);

            if (empty($packageFile) || !file_exists($packageFile)) {
                throw new RuntimeException(__('admin-update.update_failed'));
            }

            $this->flashMessage(__('admin-update.update_extracting'));

            $success = match ($type) {
                'cms' => ( new CmsUpdater() )->update(['package_file' => $packageFile]),
                'module' => $this->updateModule($id, ['package_file' => $packageFile]),
                'theme' => $this->updateTheme($id, ['package_file' => $packageFile]),
                default => throw new InvalidArgumentException(__('admin-update.unknown_type')),
            };

            if ($success) {
                $this->updateService->clearCache();

                app(\Flute\Core\ModulesManager\ModuleManager::class)->clearCache();

                if (file_exists($packageFile)) {
                    @unlink($packageFile);
                }

                $this->updateService->getAvailableUpdates(true);

                $this->flashMessage(__('admin-update.update_complete'));
                $this->triggerSidebarRefresh();
            } else {
                throw new RuntimeException(__('admin-update.update_failed'));
            }
        } catch (Throwable $e) {
            if (is_debug()) {
                throw $e;
            }
            logs()->error('Update error: ' . $e->getMessage());
            $this->flashMessage(__('admin-update.update_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    /**
     * Handle update all command
     */
    public function handleUpdateAll(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        try {
            $this->flashMessage(__('admin-update.update_all_preparing'));

            $this->updateService->clearCache();
            $updates = $this->updateService->getAvailableUpdates(true);

            $totalUpdates = 0;
            $successfulUpdates = 0;

            if (!empty($updates['cms'])) {
                $totalUpdates++;

                try {
                    $this->flashMessage(__('admin-update.update_downloading') . ' (CMS)');
                    $packageFile = $this->updateService->downloadUpdate('cms', null, $updates['cms']['version']);

                    if (!empty($packageFile) && file_exists($packageFile)) {
                        $this->flashMessage(__('admin-update.update_extracting') . ' (CMS)');
                        $success = ( new CmsUpdater() )->update(['package_file' => $packageFile]);

                        if ($success) {
                            $successfulUpdates++;
                        }

                        if (file_exists($packageFile)) {
                            @unlink($packageFile);
                        }
                    }
                } catch (Throwable $e) {
                    logs()->error('CMS update error: ' . $e->getMessage());
                }
            }

            if (!empty($updates['modules'])) {
                foreach ($updates['modules'] as $moduleId => $moduleUpdate) {
                    $totalUpdates++;

                    try {
                        $this->flashMessage(__('admin-update.update_downloading') . " ({$moduleUpdate['name']})");
                        $packageFile = $this->updateService->downloadUpdate(
                            'module',
                            $moduleId,
                            $moduleUpdate['version'],
                        );

                        if (!empty($packageFile) && file_exists($packageFile)) {
                            $this->flashMessage(__('admin-update.update_extracting') . " ({$moduleUpdate['name']})");
                            $success = $this->updateModule($moduleId, ['package_file' => $packageFile]);

                            if ($success) {
                                $successfulUpdates++;
                            }

                            if (file_exists($packageFile)) {
                                @unlink($packageFile);
                            }
                        }
                    } catch (Throwable $e) {
                        logs()->error("Module {$moduleId} update error: " . $e->getMessage());
                    }
                }
            }

            if (!empty($updates['themes'])) {
                foreach ($updates['themes'] as $themeId => $themeUpdate) {
                    $totalUpdates++;

                    try {
                        $this->flashMessage(__('admin-update.update_downloading') . " ({$themeUpdate['name']})");
                        $packageFile = $this->updateService->downloadUpdate('theme', $themeId, $themeUpdate['version']);

                        if (!empty($packageFile) && file_exists($packageFile)) {
                            $this->flashMessage(__('admin-update.update_extracting') . " ({$themeUpdate['name']})");
                            $success = $this->updateTheme($themeId, ['package_file' => $packageFile]);

                            if ($success) {
                                $successfulUpdates++;
                            }

                            if (file_exists($packageFile)) {
                                @unlink($packageFile);
                            }
                        }
                    } catch (Throwable $e) {
                        logs()->error("Theme {$themeId} update error: " . $e->getMessage());
                    }
                }
            }

            $this->updateService->clearCache();
            app(\Flute\Core\ModulesManager\ModuleManager::class)->clearCache();

            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            $this->updateService->getAvailableUpdates(true);

            if ($successfulUpdates === $totalUpdates && $totalUpdates > 0) {
                $this->flashMessage(__('admin-update.update_all_complete'));
            } else {
                $this->flashMessage(__('admin-update.update_complete') . " ({$successfulUpdates}/{$totalUpdates})");
            }

            $this->triggerSidebarRefresh();
        } catch (Throwable $e) {
            if (is_debug()) {
                throw $e;
            }
            logs()->error('Bulk update error: ' . $e->getMessage());
            $this->flashMessage(__('admin-update.update_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    /**
     * Install a specific CMS version from the catalog (upgrade or rollback)
     */
    public function handleInstallVersion(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        try {
            $data = request()->all();
            $version = $data['version'] ?? null;

            if (empty($version)) {
                throw new InvalidArgumentException(__('admin-update.no_version_selected'));
            }

            $this->flashMessage(__('admin-update.update_downloading'));
            $packageFile = $this->updateService->downloadVersionFromCatalog($version);

            if (empty($packageFile) || !file_exists($packageFile)) {
                throw new RuntimeException(__('admin-update.update_failed'));
            }

            $this->flashMessage(__('admin-update.update_extracting'));

            $success = ( new CmsUpdater() )->update(['package_file' => $packageFile]);

            if ($success) {
                $this->updateService->clearCache();
                app(ModuleManager::class)->clearCache();

                if (file_exists($packageFile)) {
                    @unlink($packageFile);
                }

                $this->flashMessage(__('admin-update.version_installed', ['version' => $version]));
                $this->triggerSidebarRefresh();
            } else {
                throw new RuntimeException(__('admin-update.update_failed'));
            }
        } catch (Throwable $e) {
            if (is_debug()) {
                throw $e;
            }
            logs()->error('Version install error: ' . $e->getMessage());
            $this->flashMessage(__('admin-update.update_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    /**
     * Update module
     */
    protected function updateModule(string $moduleId, array $data): bool
    {
        $module = app(ModuleManager::class)->getModule($moduleId);
        if (!$module) {
            throw new InvalidArgumentException("Module {$moduleId} not found");
        }

        return ( new ModuleUpdater($module) )->update($data);
    }

    /**
     * Update theme
     */
    protected function updateTheme(string $themeId, array $data): bool
    {
        $theme = app(ThemeManager::class)->getTheme($themeId);
        if (!$theme) {
            throw new InvalidArgumentException("Theme {$themeId} not found");
        }

        $themeData = app(ThemeManager::class)->getThemeData($themeId);

        $theme = Theme::findOne(['key' => $themeId]);

        return ( new ThemeUpdater($theme, $themeData) )->update($data);
    }

    protected function triggerSidebarRefresh(): void
    {
        $this->dispatchBrowserEvent('sidebar-refresh');
    }
}
