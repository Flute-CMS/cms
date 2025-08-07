<?php

namespace Flute\Core\Support;

use Flute\Admin\AdminPanel;
use Flute\Admin\Contracts\AdminPackageInterface;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Modules\Page\Services\WidgetManager;
use Flute\Core\ModulesManager\Contracts\ModuleServiceProviderInterface;
use Flute\Core\Services\ConfigurationService;
use Flute\Core\Template\TemplateAssets;
use SplFileInfo;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

/**
 * Class ModuleServiceProvider
 *
 * This abstract class provides a base implementation for a module service provider.
 * It includes loading routes, views, translations, widgets, components, and more
 * with caching mechanisms to improve performance.
 */
abstract class ModuleServiceProvider implements ModuleServiceProviderInterface
{
    /**
     * @var array|string[]
     */
    public array $extensions = [];

    /**
     * @var string|null
     */
    protected ?string $moduleName = '';

    /**
     * @var string|null
     */
    protected ?string $namespace = '';

    /**
     * @var array
     */
    protected array $listen = [];

    /**
     * @var array
     */
    protected array $updateChannel = [];

    /**
     * @var int Default cache duration in seconds.
     */
    protected int $defaultCacheDuration = 3600;

    /**
     * @var array Runtime cache for directories
     */
    private array $directoryCache = [];

    /**
     * @var array Runtime cache for module paths
     */
    private array $modulePathCache = [];

    /**
     * @var array Track loaded resources to prevent duplicate loading
     */
    private array $loadedStatus = [];

    /**
     * Get an array of event listeners for this module.
     *
     * @return array
     */
    public function getEventListeners(): array
    {
        return $this->listen;
    }

    /**
     * Set the name of the module.
     *
     * @param string $moduleName
     * @return void
     */
    public function setModuleName(string $moduleName): void
    {
        $this->moduleName = $moduleName;
    }

    /**
     * Get the name of the module.
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return (string) $this->moduleName;
    }

    /**
     * Set the GitHub update channel (organization and repository).
     *
     * @param string $org
     * @param string $rep
     * @return $this
     */
    public function setUpdateChannel(string $org, string $rep): self
    {
        $this->updateChannel = [
            'org' => $org,
            'rep' => $rep,
        ];

        return $this;
    }

    /**
     * Get the GitHub API URL for the update channel, or false if not set.
     *
     * @return string|false
     */
    public function getUpdateChannel()
    {
        if (empty($this->updateChannel)) {
            return false;
        }

        return "https://api.github.com/repos/{$this->updateChannel['org']}/{$this->updateChannel['rep']}/releases/latest";
    }

    /**
     * Set the default cache duration (in seconds).
     *
     * @param int $seconds
     * @return $this
     */
    public function setCacheDuration(int $seconds): self
    {
        $this->defaultCacheDuration = $seconds;

        return $this;
    }

    /**
     * Load routes from a specific file path.
     *
     * @param string $path
     * @return void
     */
    public function loadRoutesFrom(string $path): void
    {
        require path($path);
    }

    /**
     * Load routes from the current module's routes.php file.
     *
     * @return void
     */
    public function loadRoutes(): void
    {
        $routesCacheKey = "module.{$this->getModuleName()}.routes_loaded";
        if (isset($this->loadedStatus[$routesCacheKey])) {
            return;
        }

        $routesPath = "app/Modules/{$this->getModuleName()}/routes.php";
        if (file_exists(path($routesPath))) {
            require path($routesPath);
            $this->loadedStatus[$routesCacheKey] = true;
        }
    }

    /**
     * Indicates whether extensions are callable. Override if needed.
     *
     * @return bool
     */
    public function isExtensionsCallable(): bool
    {
        return true;
    }

    /**
     * Load entity classes from the module's Entities directory and register them with the ORM.
     *
     * @return void
     */
    public function loadEntities(): void
    {
        $entitiesCacheKey = "module.{$this->getModuleName()}.entities_loaded";
        if (isset($this->loadedStatus[$entitiesCacheKey])) {
            return;
        }

        try {
            $entDir = $this->getModulePath("database/Entities");
            if (!is_dir($entDir)) {
                return;
            }

            $cacheKey = "module.{$this->getModuleName()}.entities_dir";
            $hasEntities = cache()->callback($cacheKey, function () use ($entDir) {
                $finder = finder();
                $finder->files()->in($entDir)->name('*.php');

                return $finder->count() > 0;
            }, $this->defaultCacheDuration);

            if ($hasEntities) {
                $db = app(DatabaseConnection::class);
                // logs('modules')->info("Adding entities directory for module {$this->getModuleName()}: {$entDir}");
                $db->addDir($entDir);
                $this->loadedStatus[$entitiesCacheKey] = true;
            }
        } catch (DirectoryNotFoundException $e) {
            logs('modules')->warning("Directory not found for module {$this->getModuleName()}: " . $e->getMessage());
        } catch (\Exception $e) {
            logs('modules')->error("Error loading entities for module {$this->getModuleName()}: " . $e->getMessage());
        }
    }

    /**
     * Load an admin package into the admin panel.
     *
     * @param AdminPackageInterface $package
     * @return void
     */
    public function loadPackage(AdminPackageInterface $package): void
    {
        app(AdminPanel::class)->registerPackage($package);
    }

    /**
     * Register a template component (usually for Blade or similar).
     *
     * @param string $component
     * @param string $name
     * @return void
     */
    public function loadComponent(string $component, string $name): void
    {
        template()->registerComponent($name, $component);
    }

    /**
     * Load translation files from the module's language directory.
     *
     * @return void
     */
    public function loadTranslations(): void
    {
        $translationsCacheKey = "module.{$this->getModuleName()}.translations_loaded";
        if (isset($this->loadedStatus[$translationsCacheKey])) {
            return;
        }

        $translationsDir = $this->getModulePath('Resources/lang');
        if (!is_dir($translationsDir)) {
            return;
        }

        translation()->loadTranslationsFromDirectory($translationsDir, $this->defaultCacheDuration);
        $this->loadedStatus[$translationsCacheKey] = true;
    }

    /**
     * Load configuration files from the module's config directory.
     *
     * @return void
     */
    public function loadConfigs(): void
    {
        $configsCacheKey = "module.{$this->getModuleName()}.configs_loaded";
        if (isset($this->loadedStatus[$configsCacheKey])) {
            return;
        }

        $configDir = $this->getModulePath('Resources/config');
        if (!is_dir($configDir)) {
            return;
        }

        $cacheKey = "module.{$this->getModuleName()}.configs";
        $configFiles = cache()->callback($cacheKey, function () use ($configDir) {
            $finder = finder();
            $finder->files()->in($configDir)->name('*.php');

            $files = [];
            foreach ($finder as $file) {
                $files[] = [
                    'path' => $file->getPathname(),
                    'name' => basename($file->getFilename(), '.php'),
                ];
            }

            return $files;
        }, $this->defaultCacheDuration);

        if (!empty($configFiles)) {
            $configService = app(ConfigurationService::class);

            foreach ($configFiles as $file) {
                $configService->loadCustomConfig($file['path'], $file['name']);
            }

            $this->loadedStatus[$configsCacheKey] = true;
        }
    }

    /**
     * Load "page" views and automatically register routes
     * for index and subpage templates in the module's pages directory.
     *
     * @return void
     */
    public function loadViewPages(): void
    {
        $pagesDir = $this->getModulePath('Resources/pages');
        if (!is_dir($pagesDir)) {
            return;
        }

        $viewPagesCacheKey = "module.{$this->getModuleName()}.view_pages_loaded";
        if (isset($this->loadedStatus[$viewPagesCacheKey])) {
            return;
        }

        $namespace = $this->kebabCase($this->getModuleName());
        $this->loadViews('Resources/pages', $namespace);

        $this->recursivelyRegisterViewPages($pagesDir, '');
        $this->loadedStatus[$viewPagesCacheKey] = true;
    }

    /**
     * Recursively register view-based pages.
     *
     * @param string $directory
     * @param string $prefix
     * @return void
     */
    protected function recursivelyRegisterViewPages(string $directory, string $prefix): void
    {
        $cacheKey = "module.{$this->getModuleName()}.view_pages." . md5($directory . $prefix);

        $viewPageData = cache()->callback($cacheKey, function () use ($directory, $prefix) {
            $namespace = $this->kebabCase($this->getModuleName());
            $routes = [];

            foreach ($this->getFilesFromDirectory($directory) as $file) {
                $relativePath = ltrim(str_replace($this->getModulePath('Resources/pages'), '', $file->getPathname()), DIRECTORY_SEPARATOR);

                if ($file->isDir()) {
                    // Directory will be processed in a recursive call
                    continue;
                }

                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $filename = str_replace('.blade', '', pathinfo($file->getBasename(), PATHINFO_FILENAME));
                $routeUri = ltrim($prefix . ($filename === 'index' ? '' : '/' . $filename), '/');
                $viewPath = str_replace('.blade.php', '', $relativePath);

                $routes[] = [
                    'uri' => $routeUri,
                    'view' => $namespace . '::' . $viewPath,
                ];
            }

            // Get subdirectories for recursive processing
            $subDirs = [];
            foreach ($this->getFilesFromDirectory($directory) as $file) {
                if ($file->isDir()) {
                    $newPrefix = trim($prefix . '/' . $file->getBasename(), '/');
                    $subDirs[] = [
                        'path' => $file->getPathname(),
                        'prefix' => $newPrefix,
                    ];
                }
            }

            return [
                'routes' => $routes,
                'subDirs' => $subDirs,
            ];
        }, $this->defaultCacheDuration);

        // Register routes
        foreach ($viewPageData['routes'] as $route) {
            router()->view($route['uri'], $route['view']);
        }

        // Process subdirectories
        foreach ($viewPageData['subDirs'] as $subDir) {
            $this->recursivelyRegisterViewPages($subDir['path'], $subDir['prefix']);
        }
    }

    /**
     * Load routes from controllers that use attribute-based routing.
     *
     * @return void
     */
    public function loadRouterAttributes(): void
    {
        $attributesCacheKey = "module.{$this->getModuleName()}.router_attributes_loaded";
        if (isset($this->loadedStatus[$attributesCacheKey])) {
            return;
        }

        $controllersPath = $this->getModulePath('Controllers');

        if (!is_dir($controllersPath)) {
            return;
        }

        $moduleNamespace = "Flute\\Modules\\{$this->getModuleName()}\\Controllers";

        try {
            router()->registerAttributeRoutes([$controllersPath], $moduleNamespace);

            $submodulesPath = $this->getModulePath('Submodules');

            if (is_dir($submodulesPath)) {
                $cacheKey = "module.{$this->getModuleName()}.submodules";
                $submodules = cache()->callback($cacheKey, function () use ($submodulesPath) {
                    $result = [];
                    foreach ($this->getFilesFromDirectory($submodulesPath) as $submodule) {
                        if ($submodule->isDir()) {
                            $submoduleControllers = $submodule->getPathname() . '/Controllers';
                            if (is_dir($submoduleControllers)) {
                                $result[] = [
                                    'name' => $submodule->getBasename(),
                                    'controllers_path' => $submoduleControllers,
                                ];
                            }
                        }
                    }

                    return $result;
                }, $this->defaultCacheDuration);

                foreach ($submodules as $submodule) {
                    $submoduleNamespace = "Flute\\Modules\\{$this->getModuleName()}\\Submodules\\" . $submodule['name'] . "\\Controllers";
                    router()->registerAttributeRoutes([$submodule['controllers_path']], $submoduleNamespace);
                }
            }

            $this->loadedStatus[$attributesCacheKey] = true;
        } catch (\Exception $e) {
            logs()->error("Error loading route attributes in module {$this->getModuleName()}: " . $e->getMessage());
        }
    }

    /**
     * Load widget classes from the module's Widgets directory and register them.
     *
     * @return void
     */
    public function loadWidgets(): void
    {
        $widgetsCacheKey = "module.{$this->getModuleName()}.widgets_loaded";
        if (isset($this->loadedStatus[$widgetsCacheKey])) {
            return;
        }

        $widgetsDirectory = $this->getModulePath('Widgets');

        if (!is_dir($widgetsDirectory)) {
            return;
        }

        $moduleNamespace = "Flute\\Modules\\{$this->getModuleName()}\\Widgets";
        $widgets = [];

        $this->scanForWidgets($widgetsDirectory, $moduleNamespace, $widgets);

        if (!empty($widgets)) {
            $this->registerWidgets($widgets);
            $this->loadedStatus[$widgetsCacheKey] = true;
        }
    }

    /**
     * Register widget classes.
     *
     * @param array $widgets
     * @return void
     */
    public function registerWidgets(array $widgets): void
    {
        $widgetManager = app(WidgetManager::class);

        foreach ($widgets as $key => $widget) {
            try {
                $widgetManager->registerWidget($key, $widget);
            } catch (\Exception $e) {
                logs('modules')->error("Error registering widget {$key}: " . $e->getMessage());
            }
        }
    }

    /**
     * Recursively scan a directory for widget classes. Uses caching to reduce file scans.
     *
     * @param string $directory
     * @param string $namespace
     * @param array  $widgets
     * @return void
     */
    protected function scanForWidgets(string $directory, string $namespace, array &$widgets): void
    {
        $moduleName = $this->getModuleName();
        $cacheKey = "module.{$moduleName}.widgets." . md5($directory . $namespace);

        $cachedWidgets = cache()->get($cacheKey);
        if ($cachedWidgets !== null) {
            $widgets = array_merge($widgets, $cachedWidgets);

            return;
        }

        $foundWidgets = [];
        foreach ($this->getFilesFromDirectory($directory) as $file) {
            if ($file->isDir()) {
                $this->scanForWidgets($file->getPathname(), $namespace . '\\' . $file->getBasename(), $foundWidgets);

                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = pathinfo($file->getBasename(), PATHINFO_FILENAME);
            $fullyQualifiedClassName = $namespace . '\\' . $className;

            if (!class_exists($fullyQualifiedClassName)) {
                continue;
            }

            $foundWidgets[$className] = $fullyQualifiedClassName;
        }

        if (!empty($foundWidgets)) {
            cache()->set($cacheKey, $foundWidgets, $this->defaultCacheDuration);
        }

        $widgets = array_merge($widgets, $foundWidgets);
    }

    /**
     * Load a single widget by name and class.
     *
     * @param string $name
     * @param string $class
     * @return void
     */
    public function loadWidget(string $name, string $class)
    {
        try {
            if (!class_exists($class) && class_exists($name)) {
                [$name, $class] = [$class, $name];
            }

            app(WidgetManager::class)->registerWidget($name, $class);
        } catch (\Exception $e) {
            logs('modules')->error("Error registering widget {$name}: " . $e->getMessage());
        }
    }

    /**
     * Load and compile SCSS file from the module.
     *
     * @param string $assetsFile
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function loadScss(string $assetsFile): void
    {
        $fullPath = $this->getModulePath($assetsFile);

        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("Assets file does not exist: {$fullPath}");
        }

        $templateAssets = app(TemplateAssets::class);
        $templateAssets->addScssFile($fullPath, 'main');
        $templateAssets->addImportPath(dirname($fullPath), 'main');
    }

    /**
     * Load views from a specific sub-directory with a given namespace.
     *
     * @param string $viewDirectory
     * @param string $namespace
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function loadViews(string $viewDirectory, string $namespace): void
    {
        $viewsCacheKey = "module.{$this->getModuleName()}.views_{$namespace}_loaded";
        if (isset($this->loadedStatus[$viewsCacheKey])) {
            return;
        }

        $fullPath = $this->getModulePath($viewDirectory);

        if (!is_dir($fullPath)) {
            throw new \InvalidArgumentException("View directory does not exist: {$fullPath}");
        }

        $this->namespace = $namespace;
        template()->addNamespace($namespace, $fullPath);
        $this->loadedStatus[$viewsCacheKey] = true;
    }

    /**
     * Get a file path relative to the module's directory.
     *
     * @param string $path
     * @return string
     */
    protected function getModulePath(string $path = ''): string
    {
        $cacheKey = $this->getModuleName() . ($path ? '_' . md5($path) : '');

        if (isset($this->modulePathCache[$cacheKey])) {
            return $this->modulePathCache[$cacheKey];
        }

        $result = path("app/Modules/{$this->getModuleName()}") . ($path ? '/' . $path : '');
        $this->modulePathCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Load and register all Live/Blade components found in the module's Components directory.
     *
     * @return void
     */
    public function loadComponents(): void
    {
        $componentsCacheKey = "module.{$this->getModuleName()}.components_loaded";
        if (isset($this->loadedStatus[$componentsCacheKey])) {
            return;
        }

        $componentsDirectory = $this->getModulePath('Components');

        if (!is_dir($componentsDirectory)) {
            return;
        }

        $moduleNamespace = "Flute\\Modules\\{$this->getModuleName()}\\Components";

        $components = [];

        $this->scanForComponents($componentsDirectory, $moduleNamespace, $components);

        if (!empty($components)) {
            $this->registerComponents($components);
            $this->loadedStatus[$componentsCacheKey] = true;
        }
    }

    /**
     * Recursively scan a directory for component classes. Uses caching to reduce file scans.
     *
     * @param string $directory
     * @param string $namespace
     * @param array  $components
     * @return void
     */
    protected function scanForComponents(string $directory, string $namespace, array &$components): void
    {
        $moduleName = $this->getModuleName();
        $cacheKey = "module.{$moduleName}.components." . md5($directory . $namespace);

        $cachedComponents = cache()->get($cacheKey);
        if ($cachedComponents !== null) {
            $components = array_merge($components, $cachedComponents);

            return;
        }

        $foundComponents = [];
        foreach ($this->getFilesFromDirectory($directory) as $file) {
            if ($file->isDir()) {
                $this->scanForComponents($file->getPathname(), $namespace . '\\' . $file->getBasename(), $foundComponents);

                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = pathinfo($file->getBasename(), PATHINFO_FILENAME);
            $fullyQualifiedClassName = $namespace . '\\' . $className;

            if (!class_exists($fullyQualifiedClassName)) {
                continue;
            }

            $componentName = $this->kebabCase($className);
            $foundComponents[$componentName] = $fullyQualifiedClassName;
        }

        if (!empty($foundComponents)) {
            cache()->set($cacheKey, $foundComponents, $this->defaultCacheDuration);
        }

        $components = array_merge($components, $foundComponents);
    }

    /**
     * Convert a string to kebab-case format (e.g., "MyExample" -> "my-example").
     *
     * @param string $string
     * @return string
     */
    protected function kebabCase(string $string): string
    {
        $string = preg_replace('/Component$/', '', $string);

        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }

    /**
     * Register components (e.g., Livewire or Yoyo) globally.
     *
     * @param array $components
     * @return void
     */
    public function registerComponents(array $components): void
    {
        if (class_exists('Clickfwd\\Yoyo\\Yoyo')) {
            \Clickfwd\Yoyo\Yoyo::registerComponents($components);
        }
    }

    /**
     * Bootstrap module by loading configs, translations, views, routes, entities, widgets, etc.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function bootstrapModule(): void
    {
        $bootstrapCacheKey = "module.{$this->getModuleName()}.bootstrapped";
        if (isset($this->loadedStatus[$bootstrapCacheKey])) {
            return;
        }

        try {
            $this->loadEntities();
            $this->loadConfigs();
            $this->loadTranslations();
            $this->loadViewPages();
            $this->loadRouterAttributes();
            $this->loadComponents();
            $this->loadWidgets();

            $this->loadedStatus[$bootstrapCacheKey] = true;
        } catch (\Exception $e) {
            logs('modules')->error("Error bootstrapping module {$this->getModuleName()}: " . $e->getMessage());

            if (is_debug()) {
                throw $e;
            }
        }
    }

    /**
     * Register services with the container (if needed).
     *
     * @param \DI\Container $container
     * @return void
     */
    public function register(\DI\Container $container): void
    {
    }

    /**
     * Boot the module (called after registration).
     *
     * @param \DI\Container $container
     * @return void
     */
    public function boot(\DI\Container $container): void
    {
        $this->bootstrapModule();
    }

    /**
     * Clear cached files/directories and also clear component/widget cache.
     *
     * @return void
     */
    public function clearFileCache(): void
    {
        $moduleName = $this->getModuleName();

        $this->directoryCache = [];
        $this->modulePathCache = [];
        $this->loadedStatus = [];

        $cachePatternFiles = "module.{$moduleName}.files.*";
        $fileKeys = cache()->getKeys($cachePatternFiles);
        if (!empty($fileKeys)) {
            foreach ($fileKeys as $key) {
                cache()->delete($key);
            }
        } else {
            $commonDirs = [
                'Controllers',
                'Components',
                'Widgets',
                'Resources/pages',
                'Resources/lang',
                'Resources/config',
                'Submodules',
            ];
            foreach ($commonDirs as $dir) {
                $dirPath = $this->getModulePath($dir);
                if (is_dir($dirPath)) {
                    $cacheKey = "module.{$moduleName}.files." . md5($dirPath);
                    cache()->delete($cacheKey);
                }
            }
        }

        $directoryCachePattern = "module.{$moduleName}.directory.*";
        $directoryKeys = cache()->getKeys($directoryCachePattern);
        if (!empty($directoryKeys)) {
            foreach ($directoryKeys as $key) {
                cache()->delete($key);
            }
        }

        $cachePatternComponents = "module.{$moduleName}.components.*";
        $componentsKeys = cache()->getKeys($cachePatternComponents);
        if (!empty($componentsKeys)) {
            foreach ($componentsKeys as $key) {
                cache()->delete($key);
            }
        }

        $cachePatternWidgets = "module.{$moduleName}.widgets.*";
        $widgetsKeys = cache()->getKeys($cachePatternWidgets);
        if (!empty($widgetsKeys)) {
            foreach ($widgetsKeys as $key) {
                cache()->delete($key);
            }
        }

        cache()->delete("module.{$moduleName}.submodules");

        $viewPagesPattern = "module.{$moduleName}.view_pages.*";
        $viewPagesKeys = cache()->getKeys($viewPagesPattern);
        if (!empty($viewPagesKeys)) {
            foreach ($viewPagesKeys as $key) {
                cache()->delete($key);
            }
        }

        cache()->delete("module.{$moduleName}.entities_dir");
        cache()->delete("module.{$moduleName}.translations");
        cache()->delete("module.{$moduleName}.configs");
    }

    /**
     * Refresh (invalidate) the cache for a specific directory and then retrieve fresh data.
     *
     * @param string $directory
     * @return SplFileInfo[]
     */
    public function refreshDirectoryCache(string $directory): array
    {
        $moduleName = $this->getModuleName();
        $cacheKey = "module.{$moduleName}.files." . md5($directory);
        cache()->delete($cacheKey);

        unset($this->directoryCache[md5($directory)]);

        return $this->getFilesFromDirectory($directory);
    }

    /**
     * Get an array of SplFileInfo objects from a directory,
     * utilizing cache to avoid scanning the same directory repeatedly.
     *
     * @param string    $directory
     * @param int|null  $cacheDuration
     * @return SplFileInfo[]
     */
    protected function getFilesFromDirectory(string $directory, ?int $cacheDuration = null): array
    {
        $dirHash = md5($directory);

        if (isset($this->directoryCache[$dirHash])) {
            return $this->directoryCache[$dirHash];
        }

        $moduleName = $this->getModuleName();
        $cacheKey = "module.{$moduleName}.files." . $dirHash;
        $duration = $cacheDuration ?? $this->defaultCacheDuration;

        $filePaths = cache()->callback($cacheKey, function () use ($directory) {
            if (!is_dir($directory)) {
                return [];
            }

            $paths = [];
            $finder = finder()->files()->in($directory);

            foreach ($finder as $file) {
                $paths[] = $file->getPathname();
            }

            return $paths;
        }, $duration);

        $result = array_map(fn ($path) => new SplFileInfo($path), $filePaths);

        $this->directoryCache[$dirHash] = $result;

        return $result;
    }
}
