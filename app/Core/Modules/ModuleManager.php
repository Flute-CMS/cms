<?php

namespace Flute\Core\Modules;

use Flute\Core\App;
use Flute\Core\Database\Entities\Module;
use Flute\Core\Modules\Events\ModuleRegistered;
use Flute\Core\Modules\Exceptions\DependencyException;
use Flute\Core\Support\Collection;
use Flute\Core\Theme\ThemeManager;
use Nette\Utils\JsonException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Throwable;

/**
 * Class ModuleManager
 *
 * Manages the modules of the application.
 */
class ModuleManager
{
    public Collection $installedModules;
    public Collection $notInstalledModules;
    public Collection $disabledModules;
    public Collection $activeModules;

    public const ACTIVE = 'active';
    public const DISABLED = 'disabled';
    public const NOTINSTALLED = 'notinstalled';
    public const INSTALLED = 'installed';

    protected const CACHE_TIME = 24 * 60 * 60;
    protected Collection $modules;
    protected string $modulesPath;
    protected array $modulesJson;
    protected array $modulesDatabase;
    protected array $serviceProviders;
    protected bool $performance;
    protected ?ModuleDependencies $dependencyChecker;
    protected ?EventDispatcher $eventDispatcher;

    /**
     * ModuleManager constructor.
     *
     * Initializes class properties, loads modules information from json and database, 
     * and sets modules information.
     */
    public function __construct(ModuleDependencies $dependencyChecker, EventDispatcher $eventDispatcher)
    {
        // Нечего нам модули грузить, если у нас двигло не стоит
        if (!is_installed())
            return;

        $this->eventDispatcher = $eventDispatcher;

        $this->modulesPath = path('app/Modules');
        $this->performance = (bool) (app('app.mode') == App::PERFORMANCE_MODE);
        $this->modules = collect();
        $this->dependencyChecker = $dependencyChecker;

        $this->activeModules = collect();
        $this->disabledModules = collect();
        $this->notInstalledModules = collect();
        $this->installedModules = collect();

        $this->loadModulesJson();
        $this->loadModulesCollections();
        $this->loadModulesFromDatabase();
        $this->setInstalledModules();
        $this->setNotInstalledModules();
        $this->setDisabledModules();
        $this->setActiveModules();

        $this->checkModulesDependencies();

        $this->setServiceProviders();
        $this->registerModules();
    }

    public function getModuleDependencies(): ModuleDependencies
    {
        return $this->dependencyChecker;
    }

    /**
     * Get all active modules
     * 
     * @return Collection
     */
    public function getActive(): Collection
    {
        return $this->activeModules;
    }

    /**
     * Get the module json from the system
     * 
     * @param string $key
     * 
     * @return string
     */
    public function getModuleJson(string $key): string
    {
        return $this->modulesJson[$key];
    }

    /**
     * Get module from the system
     * 
     * @param string $key
     * 
     * @return ModuleInformation
     */
    public function getModule(string $key): ModuleInformation
    {
        if (!$this->issetModule($key))
            throw new \Exception("Module {$key} wasn't found");

        return $this->modules->get($key);
    }

    /**
     * Check if module exists in the system
     * 
     * @param string $key
     * 
     * @return bool
     */
    public function issetModule(string $key): bool
    {
        return $this->modules->offsetExists($key);
    }

    /**
     * Get all modules
     * 
     * @return Collection The list of modules
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    /**
     * Checks all active modules dependencies.
     * @throws DependencyException
     */
    protected function checkModulesDependencies(): void
    {
        foreach ($this->activeModules ?? [] as $module) {
            try {
                /** @var ThemeManager $themeManager */
                $themeManager = app(ThemeManager::class);

                $this->dependencyChecker->checkDependencies($module->dependencies, $this->activeModules, $themeManager->getThemeInfo());
            } catch (DependencyException $e) {
                logs('modules')->emergency("[EMERGENCY MODULE SHUTDOWN] Flute module \"" . $module->key . "\" dependency check failed - " . $e->getMessage());

                (new ModuleActions)->disableModule($module, $this);

                if (is_debug())
                    throw new DependencyException($e->getMessage());
            }
        }
    }

    /**
     * Registers the modules with the application.
     */
    protected function registerModules(): void
    {
        ModuleRegister::registerServiceProviders($this->serviceProviders);
    }

    /**
     * Sets the installed modules.
     */
    protected function setInstalledModules(): void
    {
        $this->installedModules = $this->filterAndCache(self::NOTINSTALLED, true, 'installed');
    }

    /**
     * Sets the active modules.
     */
    protected function setActiveModules(): void
    {
        $this->activeModules = $this->filterAndCache(self::ACTIVE);
    }

    /**
     * Sets the disabled modules.
     */
    protected function setDisabledModules(): void
    {
        $this->disabledModules = $this->filterAndCache(self::DISABLED);
    }

    /**
     * Sets the not installed modules.
     */
    protected function setNotInstalledModules(): void
    {
        $this->notInstalledModules = $this->filterAndCache(self::NOTINSTALLED);
    }

    /**
     * Sets the service providers for the modules.
     */
    protected function setServiceProviders(): void
    {
        $providers = [];

        foreach ($this->modules ?? [] as $module) {
            /** @var ModuleInformation $module */

            foreach ($module->providers as $provider) {
                if (is_object($provider) && $provider->class) {
                    $providers[] = [
                        "class" => $provider->class,
                        "order" => $provider->order ?? 0,
                        'module' => $module->key,
                        'active' => $module->status === self::ACTIVE
                    ];
                } else {
                    $providers[] = [
                        "class" => $provider,
                        "order" => 0,
                        'module' => $module->key,
                        'active' => $module->status === self::ACTIVE
                    ];
                }
            }
        }

        // Sort the providers array by 'order' key
        usort($providers, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        $this->serviceProviders = $providers;
    }

    /**
     * Loads all the modules' details from json.
     */
    protected function loadModulesJson(): void
    {
        $this->modulesJson = $this->performance ? cache()->callback("flute.modules.json", function () {
            return ModuleFinder::getAllJson($this->modulesPath);
        }, 84000) : ModuleFinder::getAllJson($this->modulesPath);
    }

    /**
     * Loads all the modules from the database.
     */
    protected function loadModulesFromDatabase(): void
    {
        // NOT THE BEST SOLUTION
        // $modules = $this->performance ? cache()->callback("flute.modules.alldb", function () {
        //     return rep(Module::class)->select()->fetchAll();
        // }, self::CACHE_TIME) : rep(Module::class)->select()->fetchAll();

        $modules = rep(Module::class)->select()->fetchAll();

        $this->modulesDatabase = $modules;

        $this->setCurrentStatusModules();
    }

    /**
     * Maps the status of the module to the module object.
     *
     * Checks if the module is in the database and if not, it adds it to the database and maps the status of
     * the module to the module object in the module collection
     */
    protected function setCurrentStatusModules()
    {
        $columnsDb = array_column($this->modulesDatabase, 'key');

        foreach ($this->modules as $module) {

            // Наш итоговый объект, который попадет в массив
            $moduleResult = $this->modules->get($module->key);

            // Для начала мы ищем наш модуль по KEY в нашей БД
            $search = array_search($module->key, $columnsDb);

            // Потом проверяем, если все ок, то добавляем в общий массив, если нет, то добавляем в БД и массив
            if (!is_int($search) || $this->modulesDatabase[$search]->key !== $module->key) {
                $this->createModuleInDatabase($module);
            }
            else {
                $moduleResult->status = $this->modulesDatabase[$search]->status;
                $moduleResult->installedVersion = $this->modulesDatabase[$search]->installedVersion;
            }

            $this->createModuleInCollection($module->key, $moduleResult);
        }
    }

    /**
     * Creates a module entry in the database.
     *
     * This function is called when the module is not found in the database and it needs to be added.
     *
     * @param ModuleInformation $moduleInformation
     * @throws Throwable
     */
    protected function createModuleInDatabase(ModuleInformation $moduleInformation): void
    {
        $module = new Module;
        $module->key = $moduleInformation->key;
        $module->name = $moduleInformation->name;
        $module->description = $moduleInformation->description;
        $module->status = $moduleInformation->status ?? ModuleManager::NOTINSTALLED;

        transaction($module)->run();

        logs('modules')->info("Module {$module->key} was initialized in database");
    }

    /**
     * Loads the modules collection.
     *
     * This function populates the modules collection with the information from the json file.
     * @throws JsonException
     */
    protected function loadModulesCollections()
    {
        if ($this->performance === true) {
            $values = cache()->callback('flute.modules.array', function () {
                $val = [];

                foreach ($this->modulesJson as $moduleName => $module) $val[$moduleName] = new ModuleInformation(ModuleFinder::getModuleJson($module), $moduleName);

                return $val;
            }, self::CACHE_TIME);

            foreach ($values as $moduleName => $module)
                $this->createModuleInCollection($moduleName, $module);
        } else {
            foreach ($this->modulesJson as $moduleName => $module)
                $this->createModuleInCollection(
                    $moduleName,
                    new ModuleInformation(ModuleFinder::getModuleJson($module), $moduleName)
                );
        }
    }

    /**
     * Create module in collection
     * 
     * @param string $moduleName
     * @param ModuleInformation $moduleInformation
     * 
     * @return void
     */
    protected function createModuleInCollection(string $moduleName, ModuleInformation $moduleInformation): void
    {
        $this->eventDispatcher->dispatch(new ModuleRegistered($moduleName, $moduleInformation), ModuleRegistered::NAME);

        $this->modules->set(
            $moduleName,
            $moduleInformation
        );
    }

    /**
     * Filters and caches the modules based on their status.
     *
     * @param string $param The status to filter the modules on.
     * @param bool $notEqual Indicates whether to include or exclude modules with the status.
     * @param string|null $key The key to cache the result under.
     * @return Collection The filtered and possibly cached modules.
     */
    protected function filterAndCache(string $param, bool $notEqual = false, string $key = null): Collection
    {
        $filterModules = function () use ($param, $notEqual) {
            return $this->modules->filter(function (ModuleInformation $item) use ($param, $notEqual) {
                return $notEqual ? ($item->status != $param) : ($item->status == $param);
            });
        };

        if ($this->performance) {
            return cache()->callback("flute.modules.$param", $filterModules, self::CACHE_TIME);
        } else {
            return $filterModules();
        }
    }
}