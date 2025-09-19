<?php

namespace Flute\Core\Template;

use Clickfwd\Yoyo\Blade\YoyoServiceProvider;
use Clickfwd\Yoyo\ViewProviders\BladeViewProvider;
use Clickfwd\Yoyo\Yoyo;
use Exception;
use Flute\Core\Events\AfterRenderEvent;
use Flute\Core\Events\BeforeRenderEvent;
use Flute\Core\Modules\Icons\Components\IconComponent;
use Flute\Core\Modules\Icons\Services\IconFinder;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Template\Contracts\ViewServiceInterface;
use Flute\Core\Template\Controllers\YoyoController;
use Flute\Core\Template\Events\TemplateInitialized;
use Flute\Core\Theme\ThemeManager;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;
use Jenssegers\Blade\Blade;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;

/**
 * The Template class provides an interface to the Blade templating engine.
 */
class Template extends AbstractTemplateInstance implements ViewServiceInterface
{
    protected const LIVE_COMPONENT_PATH = '/live';

    protected const LIVE_COMPONENT_ADMIN_PATH = '/admin/live';

    protected ?string $currentTheme = null;

    protected array $themeData = [];

    protected Blade $blade;

    protected TemplateAssets $templateAssets;

    protected string $viewsPath;

    protected string $cachePath;

    protected array $assetAliases = [
        'animate' => '/assets/css/libs/animate.min.css',
        'montserrat' => '/assets/fonts/montserrat/montserrat.css',
        'grid' => '/assets/css/libs/bootstrap-grid.min.css',
        'jquery' => '/assets/js/libs/jquery.js',
        'floating' => '/assets/js/libs/floating.js',
    ];

    protected FluteBladeApplication $fluteBladeApp;

    protected Yoyo $yoyo;

    protected RouterInterface $router;

    protected array $globals = [];

    protected ThemeManager $themeManager;

    protected array $sectionPushes = [];

    protected static self $instance;

    protected array $componentCache = [];

    protected array $pathCache = [];

    protected array $fallbackPaths = [];

    protected string $standardTheme = 'standard';

    protected array $loadedStyles = [];

    protected array $loadedScripts = [];

    /**
     * Cache size limits to prevent memory leaks
     */
    protected int $maxComponentCacheSize = 500;

    protected int $maxPathCacheSize = 1000;

    /**
     * Create a new Template instance.
     *
     * @param TemplateAssets   $templateAssets The TemplateAssets instance.
     * @param RouterInterface  $router         The RouterInterface instance.
     * @param ThemeManager     $themeManager   The ThemeManager instance.
     * @param string|null      $viewsPath      The path to the views directory.
     * @param string|null      $cachePath      The path to the cache directory.
     */
    public function __construct(TemplateAssets $templateAssets, RouterInterface $router, ThemeManager $themeManager, ?string $viewsPath = null, ?string $cachePath = null)
    {
        $this->templateAssets = $templateAssets;
        $this->router = $router;
        $this->themeManager = $themeManager;

        $this->viewsPath = $viewsPath ?? path('app');
        $this->cachePath = $cachePath ?? path('storage/app/views');

        $this->initTheme();

        $this->setupBlade();
        $this->addTranslateDirective();

        $this->templateAssets->init($this, $this->isAdminPath() ? 'admin' : 'main');

        $this->loadComponents();
    }

    /**
     * Set the current theme.
     *
     * @param string $themeName The name of the theme to set.
     * @throws RuntimeException
     */
    public function setTheme(string $themeName): void
    {
        try {
            $this->themeManager->setTheme($themeName);
            $this->currentTheme = $this->themeManager->getCurrentTheme();
            $this->themeData = $this->themeManager->getThemeData($this->currentTheme) ?? [];

            $this->clearThemeCache();

            $this->loadComponents();

        } catch (Exception $e) {
            logs('templates')->error("Failed to set theme '{$themeName}': " . $e->getMessage());
            $this->fallbackToDefaultTheme();
        }
    }

    /**
     * Get the Yoyo instance.
     */
    public function getYoyo(): Yoyo
    {
        return $this->yoyo;
    }

    /**
     * Set the Yoyo route for live components.
     */
    public function setYoyoRoute(): void
    {
        try {
            $path = $this->isAdminPath() ? self::LIVE_COMPONENT_ADMIN_PATH : self::LIVE_COMPONENT_PATH;
            $this->router->any($path, [YoyoController::class, 'handle'])->middleware(['web', 'csrf'])->name('yoyo.update');
        } catch (Exception $e) {
            logs()->error("Exception while registering Yoyo route: " . $e->getMessage());
            if (is_debug()) {
                throw $e;
            }
        }
    }

    /**
     * Add a namespace for Blade views.
     *
     * @param string       $namespace
     * @param array|string $hints
     */
    public function addNamespace($namespace, $hints): self
    {
        $this->blade->addNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Get the Blade instance.
     */
    public function getBlade(): Blade
    {
        return $this->blade;
    }

    /**
     * Returns the asset path from the aliases or a standard path.
     *
     * @param string $assetKey The key or path to the asset.
     * @return string The full asset URL.
     */
    public function getAsset(string $assetKey): string
    {
        $assetPath = $this->assetAliases[$assetKey] ?? trim($assetKey, '/');

        return url($assetPath)->get();
    }

    /**
     * Render a Blade template with enhanced replacement system.
     *
     * @param array  $mergeData
     */
    public function render(string $template, array $context = [], $mergeData = []): View
    {
        if (!empty($this->themeData['layout_arguments'])) {
            $this->blade->share($this->themeData['layout_arguments']);
        }

        return $this->runTemplate($template, $context, $mergeData);
    }

    /**
     * Render an error template with the given variables.
     *
     * @param int   $errorCode The HTTP error code to render.
     * @param array $variables The variables to pass to the error template.
     * @throws Exception
     * @return View The rendered error template.
     */
    public function renderError(int $errorCode, array $variables = []): View
    {
        $hint = (!is_installed()) ? 'installer' : ($this->isAdminPath() ? 'admin' : 'flute');

        return $this->render("{$hint}::pages.error", array_merge(['code' => $errorCode], $variables));
    }

    /**
     * Add a custom directive to Blade.
     *
     * @param string   $name     The name of the directive.
     * @param callable $function The function to add.
     */
    public function addDirective(string $name, callable $function): void
    {
        $this->blade->directive($name, $function);
    }

    /**
     * Get the full path to the given template.
     *
     * @param string $filename The name of the template file.
     * @return string The full path to the template.
     */
    public function getTemplatePath(string $filename): string
    {
        return sprintf('%s/%s', $this->viewsPath, $filename);
    }

    /**
     * Render a Blade template from a string.
     *
     * @param string $html   The Blade template content.
     * @param array  $params The Blade template data.
     *
     * @throws Exception
     * @return string The rendered content.
     */
    public function runString(string $html, array $params = []): string
    {
        return Yoyo::getViewProvider()->getProviderInstance()->compiler()->renderString($html, $params);
    }

    /**
     * Add a stylesheet to the header stack.
     *
     * @param string $css The URL of the CSS file.
     */
    public function addStyle(string $css): void
    {
        if ($this->loadedStyles[$css] ?? false) {
            return;
        }

        $this->prependToSection('head', sprintf("<link rel='stylesheet' href='%s' type='text/css'>", $css));
        $this->loadedStyles[$css] = true;
    }

    /**
     * Prepend content to a Blade section.
     *
     * @param string $section The name of the section.
     * @param string $content The content to prepend.
     */
    public function prependToSection(string $section, string $content): void
    {
        $this->sectionPushes[$section][] = $content;
    }

    /**
     * Render content and add to section immediately.
     *
     * @param string $section The name of the section.
     * @param callable $callback A callback that returns the content when called.
     */
    public function prependToSectionDeferred(string $section, callable $callback): void
    {
        try {
            $content = $callback();
            $this->prependToSection($section, $content);
        } catch (Exception $e) {
            logs('templates')->error("Error rendering section '{$section}': " . $e->getMessage());
        }
    }

    /**
     * Prepend a template render to a section with basic optimization.
     *
     * @param string $section The name of the section.
     * @param string $template The template to render.
     * @param array $data The data to pass to the template.
     */
    public function prependTemplateToSection(string $section, string $template, array $data = []): void
    {
        if (!$this->shouldRenderSection($section)) {
            return;
        }

        $this->prependToSectionDeferred($section, function () use ($template, $data) {
            try {
                return $this->render($template, $data)->render();
            } catch (Exception $e) {
                logs('templates')->error("Error rendering template '{$template}': " . $e->getMessage());

                return '';
            }
        });
    }

    /**
     * Prepend a Yoyo component to a section with lazy loading.
     *
     * @param string $section The name of the section.
     * @param string $component The Yoyo component name.
     * @param array $data The data to pass to the component.
     */
    public function prependYoyoToSection(string $section, string $component, array $data = []): void
    {
        $this->prependToSectionDeferred($section, static function () use ($component, $data) {
            try {
                return \Yoyo\yoyo_render($component, $data);
            } catch (Exception $e) {
                logs('templates')->error("Error rendering Yoyo component '{$component}': " . $e->getMessage());

                return '';
            }
        });
    }

    /**
     * Check if a section should be rendered based on current context.
     *
     * @param string $section The section name to check.
     * @return bool Whether the section should be rendered.
     */
    public function shouldRenderSection(string $section): bool
    {
        $path = request()->getPathInfo();

        if (strpos($section, 'profile_') === 0 && !str_contains((string)$path, '/profile')) {
            return false;
        }

        return !(strpos($section, 'navbar') === 0 && is_admin_path())



        ;
    }

    /**
     * Flush the content of a section.
     */
    public function flushSectionPushes(): void
    {
        foreach ($this->sectionPushes as $section => $content) {
            $this->blade->startPush($section);

            foreach ($content as $item) {
                echo $item;
            }

            $this->blade->stopPush();
        }
    }

    /**
     * Add an inline script to the footer stack.
     *
     * @param string $scriptContent The script content.
     */
    public function addInlineScript(string $scriptContent): void
    {
        $this->prependToSection('footer', sprintf("<script>%s</script>", $scriptContent));
    }

    /**
     * Add a script to the footer stack.
     *
     * @param string $js The URL of the JavaScript file.
     */
    public function addScript(string $js): void
    {
        if ($this->loadedScripts[$js] ?? false) {
            return;
        }

        $this->prependToSection('footer', sprintf("<script src='%s' defer></script>", $js));
        $this->loadedScripts[$js] = true;
    }

    /**
     * Get the TemplateAssets instance.
     */
    public function getTemplateAssets(): TemplateAssets
    {
        return $this->templateAssets;
    }

    /**
     * Register a Yoyo component.
     *
     * @param string $name      The component name.
     * @param mixed  $component The component class or closure.
     */
    public function registerComponent(string $name, $component = null): void
    {
        $this->yoyo->registerComponent($name, $component);
    }

    /**
     * Add a global variable to the Blade instance.
     *
     * @param string $name  The name of the global variable.
     * @param mixed  $value The value of the global variable.
     */
    public function addGlobal(string $name, $value): void
    {
        $this->globals[$name] = $value;
        $this->blade->share($name, $value);
    }

    /**
     * Add an error to the global errors bag.
     *
     * @param string $input The input name.
     * @param string $error The error message.
     */
    public function addError(string $input, string $error): void
    {
        if (!isset($this->globals['errors'])) {
            $this->globals['errors'] = new ViewErrorBag();
        }

        $bag = $this->globals['errors']->getBag('default') ?? new \Illuminate\Support\MessageBag();
        $bag->add($input, $error);
        $this->globals['errors']->put('default', $bag);

        $this->blade->share('errors', $this->globals['errors']);
    }

    /**
     * Get a global variable.
     *
     * @param string $name The name of the global variable.
     * @return mixed The value of the global variable.
     */
    public function getGlobal(string $name)
    {
        return $this->globals[$name] ?? null;
    }

    /**
     * Get all Blade files from a directory.
     *
     * @param string $dir The directory path.
     * @return array The list of Blade file paths.
     */
    public function getBladeFiles(string $dir): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strpos($file->getFilename(), '.blade.php') !== false) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Get template rendering statistics for debugging.
     */
    public function getRenderStats(): array
    {
        return [
            'section_pushes' => count($this->sectionPushes),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'component_cache_size' => count($this->componentCache),
            'path_cache_size' => count($this->pathCache),
            'current_theme' => $this->currentTheme,
            'fallback_themes' => $this->getThemeFallbackOrder(),
        ];
    }

    /**
     * Clear theme-related cache.
     */
    protected function clearThemeCache(): void
    {
        $this->componentCache = [];
        $this->pathCache = [];
        $this->fallbackPaths = [];
    }

    /**
     * Add item to path cache with size limit
     *
     * @param string $key Cache key
     * @param mixed $value Cache value
     */
    protected function addToPathCache(string $key, $value): void
    {
        if (count($this->pathCache) >= $this->maxPathCacheSize) {
            array_shift($this->pathCache);
        }
        $this->pathCache[$key] = $value;
    }

    /**
     * Add item to component cache with size limit
     *
     * @param string $key Cache key
     * @param mixed $value Cache value
     */
    protected function addToComponentCache(string $key, $value): void
    {
        if (count($this->componentCache) >= $this->maxComponentCacheSize) {
            array_shift($this->componentCache);
        }
        $this->componentCache[$key] = $value;
    }

    /**
     * Find file with fallback to standard theme.
     *
     * @param string $relativePath Relative path from theme directory
     * @param string $type File type (views, assets, etc.)
     * @return string|null Found file path or null
     */
    protected function findFileWithFallback(string $relativePath, string $type = 'views'): ?string
    {
        $cacheKey = "{$type}:{$relativePath}";

        if (isset($this->pathCache[$cacheKey])) {
            return $this->pathCache[$cacheKey];
        }

        $currentThemePath = $this->getTemplatePath("Themes/{$this->currentTheme}/{$type}/{$relativePath}");
        if (file_exists($currentThemePath)) {
            $this->addToPathCache($cacheKey, $currentThemePath);

            return $currentThemePath;
        }

        if ($this->currentTheme !== $this->standardTheme) {
            $standardThemePath = $this->getTemplatePath("Themes/{$this->standardTheme}/{$type}/{$relativePath}");
            if (file_exists($standardThemePath)) {
                $this->addToPathCache($cacheKey, $standardThemePath);

                return $standardThemePath;
            }
        }

        $this->addToPathCache($cacheKey, null);

        return null;
    }

    /**
     * Get all available themes for fallback resolution.
     */
    protected function getThemeFallbackOrder(): array
    {
        return ThemeFallbackResolver::getThemeHierarchy($this->currentTheme, $this->standardTheme);
    }

    /**
     * Initialize the theme if not already set.
     */
    protected function initTheme(): void
    {
        try {
            $this->currentTheme = $this->themeManager->getCurrentTheme();
            $this->themeData = $this->themeManager->getThemeData($this->currentTheme) ?? [];

            if (empty($this->themeData)) {
                $this->fallbackToDefaultTheme();
            }

            app()->setTheme($this->currentTheme);
        } catch (Exception $e) {
            logs('templates')->error("Failed to initialize theme: " . $e->getMessage());
            $this->fallbackToDefaultTheme();
        }
    }

    /**
     * Fallback to the default theme if current theme is invalid.
     */
    protected function fallbackToDefaultTheme(): void
    {
        $defaultTheme = ThemeManager::DEFAULT_THEME;

        try {
            $this->themeManager->setTheme($defaultTheme);
            $this->currentTheme = $this->themeManager->getCurrentTheme();
            $this->themeData = $this->themeManager->getThemeData($this->currentTheme) ?? [];
            logs('templates')->warning("Fallback to default theme '{$defaultTheme}' due to invalid current theme.");
        } catch (Exception $e) {
            throw new RuntimeException("Default theme '{$defaultTheme}' is not available. " . $e->getMessage());
        }
    }

    /**
     * Run the template rendering process.
     *
     * @param string $path       The template path.
     * @param array  $variables  The variables to pass to the template.
     * @param array  $mergeData  Additional data to merge.
     */
    protected function runTemplate(string $path, array $variables, array $mergeData = []): View
    {
        $startRender = microtime(true);
        $path = $this->searchReplacementForInterface($path);

        $params = $this->beforeRenderEvent($path, $variables);

        try {
            $content = $this->blade->make($params->view, $params->variables, $mergeData);
        } catch (Throwable $e) {
            $root = $e;
            while ($root->getPrevious() !== null) {
                $root = $root->getPrevious();
            }

            if ($root !== $e) {
                throw $root;
            }

            throw $e;
        }

        $elapsed = microtime(true) - $startRender;

        \Flute\Core\Template\TemplateRenderTiming::add($params->view, $elapsed);

        return $this->afterRenderEvent($content);
    }

    /**
     * Dispatch the before-render event and return the view and variables.
     *
     * @param string $template  The name of the template.
     * @param array  $variables The variables to pass to the template.
     * @return object An object containing the view and variables.
     */
    protected function beforeRenderEvent(string $template, array $variables = []): object
    {
        $event = events()->dispatch(new BeforeRenderEvent($template, $variables), BeforeRenderEvent::NAME);

        return (object) [
            'view' => $event->getView(),
            'variables' => $event->getData() ?? [],
        ];
    }

    /**
     * After render event processor
     *
     * @param View $view The view object
     */
    protected function afterRenderEvent(View $view): View
    {
        $event = new AfterRenderEvent($view);

        return events()->dispatch($event, AfterRenderEvent::NAME)->getView();
    }

    /**
     * Set up the Blade instance with the appropriate configuration.
     */
    protected function setupBlade(): void
    {
        $this->fluteBladeApp = FluteBladeApplication::getInstance();

        $this->fluteBladeApp->bind(\Illuminate\Contracts\Foundation\Application::class, FluteBladeApplication::class);

        $this->fluteBladeApp->alias('view', ViewFactory::class);

        $this->blade = new Blade(
            $this->viewsPath,
            $this->cachePath,
            $this->fluteBladeApp
        );

        // Custom Blade conditionals
        $this->blade->if('auth', static fn () => user()->isLoggedIn());

        $this->blade->if('guest', static fn () => !user()->isLoggedIn());

        $this->blade->if('can', static fn ($ability, $arguments = []) => user()->can($ability));

        $this->blade->if('cannot', static fn ($ability, $arguments = []) => !user()->can($ability));

        $this->blade->directive('asset', static fn ($expression) => "<?php echo asset({$expression}); ?>");

        $this->blade->directive('lang', static fn ($expression) => "<?php echo __({$expression}); ?>");

        $this->addGlobal('app', app());

        $this->fluteBladeApp->bind('view', fn () => $this->blade);

        // @php-ignore
        (new YoyoServiceProvider($this->fluteBladeApp))->boot();

        $this->yoyo = new Yoyo($this->fluteBladeApp);

        $this->yoyo->configure([
            'url' => url($this->isAdminPath() ? self::LIVE_COMPONENT_ADMIN_PATH : self::LIVE_COMPONENT_PATH),
            'scriptsPath' => url('assets/js/htmx/'),
            'historyEnabled' => true, // Enables HTMX history push state
        ]);

        $this->yoyo->registerViewProvider(fn () => new BladeViewProvider($this->blade));

        if (!$this->getGlobal('errors')) {
            $this->addGlobal('errors', new ViewErrorBag());
        }

        Yoyo::getViewProvider()->getProviderInstance()->composer('*', function ($view) {
            $sections = [];

            foreach ($this->sectionPushes as $section => $contents) {
                $sections[$section] = implode('', $contents);
            }

            $view->with('sections', $sections);
        });

        app()->bind('flute.view.engine', $this->getBlade());

        $this->addNamespace('flute-icons', path('app/Core/Modules/Icons/Views'));

        $this->blade->compiler()->component(IconComponent::class, 'icon');

        $iconFinder = app()->get(IconFinder::class);

        $iconFinder->registerIconDirectory('fa', storage_path('app/icons/fontawesome'));
        $iconFinder->registerIconDirectory('ph', storage_path('app/icons/phosphoricons'));

        events()->dispatch(new TemplateInitialized($this), TemplateInitialized::NAME);

        // Set Yoyo route after Yoyo is fully initialized
        $this->setYoyoRoute();
    }

    /**
     * Add a translation directive to Blade.
     */
    protected function addTranslateDirective(): void
    {
        $this->blade->directive('t', static fn ($expression) => "<?php echo __({$expression}); ?>");
    }

    /**
     * Load and register Blade components with fallback support.
     */
    protected function loadComponents(): void
    {
        if ($this->isAdminPath() || !is_installed()) {
            return;
        }

        $cacheKey = "components:{$this->currentTheme}";

        if (isset($this->componentCache[$cacheKey])) {
            $this->registerCachedComponents($this->componentCache[$cacheKey]);

            return;
        }

        $components = [];
        $themes = $this->getThemeFallbackOrder();

        foreach ($themes as $theme) {
            $componentsDir = $this->getTemplatePath("Themes/{$theme}/views/components");

            if (is_dir($componentsDir)) {
                $themeComponents = $this->discoverComponents($componentsDir, $theme);

                foreach ($themeComponents as $alias => $componentData) {
                    if (!isset($components[$alias])) {
                        $components[$alias] = $componentData;
                    }
                }
            }
        }

        $this->addToComponentCache($cacheKey, $components);
        $this->registerCachedComponents($components);

        $this->setupThemeNamespaces();
    }

    /**
     * Discover components in a theme directory.
     */
    protected function discoverComponents(string $componentsDir, string $theme): array
    {
        $components = [];
        $componentFiles = $this->getBladeFiles($componentsDir);

        foreach ($componentFiles as $componentFile) {
            $relativePath = str_replace([$componentsDir . DIRECTORY_SEPARATOR, '.blade.php'], '', $componentFile);
            $alias = str_replace(DIRECTORY_SEPARATOR, '.', $relativePath);
            $componentView = "Themes.{$theme}.views.components." . $alias;

            $components[$alias] = [
                'view' => $componentView,
                'theme' => $theme,
                'path' => $componentFile,
            ];
        }

        return $components;
    }

    /**
     * Register cached components.
     */
    protected function registerCachedComponents(array $components): void
    {
        foreach ($components as $alias => $componentData) {
            $this->blade->compiler()->component($componentData['view'], $alias);
        }
    }

    /**
     * Setup theme namespaces with fallback support.
     */
    protected function setupThemeNamespaces(): void
    {
        $themes = $this->getThemeFallbackOrder();
        $viewPaths = [];

        foreach ($themes as $theme) {
            $themePath = $this->getTemplatePath("Themes/{$theme}/views");
            if (is_dir($themePath)) {
                $viewPaths[] = $themePath;
            }
        }

        if (!empty($viewPaths)) {
            $this->addNamespace('flute', $viewPaths);
        }

        foreach ($themes as $theme) {
            $sassPath = $this->getTemplatePath("Themes/{$theme}/assets/sass");
            if (is_dir($sassPath)) {
                $this->templateAssets->addImportPath($sassPath);
            }
        }
    }

    /**
     * Resolve the given path to a Blade view name.
     *
     * @param string $path The path to the template.
     * @return string The Blade view name.
     */
    protected function resolveTemplatePath(string $path): string
    {
        return str_replace(['.blade.php', '/'], ['', '.'], $path);
    }

    /**
     * Enhanced search for replacement based on theme configuration.
     */
    protected function searchReplacementForInterface(string $interfacePath): string
    {
        $replacements = $this->themeData['replacements'] ?? [];
        if (isset($replacements[$interfacePath])) {
            return $replacements[$interfacePath];
        }

        $moduleReplacements = $this->themeData['module_replacements'] ?? [];
        foreach ($moduleReplacements as $pattern => $replacement) {
            if (preg_match($pattern, $interfacePath)) {
                return preg_replace($pattern, $replacement, $interfacePath);
            }
        }

        $wildcardReplacements = $this->themeData['wildcard_replacements'] ?? [];
        foreach ($wildcardReplacements as $pattern => $replacement) {
            if (fnmatch($pattern, $interfacePath)) {
                return str_replace('*', basename($interfacePath), $replacement);
            }
        }

        return $interfacePath;
    }

    protected function isAdminPath(): bool
    {
        return is_admin_path() && user()->can('admin');
    }
}
