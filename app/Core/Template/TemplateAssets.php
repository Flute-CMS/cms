<?php

namespace Flute\Core\Template;

use Flute\Core\Template\Concerns\AssetCaching;
use Flute\Core\Template\Concerns\AssetResolution;
use Flute\Core\Template\Concerns\CssJsImageAssets;
use Flute\Core\Template\Concerns\RemoteAssets;
use Flute\Core\Template\Concerns\ScssCompilation;
use ScssPhp\ScssPhp\OutputStyle;

class TemplateAssets
{
    use AssetCaching;
    use AssetResolution;
    use CssJsImageAssets;
    use RemoteAssets;
    use ScssCompilation;

    private const CSS_CACHE_DIR = 'assets/css/cache/';

    private const JS_CACHE_DIR = 'assets/js/cache/';

    private const IMG_CACHE_DIR = 'assets/img/cache/';

    private const SUPPORTED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    private const EXTENSION_TO_TYPE = [
        'css' => 'css',
        'scss' => 'css',
        'js' => 'js',
        'mjs' => 'js',
        'jpg' => 'img',
        'jpeg' => 'img',
        'png' => 'img',
        'gif' => 'img',
        'webp' => 'img',
        'svg' => 'img',
    ];

    protected TemplateScssCompiler $scssCompiler;

    protected Template $template;

    protected string $context = 'main';

    protected bool $minifyAssets;

    protected bool $autoprefixAssets;

    protected bool $debugMode;

    protected string $appUrl;

    protected int $remoteAssetTimeout = 5;

    protected int $remoteAssetMaxBytes = 5242880;

    protected int $autoprefixMaxBytes = 400000;

    protected array $additionalScssFiles = [
        'main' => [],
        'admin' => [],
    ];

    protected array $additionalPartials = [
        'app/Core/Template/Resources/sass/_mixins.scss',
        'app/Core/Template/Resources/sass/_helpers.scss',
    ];

    protected array $assetPathCache = [];

    protected array $compilationCache = [];

    protected array $fallbackAssetPaths = [];

    protected string $standardTheme = 'standard';

    protected ?array $cachedThemeFallbackOrder = null;

    protected static float $assetsCompileTime = 0.0;

    public function __construct()
    {
        $this->minifyAssets = config('assets.minify');
        $this->autoprefixAssets = (bool) config('assets.autoprefix', false);
        $this->debugMode = false;
        $this->appUrl = config('app.url');
        $timeout = (int) ( config('assets.remote_asset_timeout') ?? 5 );
        $this->remoteAssetTimeout = $timeout > 0 ? $timeout : 5;
        $maxBytes = (int) ( config('assets.remote_asset_max_bytes') ?? 0 );
        if ($maxBytes > 0) {
            $this->remoteAssetMaxBytes = $maxBytes;
        }

        $limit = (int) ( config('assets.autoprefix_max_bytes') ?? 0 );

        if ($limit > 0) {
            $this->autoprefixMaxBytes = $limit;
        }

        if (is_development()) {
            $this->debugMode = true;
        }

        $this->scssCompiler = new TemplateScssCompiler();
        $this->scssCompiler->setOutputStyle($this->minifyAssets ? OutputStyle::COMPRESSED : OutputStyle::EXPANDED);

        $this->scssCompiler->addImportPath(path('app'));
    }

    public function init(Template $template, string $context = 'main'): void
    {
        $this->template = $template;
        $this->context = $context;

        $this->template->addDirective('at', static function ($expression) {
            if (str_contains($expression, ',')) {
                return "<?php echo app('Flute\\Core\\Template\\TemplateAssets')->assetFunction({$expression}); ?>";
            }

            return "<?php echo app('Flute\\Core\\Template\\TemplateAssets')->assetFunction({$expression}, false); ?>";
        });

        $this->loadThemeScssAppends();
    }

    public function addScssFile(string $path, string $context): void
    {
        if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'scss') {
            $this->additionalScssFiles[$context][] = $path;
        } else {
            logs()->warning("SCSS file not found or invalid: {$path}");
        }
    }

    public function getCompiler(): TemplateScssCompiler
    {
        return $this->scssCompiler;
    }

    public function addImportPath(string $path, string $context = 'main'): void
    {
        if ($context === $this->context && is_dir($path)) {
            $this->scssCompiler->addImportPath($path);
        }
    }
}
