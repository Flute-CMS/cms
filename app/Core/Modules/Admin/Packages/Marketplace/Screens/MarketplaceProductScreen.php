<?php

namespace Flute\Admin\Packages\Marketplace\Screens;

use Exception;
use Flute\Admin\Packages\Marketplace\Services\MarketplaceService;
use Flute\Admin\Packages\Marketplace\Services\ModuleInstallerService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\ModulesManager\ModuleManager;

class MarketplaceProductScreen extends Screen
{
    public string $slugParam = '';

    public array $module = [];

    public array $versions = [];

    public bool $isLoading = false;

    protected ?string $name = 'admin-marketplace.labels.module_details';

    protected ?string $description = 'admin-marketplace.labels.module_details';

    protected MarketplaceService $marketplaceService;

    protected ModuleManager $moduleManager;

    public function mount(): void
    {
        breadcrumb()
            ->add(__('admin-marketplace.labels.marketplace'), url('/admin/marketplace'));

        $this->marketplaceService = app(MarketplaceService::class);
        $this->moduleManager = app(ModuleManager::class);

        $this->slugParam = (string) request()->input('slug');
        if (!$this->slugParam) {
            $path = request()->getPathInfo();
            $parts = explode('/', trim($path, '/'));
            $this->slugParam = end($parts) ?: '';
        }

        $this->loadModule();
    }

    public function installModule(string $slug)
    {
        $this->ensureServicesInitialized();

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
        $this->isLoading = true;

        try {
            $moduleInstaller = app(ModuleInstallerService::class);

            $allModules = $this->marketplaceService->getModules('', '', true);
            $moduleData = null;
            foreach ($allModules as $m) {
                if (($m['slug'] ?? '') === $slug) {
                    $moduleData = $m;

                    break;
                }
            }
            $downloadUrl = $moduleData['downloadUrl'] ?? null;
            if (empty($downloadUrl)) {
                throw new Exception(__('admin-marketplace.messages.download_failed'));
            }
            $module = ['downloadUrl' => $downloadUrl, 'slug' => $slug];

            // Step 1: Download module, handle expired token
            try {
                $download = $moduleInstaller->downloadModule($module);
            } catch (Exception $e) {
                if ($e->getMessage() === 'MARKETPLACE_BAD_REQUEST') {
                    $this->marketplaceService->clearCache();
                    $allModules = $this->marketplaceService->getModules('', '', true);
                    $moduleData = null;
                    foreach ($allModules as $m) {
                        if (($m['slug'] ?? '') === $slug) {
                            $moduleData = $m;

                            break;
                        }
                    }
                    $downloadUrl = $moduleData['downloadUrl'] ?? null;
                    if (empty($downloadUrl)) {
                        throw new Exception(__('admin-marketplace.messages.download_failed'));
                    }
                    $module['downloadUrl'] = $downloadUrl;

                    try {
                        $download = $moduleInstaller->downloadModule($module);
                    } catch (Exception $e2) {
                        logs()->error($e2);
                        $this->flashMessage($e2->getMessage(), 'error');
                        $this->isLoading = false;

                        return;
                    }
                } else {
                    throw $e;
                }
            }

            // Step 2: Extract module
            $extract = $moduleInstaller->extractModule($module);

            // Step 3: Validate module
            $validate = $moduleInstaller->validateModule($module);
            $moduleInfo = $validate['moduleInfo'] ?? [];

            if (!empty($moduleInfo) && !empty($moduleInfo['name'])) {
                $module['name'] = $moduleInfo['name'];
            }

            // Step 4: Install module files
            $installResult = $moduleInstaller->installModule($module);

            // Step 5: Update composer dependencies
            try {
                $moduleInstaller->updateComposerDependencies();
            } catch (Exception $e) {
                $moduleInstaller->rollbackInstallation($installResult['moduleFolder'], $installResult['backupDir'] ?? null);

                throw $e;
            }

            // Step 6: Install/update module in system
            $moduleManager = app(\Flute\Core\ModulesManager\ModuleManager::class);
            $moduleActions = new \Flute\Core\ModulesManager\ModuleActions();

            $moduleManager->refreshModules();

            $moduleKey = $installResult['moduleFolder'];

            if ($moduleManager->issetModule($moduleKey)) {
                $moduleInfo = $moduleManager->getModule($moduleKey);

                if ($moduleInfo->status === \Flute\Core\ModulesManager\ModuleManager::NOTINSTALLED) {
                    $moduleActions->installModule($moduleInfo, $moduleManager);
                } else {
                    $moduleActions->updateModule($moduleInfo, $moduleManager);
                }

                $moduleManager->refreshModules();

                if ($moduleInfo->status !== \Flute\Core\ModulesManager\ModuleManager::ACTIVE) {
                    $moduleActions->activateModule($moduleInfo, $moduleManager);
                }
            } else {
                throw new Exception(__('admin-marketplace.messages.install_failed') . ': Модуль не найден после копирования файлов');
            }

            $this->flashMessage(__('admin-marketplace.messages.module_installed'), 'success');
            $this->triggerSidebarRefresh();

        } catch (Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        } finally {
            if (isset($moduleInstaller)) {
                $moduleInstaller->finishInstallation();
            }
            $this->moduleManager->clearCache();
            $this->moduleManager->refreshModules();
            $this->loadModule();
            $this->isLoading = false;
        }
    }

    public function uninstallModule(string $key)
    {
        $this->ensureServicesInitialized();

        try {
            $moduleManager = app(\Flute\Core\ModulesManager\ModuleManager::class);
            $moduleActions = new \Flute\Core\ModulesManager\ModuleActions();

            if (!$moduleManager->issetModule($key)) {
                throw new Exception(__('admin-marketplace.messages.module_not_found'));
            }

            $moduleInfo = $moduleManager->getModule($key);
            $moduleActions->uninstallModule($moduleInfo, $moduleManager);

            $moduleManager->refreshModules();
            $this->flashMessage(__('admin-marketplace.messages.module_uninstalled'), 'success');
            $this->triggerSidebarRefresh();
        } catch (Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        } finally {
            $this->marketplaceService->clearCache();
            $this->loadModule();
        }
    }

    public function activateModule(string $key)
    {
        $this->ensureServicesInitialized();

        try {
            $moduleManager = app(\Flute\Core\ModulesManager\ModuleManager::class);
            $moduleActions = new \Flute\Core\ModulesManager\ModuleActions();

            if (!$moduleManager->issetModule($key)) {
                throw new Exception(__('admin-marketplace.messages.module_not_found'));
            }

            $moduleInfo = $moduleManager->getModule($key);
            $moduleActions->activateModule($moduleInfo, $moduleManager);

            $moduleManager->refreshModules();
            $this->flashMessage(__('admin-marketplace.messages.module_activated'), 'success');
            $this->triggerSidebarRefresh();
        } catch (Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        } finally {
            $this->marketplaceService->clearCache();
            $this->loadModule();
        }
    }

    public function deactivateModule(string $key)
    {
        $this->ensureServicesInitialized();

        try {
            $moduleManager = app(\Flute\Core\ModulesManager\ModuleManager::class);
            $moduleActions = new \Flute\Core\ModulesManager\ModuleActions();

            if (!$moduleManager->issetModule($key)) {
                throw new Exception(__('admin-marketplace.messages.module_not_found'));
            }

            $moduleInfo = $moduleManager->getModule($key);
            $moduleActions->disableModule($moduleInfo, $moduleManager);

            $moduleManager->refreshModules();
            $this->flashMessage(__('admin-marketplace.messages.module_deactivated'), 'success');
            $this->triggerSidebarRefresh();
        } catch (Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        } finally {
            $this->marketplaceService->clearCache();
            $this->loadModule();
        }
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('admin-marketplace.actions.back_to_list'))
                ->type(Color::OUTLINE_PRIMARY)
                ->redirect('/admin/marketplace'),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::view('admin-marketplace::marketplace.module-details', [
                'module' => $this->module,
                'versions' => $this->versions,
                'isInstalled' => $this->module['isInstalled'] ?? false,
                'needsUpdate' => $this->module['needsUpdate'] ?? false,
                'status' => $this->module['status'] ?? '',
                'isLoading' => $this->isLoading,
            ]),
        ];
    }

    protected function ensureServicesInitialized(): void
    {
        if (!isset($this->marketplaceService)) {
            $this->marketplaceService = app(MarketplaceService::class);
        }
        if (!isset($this->moduleManager)) {
            $this->moduleManager = app(ModuleManager::class);
        }
    }

    protected function triggerSidebarRefresh(): void
    {
        $this->dispatchBrowserEvent('sidebar-refresh');
    }

    protected function loadModule(): void
    {
        $this->ensureServicesInitialized();

        try {
            if (!$this->slugParam) {
                return;
            }

            $this->moduleManager->refreshModules();

            try {
                $this->module = $this->marketplaceService->getModuleBySlug($this->slugParam);
            } catch (Exception $e) {
                $modules = $this->marketplaceService->getModules('', '', true);
                foreach ($modules as $item) {
                    if (!empty($item['slug']) && $item['slug'] === $this->slugParam) {
                        $this->module = $item;

                        break;
                    }
                }
            }

            if (empty($this->module) || !isset($this->module['name'])) {
                return;
            }

            $moduleName = $this->module['name'];
            $this->module['isInstalled'] = $this->moduleManager->issetModule($moduleName) &&
                $this->moduleManager->getModule($moduleName)->status !== 'notinstalled';

            if ($this->module['isInstalled'] && isset($this->module['currentVersion'])) {
                $installedModule = $this->moduleManager->getModule($moduleName);
                $this->module['installedVersion'] = $installedModule->installedVersion ?? '0.0.0';
                $this->module['needsUpdate'] = version_compare(
                    $this->module['currentVersion'],
                    $this->module['installedVersion'],
                    '>'
                );
                $this->module['status'] = $installedModule->status ?? 'disabled';
            } else {
                $this->module['isInstalled'] = false;
                $this->module['status'] = '';
            }

            if (!empty($moduleName)) {
                $this->name = $moduleName;
                breadcrumb()->add($moduleName);
            }
            $this->versions = $this->module['changelog'] ?? [];
        } catch (Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }
    }
}
