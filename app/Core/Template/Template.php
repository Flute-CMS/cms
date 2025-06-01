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
use RuntimeException;

/**
 * The Template class provides an interface to the Blade templating engine.
 */
class Template extends AbstractTemplateInstance implements ViewServiceInterface
{
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
    protected const LIVE_COMPONENT_PATH = '/live';
    protected const LIVE_COMPONENT_ADMIN_PATH = '/admin/live';
    protected array $globals = [];
    protected ThemeManager $themeManager;
    protected array $sectionPushes = [];
    protected static self $instance;

    /**
     * Create a new Template instance.
     *
     * @param TemplateAssets   $templateAssets The TemplateAssets instance.
     * @param RouterInterface  $router         The RouterInterface instance.
     * @param ThemeManager     $themeManager   The ThemeManager instance.
     * @param string|null      $viewsPath      The path to the views directory.
     * @param string|null      $cachePath      The path to the cache directory.
     */
    public function __construct(TemplateAssets $templateAssets, RouterInterface $router, ThemeManager $themeManager, string $viewsPath = null, string $cachePath = null)
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
     * @return void
     * @throws RuntimeException
     */
    public function setTheme(string $themeName) : void
    {
        try {
            $this->themeManager->setTheme($themeName);
            $this->currentTheme = $this->themeManager->getCurrentTheme();
            $this->themeData = $this->themeManager->getThemeData($this->currentTheme) ?? [];

            $this->loadComponents();
        } catch (Exception $e) {
            logs('templates')->error("Failed to set theme '{$themeName}': ".$e->getMessage());
            $this->fallbackToDefaultTheme();
        }
    }

    /**
     * Get the Yoyo instance.
     *
     * @return Yoyo
     */
    public function getYoyo() : Yoyo
    {
        return $this->yoyo;
    }

    /**
     * Set the Yoyo route for live components.
     *
     * @return void
     */
    public function setYoyoRoute() : void
    {
        try {
            $path = $this->isAdminPath() ? self::LIVE_COMPONENT_ADMIN_PATH : self::LIVE_COMPONENT_PATH;
            $this->router->any($path, [YoyoController::class, 'handle'])->middleware(['web', 'csrf'])->name('yoyo.update');
        } catch (Exception $e) {
            logs()->error("Exception while registering Yoyo route: ".$e->getMessage());
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
     * @return self
     */
    public function addNamespace($namespace, $hints) : self
    {
        $this->blade->addNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Get the Blade instance.
     *
     * @return Blade
     */
    public function getBlade() : Blade
    {
        return $this->blade;
    }

    /**
     * Returns the asset path from the aliases or a standard path.
     *
     * @param string $assetKey The key or path to the asset.
     * @return string The full asset URL.
     */
    public function getAsset(string $assetKey) : string
    {
        $assetPath = $this->assetAliases[$assetKey] ?? trim($assetKey, '/');
        return url($assetPath)->get();
    }

    /**
     * Render a Blade template.
     *
     * @param string $template
     * @param array  $context
     * @param array  $mergeData
     * @return View
     */
    public function render(string $template, array $context = [], $mergeData = []) : View
    {
        if (! empty($this->themeData['layout_arguments'])) {
            $this->blade->share($this->themeData['layout_arguments']);
        }

        return $this->runTemplate($template, $context, $mergeData);
    }

    /**
     * Render an error template with the given variables.
     *
     * @param int   $errorCode The HTTP error code to render.
     * @param array $variables The variables to pass to the error template.
     * @return View The rendered error template.
     * @throws Exception
     */
    public function renderError(int $errorCode, array $variables = []) : View
    {
        $hint = (! is_installed()) ? 'installer' : ($this->isAdminPath() ? 'admin' : 'flute');
        return $this->render("{$hint}::pages.error", array_merge(['code' => $errorCode], $variables));
    }

    /**
     * Add a custom directive to Blade.
     *
     * @param string   $name     The name of the directive.
     * @param callable $function The function to add.
     * @return void
     */
    public function addDirective(string $name, callable $function) : void
    {
        $this->blade->directive($name, $function);
    }

    /**
     * Get the full path to the given template.
     *
     * @param string $filename The name of the template file.
     * @return string The full path to the template.
     */
    public function getTemplatePath(string $filename) : string
    {
        return sprintf('%s/%s', $this->viewsPath, $filename);
    }

    /**
     * Render a Blade template from a string.
     *
     * @param string $html   The Blade template content.
     * @param array  $params The Blade template data.
     *
     * @return string The rendered content.
     *
     * @throws Exception
     */
    public function runString(string $html, array $params = []) : string
    {
        return Yoyo::getViewProvider()->getProviderInstance()->compiler()->renderString($html, $params);
    }

    /**
     * Add a stylesheet to the header stack.
     *
     * @param string $css The URL of the CSS file.
     * @return void
     */
    public function addStyle(string $css) : void
    {
        $this->prependToSection('head', sprintf("<link rel='stylesheet' href='%s' type='text/css'>", $css));
    }

    /**
     * Prepend content to a Blade section.
     *
     * @param string $section The name of the section.
     * @param string $content The content to prepend.
     * @return void
     */
    public function prependToSection(string $section, string $content) : void
    {
        $this->sectionPushes[$section][] = $content;
    }

    /**
     * Flush the content of a section.
     *
     * @return void
     */
    public function flushSectionPushes() : void
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
     * @return void
     */
    public function addInlineScript(string $scriptContent) : void
    {
        $this->prependToSection('footer', sprintf("<script>%s</script>", $scriptContent));
    }

    /**
     * Add a script to the footer stack.
     *
     * @param string $js The URL of the JavaScript file.
     * @return void
     */
    public function addScript(string $js) : void
    {
        $this->prependToSection('footer', sprintf("<script src='%s' defer></script>", $js));
    }

    /**
     * Initialize the theme if not already set.
     *
     * @return void
     */
    protected function initTheme() : void
    {
        try {
            $this->currentTheme = $this->themeManager->getCurrentTheme();
            $this->themeData = $this->themeManager->getThemeData($this->currentTheme) ?? [];

            if (empty($this->themeData)) {
                $this->fallbackToDefaultTheme();
            }
            
            app()->setTheme($this->currentTheme);
        } catch (Exception $e) {
            logs('templates')->error("Failed to initialize theme: ".$e->getMessage());
            $this->fallbackToDefaultTheme();
        }
    }

    /**
     * Fallback to the default theme if current theme is invalid.
     *
     * @return void
     */
    protected function fallbackToDefaultTheme() : void
    {
        $defaultTheme = ThemeManager::DEFAULT_THEME;
        try {
            $this->themeManager->setTheme($defaultTheme);
            $this->currentTheme = $this->themeManager->getCurrentTheme();
            $this->themeData = $this->themeManager->getThemeData($this->currentTheme) ?? [];
            logs('templates')->warning("Fallback to default theme '{$defaultTheme}' due to invalid current theme.");
        } catch (Exception $e) {
            throw new RuntimeException("Default theme '{$defaultTheme}' is not available. ".$e->getMessage());
        }
    }

    /**
     * Get the TemplateAssets instance.
     *
     * @return TemplateAssets
     */
    public function getTemplateAssets() : TemplateAssets
    {
        return $this->templateAssets;
    }

    /**
     * Register a Yoyo component.
     *
     * @param string $name      The component name.
     * @param mixed  $component The component class or closure.
     * @return void
     */
    public function registerComponent(string $name, $component = null) : void
    {
        $this->yoyo->registerComponent($name, $component);
    }

    /**
     * Add a global variable to the Blade instance.
     *
     * @param string $name  The name of the global variable.
     * @param mixed  $value The value of the global variable.
     * @return void
     */
    public function addGlobal(string $name, $value) : void
    {
        $this->globals[$name] = $value;
        $this->blade->share($name, $value);
    }

    /**
     * Add an error to the global errors bag.
     *
     * @param string $input The input name.
     * @param string $error The error message.
     * @return void
     */
    public function addError(string $input, string $error) : void
    {
        if (! isset($this->globals['errors'])) {
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
     * Run the template rendering process.
     *
     * @param string $path       The template path.
     * @param array  $variables  The variables to pass to the template.
     * @param array  $mergeData  Additional data to merge.
     * @return View
     */
    protected function runTemplate(string $path, array $variables, array $mergeData = []) : View
    {
        $path = $this->searchReplacementForInterface($path);

        $params = $this->beforeRenderEvent($path, $variables);

        $content = $this->blade->make($params->view, $params->variables, $mergeData);

        return $this->afterRenderEvent($content);
    }

    /**
     * Dispatch the before-render event and return the view and variables.
     *
     * @param string $template  The name of the template.
     * @param array  $variables The variables to pass to the template.
     * @return object An object containing the view and variables.
     */
    protected function beforeRenderEvent(string $template, array $variables = []) : object
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
     * @return View
     */
    protected function afterRenderEvent(View $view) : View
    {
        $event = new AfterRenderEvent($view);
        return events()->dispatch($event, AfterRenderEvent::NAME)->getView();
    }

    /**
     * Set up the Blade instance with the appropriate configuration.
     *
     * @return void
     */
    protected function setupBlade() : void
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
        $this->blade->if('auth', function () {
            return user()->isLoggedIn();
        });

        $this->blade->if('guest', function () {
            return ! user()->isLoggedIn();
        });

        $this->blade->if('can', function ($ability, $arguments = []) {
            return user()->can($ability);
        });

        $this->blade->if('cannot', function ($ability, $arguments = []) {
            return ! user()->can($ability);
        });

        $this->blade->directive('asset', function ($expression) {
            return "<?php echo asset($expression); ?>";
        });

        $this->addGlobal('app', app());

        $this->fluteBladeApp->bind('view', function () {
            return $this->blade;
        });

        // @php-ignore
        (new YoyoServiceProvider($this->fluteBladeApp))->boot();

        $this->yoyo = new Yoyo($this->fluteBladeApp);

        $this->yoyo->configure([
            'url' => url($this->isAdminPath() ? self::LIVE_COMPONENT_ADMIN_PATH : self::LIVE_COMPONENT_PATH),
            'scriptsPath' => url('assets/js/htmx/'),
            'historyEnabled' => true, // Enables HTMX history push state
        ]);

        $this->yoyo->registerViewProvider(function () {
            return new BladeViewProvider($this->blade);
        });

        if (! $this->getGlobal('errors')) {
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
     *
     * @return void
     */
    protected function addTranslateDirective() : void
    {
        $this->blade->directive('t', function ($expression) {
            return "<?php echo __($expression); ?>";
        });
    }

    /**
     * Load and register Blade components.
     *
     * @return void
     */
    protected function loadComponents() : void
    {
        if ( $this->isAdminPath() || ! is_installed())
            return;

        $componentsDir = $this->getTemplatePath("Themes/{$this->currentTheme}/views/components");

        if (is_dir($componentsDir)) {
            $componentFiles = $this->getBladeFiles($componentsDir);

            foreach ($componentFiles as $componentFile) {
                $relativePath = str_replace([$componentsDir.DIRECTORY_SEPARATOR, '.blade.php'], '', $componentFile);
                $alias = str_replace(DIRECTORY_SEPARATOR, '.', $relativePath);

                $componentView = "Themes.{$this->currentTheme}.views.components.".$alias;
                $this->blade->compiler()->component($componentView, $alias);
            }
        }

        // e.g., flute::pages.home
        $this->addNamespace('flute', $this->getTemplatePath("Themes/{$this->currentTheme}/views"));

        // For styles
        $sassPath = $this->getTemplatePath("Themes/{$this->currentTheme}/assets/sass");

        if (is_dir($sassPath)) {
            $this->templateAssets->getCompiler()->addImportPath($sassPath);
        }
    }

    /**
     * Get all Blade files from a directory.
     *
     * @param string $dir The directory path.
     * @return array The list of Blade file paths.
     */
    public function getBladeFiles(string $dir) : array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strpos($file->getFilename(), '.blade.php') !== false) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Resolve the given path to a Blade view name.
     *
     * @param string $path The path to the template.
     * @return string The Blade view name.
     */
    protected function resolveTemplatePath(string $path) : string
    {
        return str_replace(['.blade.php', '/'], ['', '.'], $path);
    }

    /**
     * Search for a replacement path based on the theme's interface replacements.
     *
     * @param string $interfacePath
     * @return string
     */
    protected function searchReplacementForInterface(string $interfacePath) : string
    {
        $replacements = $this->themeData['replacements'] ?? [];

        return $replacements[$interfacePath] ?? $interfacePath;
    }

    protected function isAdminPath() : bool
    {
        return is_admin_path() && user()->can('admin');
    }
}
