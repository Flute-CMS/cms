<?php

namespace Flute\Core\Template;

use Exception;
use Flute\Core\Template\EditedBladeOne as BladeOne;
use Flute\Core\Contracts\ThemeLoaderInterface;
use Flute\Core\Contracts\ViewServiceInterface;
use Flute\Core\Events\AfterRenderEvent;
use Flute\Core\Events\BeforeRenderEvent;
use Flute\Themes\standard\ThemeLoader;

/**
 * The Template class provides an interface to the Blade templating engine.
 */
class Template extends AbstractTemplateInstance implements ViewServiceInterface
{
    protected ThemeLoaderInterface $templateClass;

    protected array $assetAliases = [
        'animate' => '/assets/css/libs/animate.min.css',
        'sfpro' => '/assets/fonts/sfpro/sfpro.css',
        'montserrat' => '/assets/fonts/montserrat/montserrat.css',
        'grid' => '/assets/css/libs/bootstrap-grid.min.css',
        'appjs' => '/assets/js/app.js',
        'jquery' => '/assets/js/jquery.js',
        'tables_js' => '/assets/js/tables.js',
        'tables_css' => '/assets/css/libs/tables.css',
        'phosphor' => 'https://unpkg.com/@phosphor-icons/web@2.0.3/src/index.js'
    ];

    /**
     * Create a new Template instance.
     *
     * @param TemplateAssets $templateAssets The TemplateAssets instance.
     * @param string|null $viewsPath The path to the views' directory.
     * @param string|null $cachePath The path to the cache directory.
     */
    public function __construct(TemplateAssets $templateAssets, string $viewsPath = null, string $cachePath = null)
    {
        $this->templateAssets = $templateAssets;
        $this->viewsPath = $viewsPath ?? path("app");
        $this->cachePath = $cachePath ?? path("storage/app/views");

        $this->templateVariables = new TemplateVariables;

        $this->setupBlade();
        $this->addTranslateDirective();

        $this->templateAssets->addScssDirective($this);
    }

    /**
     * Get the BladeOne instance.
     *
     * @return BladeOne The BladeOne instance.
     */
    public function getBlade(): BladeOne
    {
        return $this->blade;
    }

    /**
     * Render a template with the given variables.
     *
     * @param string $template The name of the template to render.
     * @param array $context The variables to pass to the template.
     * @param bool $templatePath Whether to use the active theme or not.
     *
     * @return string The rendered template.
     * @throws Exception
     */
    public function render(string $template, array $context = [], bool $templatePath = true): string
    {
        $templatePath = $this->resolveTemplatePath(
            $templatePath ? sprintf("Themes/%s/%s", app()->getTheme(), $template) : $template
        );

        $this->initExternalTemplate();

        if ($templatePath && $this->templateClass)
            $this->blade->share($this->templateClass->getLayoutArguments());

        return $this->_run($templatePath, $context);
    }

    /**
     * Render a module with the given variables.
     *
     * @param string $template The name of the module to render.
     * @param array $variables The variables to pass to the module.
     * @return string The rendered module.
     * @throws Exception
     */
    public function renderModule(string $template, array $variables = []): string
    {
        return $this->_run("Modules/" . $template, $variables);
    }

    /**
     * Render an error template with the given variables.
     *
     * @param int $errorCode The HTTP error code to render.
     * @param array $variables The variables to pass to the error template.
     * @param bool $useTheme Whether to use the active theme or not.
     * @return string The rendered error template.
     * @throws Exception
     */
    public function renderError(int $errorCode, array $variables = [], bool $useTheme = true): string
    {
        $template = "errors/{$errorCode}.blade.php";

        // If the error template for the current theme doesn't exist, fall back to the standard theme.
        if ($useTheme && !$this->templateExists($template)) {
            $template = "Themes/errors/standard/{$errorCode}.blade.php";
            $useTheme = false;
        }

        return $this->render($template, $variables, $useTheme);
    }

    /**
     * Check if a template exists in the current theme.
     *
     * @param string $template The name of the template to check.
     * @return bool Whether the template exists or not.
     */
    public function templateExists(string $template): bool
    {
        $templatePath = sprintf("%s/Themes/%s/%s", $this->viewsPath, app()->getTheme(), $template);

        return file_exists($templatePath);
    }

    /**
     * Extend a template with another template.
     *
     * @param string $template The name of the template to extend.
     * @param string $extension The name of the template to use as the extension.
     * @return void
     */
    public function extendTemplate(string $template, string $extension): void
    {
        $this->blade->addInclude($template, $extension);
    }

    /**
     * Add a global variable that will be available in all templates.
     *
     * @param string $name The name of the variable.
     * @param mixed $value The value of the variable.
     * @return void
     */
    public function addGlobal(string $name, $value): void
    {
        $this->blade->share($name, $value);
    }

    /**
     * Add a custom function to Blade.
     *
     * @param string $name The name of the function.
     * @param callable $function The function to add.
     * @return void
     */
    public function addFunction(string $name, callable $function): void
    {
        $this->blade->directiveRT($name, $function);
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
     * Run string in blade
     *
     * @param string $html
     * @param array $data
     *
     * @return string
     * @throws Exception
     */
    public function runString(string $html, array $data = []): string
    {
        return $this->blade->runString($html, $data);
    }

    /**
     * Add content to section.
     * 
     * @param string $section
     * @param string $content
     * 
     * @return void
     */
    public function section(string $section, string $content): void
    {
        $this->blade->push($section, $content);
    }

    /**
     * Clear the section
     * 
     * @param string $section
     * 
     * @return void
     */
    public function clearSection( string $section )
    {
        $this->blade->clearSection($section);
    }

    /**
     * Clear all sections
     * 
     * @return void
     */
    public function clearAllSections()
    {
        $this->blade->clearSections();
    }

    /**
     * Add styles to the section.
     * 
     * @param string $css
     * 
     * @return void
     */
    public function addStyle(string $css): void
    {
        $this->section('header', sprintf("<link rel='stylesheet' href='%s' type='text/css'>", $this->getBlade()->relative($css)));
    }

    /**
     * Add scripts to the section.
     * 
     * @param string $js
     * 
     * @return void
     */
    public function addScript(string $js): void
    {
        $this->section('footer', sprintf("<script src='%s'></script>", $this->getBlade()->relative($js)));
    }

    /**
     * Add content to section first.
     * 
     * @param string $section
     * @param string $content
     * 
     * @return void
     */
    public function sectionFirst(string $section, string $content): void
    {
        $this->blade->pushFirst($section, $content);
    }

    /**
     * Prepare template to render template
     * 
     * @return void
     */
    public function setThemeLoader(ThemeLoaderInterface $themeLoader)
    {
        $this->templateClass = $themeLoader;
        $this->templateClass->register($this);
        $this->templateClass->blade($this->getBlade());
        $this->loadComponents();
    }

    protected function initExternalTemplate()
    {
        if( !isset( $this->templateClass ) ) {
            $themeLoader = app(ThemeLoader::class);
            
            $this->setThemeLoader($themeLoader);
        }
    }

    /**
     * Get template assets instance
     * 
     * @return TemplateAssets
     */
    public function getTemplateAssets(): TemplateAssets
    {
        return $this->templateAssets;
    }

    /**
     * Get the scss variables instance
     * 
     * @return TemplateVariables
     */
    public function variables(): TemplateVariables
    {
        return $this->templateVariables;
    }

    /**
     * Render the given template with the given variables.
     *
     * @param string $path The path to the template.
     * @param array $variables The variables to pass to the template.
     * @return string The rendered template.
     * @throws Exception
     */
    protected function _run(string $path, array $variables): string
    {
        $path = $this->searchReplacementForInterface($path);

        $params = $this->_beforeEvent($path, $variables);

        $content = $this->blade->run((string) $params->view, $params->variables);

        return $this->_afterEvent($content);
    }

    /**
     * Dispatch the before-render event and return the view and variables.
     *
     * @param string $template The name of the template.
     * @param array $variables The variables to pass to the template.
     * @return object An object containing the view and variables.
     */
    protected function _beforeEvent(string $template, array $variables = []): object
    {
        $event = events()->dispatch(new BeforeRenderEvent($template, $variables), BeforeRenderEvent::NAME);

        return (object) [
            "view" => $event->getView(),
            "variables" => $event->getData() ?? []
        ];
    }

    /**
     * Dispatch the after-render event and return the content.
     *
     * @param string $content The content to dispatch the event for.
     * @return string The updated content.
     */
    protected function _afterEvent(string $content): string
    {
        $event = new AfterRenderEvent($content);
        return events()->dispatch($event, AfterRenderEvent::NAME)->getContent();
    }

    /**
     * Set up the BladeOne instance with the appropriate configuration.
     *
     * @return void
     */
    protected function setupBlade(): void
    {
        $this->blade = BladeOne::getInstance($this->viewsPath, $this->cachePath, config('view.cache.mode'));

        $this->blade->setBaseUrl(config("app.url"));
        $this->blade->throwOnError = true;
        $this->blade->includeScope = true;

        $this->blade->setInjectResolver(static function ($namespace) {
            return app($namespace);
        });

        $this->blade->setCanFunction(function ($action = null, $subject = null) {
            return user()->hasPermission($action);
        });

        $this->addGlobal("app", app());

        $this->addAssetAliases();

        $this->blade->pushFirst('header', '<script>const SITE_URL = `' . config('app.url') . '`;</script>');
    }

    /**
     * Adds assets aliases for the @asset()
     * 
     * @return void
     */
    protected function addAssetAliases(): void
    {
        $this->blade->addAssetDict($this->assetAliases);
    }

    /**
     * Add translate directive into the BladeOne
     * 
     * @return void
     */
    protected function addTranslateDirective()
    {
        $this->addFunction('t', function ($key, $replace = [], $locale = null) {
            echo __($key, $replace, $locale);
        });
    }

    /**
     * Load all components from the template into the blade
     * 
     * @return void
     */
    protected function loadComponents()
    {
        if (!$this->templateClass)
            return;

        foreach ($this->templateClass->getComponentsLayout() as $key => $component) {
            $this->blade->addInclude(sprintf("Themes/%s/%s.blade.php", $this->templateClass->getKey(), $component), $key);
        }
    }

    /**
     * Resolve given path to a file.
     * 
     * @param string $path The path to template.
     * 
     * @return string
     */
    protected function resolveTemplatePath(string $path): string
    {
        return str_contains($path, '.blade.php') ? $path : $path . '.blade.php';
    }

    /**
     * Get current path with replace or not
     *
     * @param string $interfacePath
     * @return string
     */
    protected function searchReplacementForInterface(string $interfacePath): string
    {
        $replacement = $this->templateClass->getReplacement($interfacePath);

        return !empty($replacement) ? $replacement : $interfacePath;
    }
}
