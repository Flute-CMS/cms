<?php

namespace Flute\Admin\Packages\Update\Screens;

use Flute\Admin\Platform\Actions\Button;
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

class UpdateScreen extends Screen
{
    /**
     * @var UpdateService
     */
    protected UpdateService $updateService;

    public $name = 'admin-update.title';

    public $description = 'admin-update.description';

    public function mount(): void
    {
        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-update.title'));

        $this->updateService = app(UpdateService::class);
    }

    /**
     * Get screen layouts
     */
    public function layout(): array
    {
        $updates = $this->updateService->getAvailableUpdates();

        return [
            LayoutFactory::view('admin-update::components.javascript'),

            LayoutFactory::view('admin-update::layouts.cms-update', [
                'current_version' => App::VERSION,
                'update' => $updates['cms'] ?? null,
                'modules' => $updates['modules'] ?? [],
                'themes' => $updates['themes'] ?? [],
            ])->setVisible(! empty($updates['cms']) || ! empty($updates['modules']) || ! empty($updates['themes'])),

            LayoutFactory::view('admin-update::components.no-updates')->setVisible(
                empty($updates['cms']) && empty($updates['modules']) && empty($updates['themes'])
            ),
        ];
    }

    /**
     * Get screen commands
     */
    public function commandBar(): array
    {
        return [
            Button::make(__('admin-update.check_updates'))
                ->icon('ph.bold.arrows-clockwise-bold')
                ->method('handleCheckUpdates'),
        ];
    }

    /**
     * Handle check updates command
     */
    public function handleCheckUpdates(): void
    {
        $this->updateService->clearCache();
        $this->updateService->getAvailableUpdates(true);
        
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        $this->flashMessage(__('admin-update.check_complete'));
    }

    /**
     * Handle update command
     */
    public function handleUpdate(): void
    {
        try {
            $data = request()->all();

            $type = $data['type'] ?? '';
            $id = $data['id'] ?? null;
            $version = $data['version'] ?? null;

            $this->flashMessage(__('admin-update.update_preparing'));

            $this->updateService->clearCache();
            $updates = $this->updateService->getAvailableUpdates(true);
            
            if (($type === 'cms' && empty($updates['cms'])) ||
                ($type === 'module' && (empty($id) || empty($updates['modules'][$id]))) ||
                ($type === 'theme' && (empty($id) || empty($updates['themes'][$id])))
            ) {
                throw new \InvalidArgumentException(__('admin-update.no_updates'));
            }

            $this->flashMessage(__('admin-update.update_downloading'));
            $packageFile = $this->updateService->downloadUpdate($type, $id, $version);

            if (empty($packageFile) || ! file_exists($packageFile)) {
                throw new \RuntimeException(__('admin-update.update_failed'));
            }

            $this->flashMessage(__('admin-update.update_extracting'));

            $success = match ($type) {
                'cms' => (new CmsUpdater())->update(['package_file' => $packageFile]),
                'module' => $this->updateModule($id, ['package_file' => $packageFile]),
                'theme' => $this->updateTheme($id, ['package_file' => $packageFile]),
                default => throw new \InvalidArgumentException(__('admin-update.unknown_type')),
            };

            if ($success) {
                $this->updateService->clearCache();
                
                cache()->clear();
                
                if (file_exists($packageFile)) {
                    @unlink($packageFile);
                }

                $this->updateService->getAvailableUpdates(true);
                
                $this->flashMessage(__('admin-update.update_complete'));
            } else {
                throw new \RuntimeException(__('admin-update.update_failed'));
            }
        } catch (\Exception $e) {
            if (is_debug()) {
                throw $e;
            }
            logs()->error('Update error: ' . $e->getMessage());
            $this->flashMessage(__('admin-update.update_error', ['message' => $e->getMessage()]), 'error');
        }
    }

    /**
     * Update module
     */
    protected function updateModule(string $moduleId, array $data): bool
    {
        $module = app(ModuleManager::class)->getModule($moduleId);
        if (! $module) {
            throw new \InvalidArgumentException("Module {$moduleId} not found");
        }
        return (new ModuleUpdater($module))->update($data);
    }

    /**
     * Update theme
     */
    protected function updateTheme(string $themeId, array $data): bool
    {
        $theme = app(ThemeManager::class)->getTheme($themeId);
        if (! $theme) {
            throw new \InvalidArgumentException("Theme {$themeId} not found");
        }
        
        $themeData = app(ThemeManager::class)->getThemeData($themeId);

        $theme = Theme::findOne(['key' => $themeId]);

        return (new ThemeUpdater($theme, $themeData))->update($data);
    }
}
