<?php

namespace Flute\Admin\Packages\Marketplace\Screens;

use Exception;
use Flute\Admin\Packages\Marketplace\Services\MarketplaceService;
use Flute\Admin\Packages\Marketplace\Services\ModuleInstallerService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Core\ModulesManager\ModuleManager;

class MarketplaceScreen extends Screen
{
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
     * Screen title
     */
    protected ?string $name = 'admin-marketplace.labels.marketplace';

    /**
     * Screen description
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
     * Mount the screen
     */
    public function mount(): void
    {
        breadcrumb()->add(__('admin-marketplace.labels.marketplace'));

        $this->marketplaceService = app(MarketplaceService::class);
        $this->moduleManager = app(ModuleManager::class);

        $req = request();
        $this->searchQuery = (string) $req->input('q', '');
        $this->selectedCategory = (string) $req->input('category', '');
        $this->priceFilter = (string) $req->input('price', ''); // '', 'free', 'paid'
        $this->statusFilter = (string) $req->input('status', ''); // '', 'installed','notinstalled','update'

        $this->categories = $this->getCategories();

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
     * Yoyo handler: apply filters from current request payload
     */
    public function handleFilters(): void
    {
        $req = request();
        $this->searchQuery = (string) $req->input('q', '');
        $this->selectedCategory = (string) $req->input('category', '');
        $this->priceFilter = (string) $req->input('price', '');
        $this->statusFilter = (string) $req->input('status', '');

        $this->loadModules();
    }

    /**
     * Yoyo handler: clear filters and reload
     */
    public function clearFilters(): void
    {
        $this->searchQuery = '';
        $this->selectedCategory = '';
        $this->priceFilter = '';
        $this->statusFilter = '';

        $this->loadModules();
    }

    /**
     * Load modules from marketplace
     */
    public function loadModules(bool $force = false)
    {
        $this->isLoading = true;

        try {
            $this->modules = $this->marketplaceService->getModules($this->searchQuery, $this->selectedCategory, $force);

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
        } catch (Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->isLoading = false;
    }

    /**
     * Install module
     *
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

            // Шаг 4: Копирование файлов модуля
            $installResult = $moduleInstaller->installModule($module);

            // Шаг 5: Обновление зависимостей Composer
            try {
                $moduleInstaller->updateComposerDependencies();
            } catch (Exception $e) {
                $moduleInstaller->rollbackInstallation($installResult['moduleFolder'], $installResult['backupDir'] ?? null);

                throw $e;
            }

            // Шаг 6: Установка/обновление модуля в системе
            $moduleManager = app(\Flute\Core\ModulesManager\ModuleManager::class);
            $moduleActions = new \Flute\Core\ModulesManager\ModuleActions();

            $moduleKey = $this->waitForInstalledModuleKey($moduleManager, (string) $installResult['moduleFolder']);

            if ($moduleKey !== null && $moduleManager->issetModule($moduleKey)) {
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

            $this->loadModules();

        } catch (Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        } finally {
            $moduleInstaller->finishInstallation();
            $this->moduleManager->clearCache();
            $this->moduleManager->refreshModules();
            $this->loadModules();
        }

        $this->isLoading = false;
    }

    /**
     * Get the layout elements
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
        } catch (Exception $e) {
            logs()->error($e);

            return [];
        }
    }

    protected function waitForInstalledModuleKey(ModuleManager $moduleManager, string $moduleFolder, int $timeoutSeconds = 15): ?string
    {
        $moduleFolder = trim($moduleFolder);
        if ($moduleFolder === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $moduleFolder);
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;
        $normalized = trim($normalized, '/');

        $candidates = array_values(array_unique(array_filter([
            $moduleFolder,
            $normalized,
            basename($normalized),
            explode('/', $normalized)[0] ?? null,
        ], static fn ($v) => is_string($v) && $v !== '')));

        $start = microtime(true);
        while ((microtime(true) - $start) < $timeoutSeconds) {
            clearstatcache(true);
            $moduleManager->refreshModules();

            foreach ($candidates as $candidate) {
                if ($moduleManager->issetModule($candidate)) {
                    return $candidate;
                }
            }

            usleep(250000);
        }

        return null;
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
            $filteredModules = array_filter($filteredModules, fn ($module) => str_contains(strtolower((string)($module['name'] ?? '')), strtolower((string)$this->searchQuery)));
        }

        usort($filteredModules, static function ($a, $b) {
            $ap = !empty($a['isPaid']);
            $bp = !empty($b['isPaid']);
            if ($ap === $bp) {
                return 0;
            }

            return $ap ? -1 : 1;
        });

        $this->modules = array_values($filteredModules);
    }
}
