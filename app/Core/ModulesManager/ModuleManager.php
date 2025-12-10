<?php

namespace Flute\Core\ModulesManager;

use Exception;
use Flute\Core\Composer\ComposerManager;
use Flute\Core\Database\Entities\Module;
use Flute\Core\ModulesManager\Events\ModuleRegistered;
use Flute\Core\ModulesManager\Exceptions\ModuleDependencyException;
use Flute\Core\Theme\ThemeManager;
use Illuminate\Support\Collection;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class ModuleManager
 *
 * Manages the modules of the application.
 */
class ModuleManager
{
    public const ACTIVE = 'active';

    public const DISABLED = 'disabled';

    public const NOTINSTALLED = 'notinstalled';

    public const INSTALLED = 'installed';

    protected const CACHE_TIME = 60 * 60; // 1 hour

    public Collection $installedModules;

    public Collection $notInstalledModules;

    public Collection $disabledModules;

    public Collection $activeModules;

    protected Collection $modules;

    protected string $modulesPath;

    protected array $modulesJson;

    protected array $modulesDatabase;

    protected array $serviceProviders;

    protected bool $performance;

    protected ?ModuleDependencies $dependencyChecker;

    protected ?EventDispatcher $eventDispatcher;

    protected bool $initialized = false;

    public function __construct(ModuleDependencies $dependencyChecker, EventDispatcher $eventDispatcher)
    {
        if (!is_installed()) {
            return;
        }

        $this->eventDispatcher = $eventDispatcher;
        $this->modulesPath = path('app/Modules');
        $this->performance = (bool) (is_performance());
        $this->dependencyChecker = $dependencyChecker;

        $this->modules = collect();
        $this->activeModules = collect();
        $this->disabledModules = collect();
        $this->notInstalledModules = collect();
        $this->installedModules = collect();
    }

    /**
     * Initialize the module manager.
     */
    public function initialize(): void
    {
        if ($this->initialized || !is_installed()) {
            return;
        }

        $this->registerModulesAutoload();
        $this->loadModulesJson();
        $this->loadModulesCollections();
        $this->loadModulesFromDatabase();
        $this->setInstalledModules();
        $this->setNotInstalledModules();
        $this->setDisabledModules();
        $this->setActiveModules();
        $this->checkModulesDependencies();
        $this->setServiceProviders();

        $this->initialized = true;

        $this->registerModules();
    }

    /**
     * Get the module dependencies.
     */
    public function getModuleDependencies(): ModuleDependencies
    {
        $this->initialize();

        return $this->dependencyChecker;
    }

    /**
     * Get the active modules.
     */
    public function getActive(): Collection
    {
        $this->initialize();

        return $this->activeModules;
    }

    /**
     * Get the module json.
     */
    public function getModuleJson(string $key): string
    {
        $this->initialize();

        return $this->modulesJson[$key];
    }

    /**
     * Get the module.
     */
    public function getModule(string $key): ModuleInformation
    {
        $this->initialize();

        if (!$this->issetModule($key)) {
            throw new Exception("Module {$key} wasn't found");
        }

        return $this->modules->get($key);
    }

    /**
     * Check if the module exists.
     */
    public function issetModule(string $key): bool
    {
        $this->initialize();

        return $this->modules->offsetExists($key);
    }

    /**
     * Refresh the modules.
     */
    public function refreshModules(): void
    {
        $this->clearCache();

        $this->loadModulesJson();
        $this->loadModulesCollections();
        $this->loadModulesFromDatabase();
        $this->setInstalledModules();
        $this->setNotInstalledModules();
        $this->setDisabledModules();
        $this->setActiveModules();
        $this->checkModulesDependencies();
        $this->setServiceProviders();
    }

    /**
     * Run the composer install only if necessary.
     *
     * For module installation/update: only run if module has a composer.json file
     * For module uninstallation: run without checking since module directory is already gone
     *
     * @param ModuleInformation|null $module Module to check for composer.json
     * @param bool $forceUpdate Force update regardless of composer.json existence
     * @return bool Whether composer update was executed
     */
    public function runComposerInstall(?ModuleInformation $module = null, bool $forceUpdate = false): bool
    {
        if ($forceUpdate || $module === null) {
            app(ComposerManager::class)->install();

            return true;
        }

        $composerJsonPath = path('app/Modules/' . $module->key . '/composer.json');

        if (!fs()->exists($composerJsonPath)) {
            return false;
        }

        app(ComposerManager::class)->install();

        return true;
    }

    /**
     * Get the modules.
     */
    public function getModules(): Collection
    {
        $this->initialize();

        return $this->modules;
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    public function clearCache()
    {
        cache()->delete('flute.modules.collection');
        cache()->delete('flute.modules.alldb');
        cache()->delete('flute.modules.json');
    }

    public function registerModules(): void
    {
        $this->initialize();

        ModuleRegister::registerServiceProviders($this->serviceProviders);
    }

    /**
     * Register PSR-4 autoloading for all modules.
     */
    protected function registerModulesAutoload(): void
    {
        $loader = app()->getLoader();

        $loader->addPsr4('Flute\\Modules\\', $this->modulesPath . DIRECTORY_SEPARATOR);

        if (!is_dir($this->modulesPath)) {
            return;
        }

        $moduleDirs = @scandir($this->modulesPath);

        if ($moduleDirs === false) {
            return;
        }

        foreach ($moduleDirs as $dir) {
            if ($dir === '.' || $dir === '..' || $dir === '.disabled') {
                continue;
            }

            $modulePath = $this->modulesPath . DIRECTORY_SEPARATOR . $dir;

            if (!is_dir($modulePath)) {
                continue;
            }

            $namespace = 'Flute\\Modules\\' . $dir . '\\';
            $loader->addPsr4($namespace, $modulePath . DIRECTORY_SEPARATOR);
        }
    }

    protected function checkModulesDependencies(): void
    {
        static $checking = false;

        if ($checking) {
            return;
        }

        $stateHash = md5(json_encode($this->activeModules->keys()));

        if (cache()->get('modules.dependencies.hash') === $stateHash) {
            return;
        }

        $checking = true;

        try {
            /** @var ThemeManager $themeManager */
            $themeManager = app(ThemeManager::class);
            $modulesToDisable = [];

            foreach ($this->activeModules as $module) {
                try {
                    $this->dependencyChecker->checkDependencies($module->dependencies, $this->activeModules, $themeManager->getThemeInfo());
                } catch (ModuleDependencyException $e) {
                    logs('modules')->emergency("[EMERGENCY MODULE SHUTDOWN] Flute module \"" . $module->key . "\" dependency check failed - " . $e->getMessage());
                    $modulesToDisable[] = $module;
                }
            }

            if (!empty($modulesToDisable)) {
                foreach ($modulesToDisable as $module) {
                    (new ModuleActions())->disableModule($module, $this);
                }

                cache()->delete('flute.modules.alldb');
                cache()->delete('modules.dependencies.hash');

                $this->loadModulesFromDatabase();
                $this->setInstalledModules();
                $this->setNotInstalledModules();
                $this->setDisabledModules();
                $this->setActiveModules();

                $stateHash = md5(json_encode($this->activeModules->keys()));
            }

            cache()->set('modules.dependencies.hash', $stateHash, self::CACHE_TIME);
        } finally {
            $checking = false;
        }
    }

    protected function setInstalledModules(): void
    {
        $this->installedModules = $this->filterModules(self::NOTINSTALLED, true);
    }

    protected function setActiveModules(): void
    {
        $this->activeModules = $this->filterModules(self::ACTIVE);
    }

    protected function setDisabledModules(): void
    {
        $this->disabledModules = $this->filterModules(self::DISABLED);
    }

    protected function setNotInstalledModules(): void
    {
        $this->notInstalledModules = $this->filterModules(self::NOTINSTALLED);
    }

    protected function setServiceProviders(): void
    {
        $providers = [];

        foreach ($this->modules as $module) {
            /** @var ModuleInformation $module */
            foreach ($module->providers as $provider) {
                $providerClass = is_object($provider) && isset($provider->class) ? $provider->class : $provider;
                $order = is_object($provider) && isset($provider->order) ? $provider->order : 0;

                $providers[] = [
                    'class' => $providerClass,
                    'order' => $order,
                    'module' => $module->key,
                    'active' => $module->status === self::ACTIVE,
                ];
            }
        }

        usort($providers, static fn ($a, $b) => $a['order'] <=> $b['order']);

        $this->serviceProviders = $providers;
    }

    protected function loadModulesJson(): void
    {
        $this->modulesJson = cache()->callback('flute.modules.json', fn () => ModuleFinder::getAllJson($this->modulesPath), self::CACHE_TIME);
    }

    protected function loadModulesFromDatabase(): void
    {
        $this->modulesDatabase = cache()->callback('flute.modules.alldb', static function () {
            $modules = Module::findAll();

            return array_map(static fn ($m) => [
                'key' => $m->key,
                'createdAt' => $m->createdAt,
                'status' => $m->status,
                'installedVersion' => $m->installedVersion,
            ], $modules);
        }, self::CACHE_TIME);

        $this->setCurrentStatusModules();
    }

    protected function setCurrentStatusModules(): void
    {
        $columnsDb = array_column($this->modulesDatabase, 'key');

        foreach ($this->modules as $module) {
            $moduleResult = $this->modules->get($module->key);
            $search = array_search($module->key, $columnsDb);

            if ($search === false || $this->modulesDatabase[$search]['key'] !== $module->key) {
                $this->createModuleInDatabase($module);
            } else {
                $moduleResult->createdAt = $this->modulesDatabase[$search]['createdAt'];
                $moduleResult->status = $this->modulesDatabase[$search]['status'];
                $moduleResult->installedVersion = $this->modulesDatabase[$search]['installedVersion'];
            }

            $this->createModuleInCollection($module->key, $moduleResult);
        }
    }

    protected function createModuleInDatabase(ModuleInformation $moduleInformation): void
    {
        $module = new Module();
        $module->key = $moduleInformation->key;
        $module->name = $moduleInformation->name;
        $module->description = $moduleInformation->description;
        $module->status = $moduleInformation->status ?? self::NOTINSTALLED;

        try {
            transaction($module)->run();
            logs('modules')->info("Module {$module->key} was initialized in database");

            $this->modulesDatabase[] = $module;
        } catch (Exception $e) {
            logs('modules')->error("Ошибка при создании модуля в базе данных: " . $e->getMessage());
        }
    }

    protected function loadModulesCollections()
    {
        $this->modules = cache()->callback('flute.modules.collection', function () {
            $collection = collect();

            foreach ($this->modulesJson as $moduleName => $modulePath) {
                $moduleData = ModuleFinder::getModuleJson($modulePath);
                $moduleInformation = new ModuleInformation($moduleData, $moduleName);
                $this->createModuleInCollection($moduleName, $moduleInformation);
                $collection->put($moduleName, $moduleInformation);
            }

            return $collection;
        }, self::CACHE_TIME);
    }

    protected function createModuleInCollection(string $moduleName, ModuleInformation $moduleInformation): void
    {
        $this->eventDispatcher->dispatch(new ModuleRegistered($moduleName, $moduleInformation), ModuleRegistered::NAME);

        $this->modules->put($moduleName, $moduleInformation);
    }

    protected function filterModules(string $status, bool $notEqual = false): Collection
    {
        return $this->modules->filter(static fn (ModuleInformation $module) => $notEqual ? ($module->status !== $status) : ($module->status === $status));
    }
}
