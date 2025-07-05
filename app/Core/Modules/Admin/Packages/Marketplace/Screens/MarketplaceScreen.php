<?php

namespace Flute\Admin\Packages\Marketplace\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Packages\Marketplace\Services\MarketplaceService;
use Flute\Admin\Packages\Marketplace\Services\ModuleInstallerService;
use Flute\Core\ModulesManager\ModuleManager;

class MarketplaceScreen extends Screen
{
    /**
     * Screen title
     * 
     * @var string
     */
    protected ?string $name = 'admin-marketplace.labels.marketplace';

    /**
     * Screen description
     * 
     * @var string
     */
    protected ?string $description = 'admin-marketplace.descriptions.marketplace';

    /**
     * @var MarketplaceService
     */
    protected $marketplaceService;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var array
     */
    public $modules = [];

    /**
     * @var string
     */
    public $searchQuery = '';

    /**
     * @var string
     */
    public $selectedCategory = '';

    /**
     * @var string
     */
    public $priceFilter = '';

    /**
     * @var string
     */
    public $statusFilter = '';

    /**
     * @var array
     */
    public $categories = [];

    /**
     * @var bool
     */
    public $isLoading = false;

    /**
     * @var string
     */
    public $moduleArchivePath = '';

    /**
     * @var string
     */
    public $moduleExtractPath = '';

    /**
     * @var string
     */
    public $moduleKey = '';

    /**
     * Mount the screen
     * 
     * @return void
     */
    public function mount(): void
    {
        breadcrumb()->add(__('admin-marketplace.labels.marketplace'));

        $this->marketplaceService = app(MarketplaceService::class);
        $this->moduleManager = app(ModuleManager::class);

        // $this->categories = $this->getCategories();

        $this->loadModules();
    }

    /**
     * Get screen commands
     */
    public function commandBar(): array
    {
        return [
            Button::make(__('admin-marketplace.labels.refresh'))
                ->icon('ph.bold.arrows-clockwise-bold')
                ->method('handleRefresh'),
        ];
    }

    /**
     * Handle refresh command
     */
    public function handleRefresh()
    {
        $this->loadModules(true);

        $this->flashMessage(__('admin-marketplace.messages.refresh_success'), 'success');
    }

    /**
     * Load modules from marketplace
     */
    public function loadModules(bool $force = false)
    {
        $this->isLoading = true;

        try {
            $this->modules = $this->marketplaceService->getModules("", "", $force);

            foreach ($this->modules as &$module) {
                if (!isset($module['name'])) {
                    continue;
                }

                $moduleKey = $module['name'];

                $module['isInstalled'] = $this->moduleManager->issetModule($moduleKey) &&
                    $this->moduleManager->getModule($moduleKey)->status !== 'notinstalled';

                if ($module['isInstalled'] && isset($module['currentVersion'])) {
                    $installedModule = $this->moduleManager->getModule($moduleKey);
                    $module['installedVersion'] = $installedModule->installedVersion ?? '0.0.0';
                    $module['needsUpdate'] = version_compare(
                        $module['currentVersion'],
                        $module['installedVersion'],
                        '>'
                    );
                }
            }

            $this->applyLocalFilters();
        } catch (\Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->isLoading = false;
    }

    /**
     * Применить локальные фильтры к полученным данным
     */
    protected function applyLocalFilters()
    {
        if (empty($this->modules)) {
            return;
        }

        $filteredModules = $this->modules;

        if (!empty($this->priceFilter)) {
            $filteredModules = array_filter($filteredModules, function ($module) {
                $isPaid = !empty($module['isPaid']);
                return ($this->priceFilter === 'paid' && $isPaid) || ($this->priceFilter === 'free' && !$isPaid);
            });
        }

        if (!empty($this->statusFilter)) {
            $filteredModules = array_filter($filteredModules, function ($module) {
                $isInstalled = $module['isInstalled'] ?? false;
                $needsUpdate = $module['needsUpdate'] ?? false;

                if ($this->statusFilter === 'installed') {
                    return $isInstalled;
                } elseif ($this->statusFilter === 'notinstalled') {
                    return !$isInstalled;
                } elseif ($this->statusFilter === 'update') {
                    return $isInstalled && $needsUpdate;
                }

                return true;
            });
        }

        if (!empty($this->searchQuery)) {
            $filteredModules = array_filter($filteredModules, function ($module) {
                return stripos($module['name'], $this->searchQuery) !== false
                    || stripos($module['description'] ?? '', $this->searchQuery) !== false;
            });
        }

        $this->modules = array_values($filteredModules);
    }

    public function clearFilters()
    {
        $this->searchQuery = '';
        $this->selectedCategory = '';
        $this->priceFilter = '';
        $this->statusFilter = '';

        $this->loadModules();
    }

    public function statusFilterChanged()
    {
        $this->loadModules();
    }

    /**
     * Search modules
     */
    public function searchChanged()
    {
        $this->searchQuery = request()->input('searchQuery');
        $this->loadModules();
    }

    /**
     * Filter by category
     */
    public function categoryFilterChanged()
    {
        $this->loadModules();
    }

    public function priceFilterChanged()
    {
        $this->loadModules();
    }

    /**
     * Install module
     * 
     * @param string $slug
     * @return void
     */
    public function installModule(string $slug)
    {
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
                throw new \Exception(__('admin-marketplace.messages.download_failed'));
            }
            $module = ['downloadUrl' => $downloadUrl, 'slug' => $slug];

            // Step 1: Download module, handle expired token
            try {
                $download = $moduleInstaller->downloadModule($module);
            } catch (\Exception $e) {
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
                        throw new \Exception(__('admin-marketplace.messages.download_failed'));
                    }
                    $module['downloadUrl'] = $downloadUrl;
                    try {
                        $download = $moduleInstaller->downloadModule($module);
                    } catch (\Exception $e2) {
                        logs()->error($e2);
                        $this->flashMessage($e2->getMessage(), 'error');
                        $this->isLoading = false;
                        return;
                    }
                } else {
                    throw $e;
                }
            }
            $this->moduleArchivePath = $download['path'];

            // Шаг 2: Распаковка модуля
            $extract = $moduleInstaller->extractModule($module);
            $this->moduleExtractPath = $extract['path'];
            $this->moduleKey = $extract['key'] ?? null;

            // Шаг 3: Проверка совместимости
            $validate = $moduleInstaller->validateModule($module);
            $moduleInfo = $validate['moduleInfo'] ?? [];

            if (!empty($moduleInfo) && !empty($moduleInfo['name'])) {
                $module['name'] = $moduleInfo['name'];
            }

            // Шаг 4: Установка модуля
            $moduleInstaller->installModule($module);

            // Шаг 5: Обновление зависимостей Composer
            $moduleInstaller->updateComposerDependencies();

            $this->flashMessage(__('admin-marketplace.messages.module_installed'), 'success');

            $this->loadModules();

            // Шаг 6: Завершение установки
            $moduleInstaller->finishInstallation();

            $this->moduleManager->clearCache();
            $this->moduleManager->refreshModules();
            $this->loadModules();
        } catch (\Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->isLoading = false;
    }

    /**
     * Get the layout elements
     * 
     * @return array
     */
    public function layout(): array
    {
        return [
            LayoutFactory::columns([
                LayoutFactory::view('admin-marketplace::marketplace.module-list', [
                    'modules' => $this->modules,
                    'isLoading' => $this->isLoading,
                    'moduleManager' => $this->moduleManager,
                    'searchQuery' => $this->searchQuery,
                    'selectedCategory' => $this->selectedCategory,
                    'priceFilter' => $this->priceFilter,
                    'statusFilter' => $this->statusFilter,
                    'categories' => $this->categories,
                ]),
            ]),
        ];
    }

    /**
     * Get module categories for filtering
     * 
     * @return array
     */
    public function getCategories()
    {
        try {
            return $this->marketplaceService->getCategories();
        } catch (\Exception $e) {
            logs()->error($e);
            return [];
        }
    }
}
