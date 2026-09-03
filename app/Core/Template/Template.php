<?php

namespace Flute\Core\Template;

use Clickfwd\Yoyo\Yoyo;
use Exception;
use Flute\Core\Router\Contracts\RouterInterface;
use Flute\Core\Template\Concerns\TemplateCore\HandlesBladeSetup;
use Flute\Core\Template\Concerns\TemplateCore\HandlesComponents;
use Flute\Core\Template\Concerns\TemplateCore\HandlesGlobals;
use Flute\Core\Template\Concerns\TemplateCore\HandlesSections;
use Flute\Core\Template\Concerns\TemplateCore\HandlesTemplateRendering;
use Flute\Core\Template\Concerns\TemplateCore\HandlesThemeCache;
use Flute\Core\Template\Contracts\ViewServiceInterface;
use Flute\Core\Template\Controllers\YoyoController;
use Flute\Core\Theme\ThemeManager;
use Illuminate\View\View;
use Jenssegers\Blade\Blade;
use RuntimeException;
use Throwable;

/**
 * The Template class provides an interface to the Blade templating engine.
 */
class Template extends AbstractTemplateInstance implements ViewServiceInterface
{
    use HandlesBladeSetup;
    use HandlesComponents;
    use HandlesGlobals;
    use HandlesSections;
    use HandlesTemplateRendering;
    use HandlesThemeCache;

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
    protected ?array $cachedFallbackOrder = null;

    protected int $maxComponentCacheSize = 500;
    protected int $maxPathCacheSize = 1000;

    public function __construct(
        TemplateAssets $templateAssets,
        RouterInterface $router,
        ThemeManager $themeManager,
        ?string $viewsPath = null,
        ?string $cachePath = null,
    ) {
        self::$instance = $this;

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
        } catch (Throwable $e) {
            logs('templates')->error("Failed to set theme '{$themeName}': " . $e->getMessage());
            $this->fallbackToDefaultTheme();
        }
    }

    public function getYoyo(): Yoyo
    {
        return $this->yoyo;
    }

    public static function getInitializedInstance(): ?self
    {
        return self::$instance ?? null;
    }

    public function setYoyoRoute(): void
    {
        try {
            $path = $this->isAdminPath() ? self::LIVE_COMPONENT_ADMIN_PATH : self::LIVE_COMPONENT_PATH;
            $this->router
                ->any($path, [YoyoController::class, 'handle'])
                ->middleware(['web', 'csrf:all'])
                ->name('yoyo.update');
        } catch (Throwable $e) {
            logs()->error('Exception while registering Yoyo route: ' . $e->getMessage());
            if (is_debug()) {
                throw $e;
            }
        }
    }

    public function addNamespace($namespace, $hints): self
    {
        $this->blade->addNamespace($namespace, $hints);

        return $this;
    }

    public function getBlade(): Blade
    {
        return $this->blade;
    }

    public function getAsset(string $assetKey): string
    {
        $assetPath = $this->assetAliases[$assetKey] ?? trim($assetKey, '/');

        return url($assetPath)->get();
    }

    public function render(string $template, array $context = [], $mergeData = []): View
    {
        if (!empty($this->themeData['layout_arguments'])) {
            $this->blade->share($this->themeData['layout_arguments']);
        }

        return $this->runTemplate($template, $context, $mergeData);
    }

    /**
     * @throws Exception
     */
    public function renderError(int $errorCode, array $variables = []): View
    {
        $hint = !is_installed() ? 'installer' : ( $this->isAdminPath() ? 'admin' : 'flute' );

        return $this->render("{$hint}::pages.error", array_merge(['code' => $errorCode], $variables));
    }

    public function getTemplatePath(string $filename): string
    {
        return sprintf('%s/%s', $this->viewsPath, $filename);
    }

    /**
     * @throws Exception
     */
    public function runString(string $html, array $params = []): string
    {
        return Yoyo::getViewProvider()
            ->getProviderInstance()
            ->compiler()
            ->renderString($html, $params);
    }

    public function getTemplateAssets(): TemplateAssets
    {
        return $this->templateAssets;
    }

    public function registerComponent(string $name, $component = null): void
    {
        $this->yoyo->registerComponent($name, $component);
    }

    protected function isAdminPath(): bool
    {
        if (!is_admin_path()) {
            return false;
        }

        try {
            return user()->can('admin');
        } catch (Throwable $e) {
            logs('templates')->warning('Unable to resolve admin template context: ' . $e->getMessage());

            return false;
        }
    }
}
