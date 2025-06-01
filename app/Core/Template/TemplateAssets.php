<?php

namespace Flute\Core\Template;

use Nette\Utils\Validators;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;
use ScssPhp\ScssPhp\Compiler;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Exception\CompilerException;
use ScssPhp\ScssPhp\Exception\SassException;
use ScssPhp\ScssPhp\OutputStyle;
use WebPConvert\WebPConvert;

class TemplateAssets
{
    protected Compiler $scssCompiler;
    protected Template $template;
    protected string $context = 'main';
    protected bool $minifyAssets;
    protected bool $debugMode;
    protected string $appUrl;
    protected array $additionalScssFiles = [
        'main' => [],
        'admin' => []
    ];
    protected array $additionalPartials = [
        'app/Core/Template/Resources/sass/_mixins.scss',
        'app/Core/Template/Resources/sass/_helpers.scss',
    ];

    private const CSS_CACHE_DIR = 'assets/css/cache/';
    private const JS_CACHE_DIR = 'assets/js/cache/';
    private const IMG_CACHE_DIR = 'assets/img/cache/';
    private const SUPPORTED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public function __construct()
    {
        $this->minifyAssets = config('assets.minify');
        $this->debugMode = false;
        $this->appUrl = config('app.url');

        if(!is_cli() && isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1' && is_debug()) {
            $this->debugMode = true;
        }

        $this->scssCompiler = new Compiler();
        $this->scssCompiler->setOutputStyle($this->minifyAssets ? OutputStyle::COMPRESSED : OutputStyle::EXPANDED);
    }

    /**
     * Initializes the template and context for asset handling, adding a custom directive
     * for embedding assets into the template.
     *
     * @param Template $template The template object to associate with this instance.
     * @param string $context The context in which assets are loaded (e.g., 'main' or 'admin').
     */
    public function init(Template $template, string $context = 'main') : void
    {
        $this->template = $template;
        $this->context = $context;

        $this->template->addDirective("at", function ($expression) {
            if (strpos($expression, ',') !== false) {
                return "<?php echo app('Flute\\Core\\Template\\TemplateAssets')->assetFunction($expression); ?>";
            }
            
            return "<?php echo app('Flute\\Core\\Template\\TemplateAssets')->assetFunction($expression, false); ?>";
        });
    }

    /**
     * Generates the appropriate URL or HTML tag for an asset based on its type (CSS, JS, image).
     *
     * @param string $expression The path or URL of the asset.
     * @param bool $urlOnly Whether to return only the URL instead of the full HTML tag.
     * @return string The generated HTML tag or asset URL.
     */
    public function assetFunction(string $expression, bool $urlOnly = false) : string
    {
        $filePath = $this->resolveFilePath($expression);
        $extension = $this->getFileExtension($expression, $filePath);
        $pathParts = explode("/", $expression);
        $firstSegment = $pathParts[0] ?? '';

        if ($firstSegment === "assets") {
            $url = $this->generateAssetUrl($expression);
            return $urlOnly ? $this->extractUrl($url) : $url;
        }

        return $this->processAssetBasedOnExtension($extension, $expression, $filePath, $urlOnly);
    }

    /**
     * Extracts just the URL from a generated HTML tag.
     *
     * @param string $htmlTag The HTML tag containing the URL.
     * @return string The extracted URL.
     */
    private function extractUrl(string $htmlTag) : string
    {
        if (preg_match('/(?:href|src)=["\'](.*?)["\']/i', $htmlTag, $matches)) {
            return $matches[1];
        }
        return $htmlTag;
    }

    /**
     * Resolves the file path for a given asset expression, ensuring it includes the base path if needed.
     *
     * @param string $expression The relative or absolute path of the asset.
     * @return string The resolved file path.
     */
    private function resolveFilePath(string $expression) : string
    {
        return strpos($expression, BASE_PATH) !== false ? $expression : BASE_PATH . "app/" . $expression;
    }

    /**
     * Retrieves the file extension of an asset from its path or URL.
     *
     * @param string $expression The asset path or URL.
     * @param string $filePath The full path to the asset file.
     * @return string The file extension in lowercase.
     */
    private function getFileExtension(string $expression, string $filePath) : string
    {
        $path = parse_url($expression, PHP_URL_PATH) ?: $filePath;
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Generates the full URL for an asset and adds a version query based on the last modification time.
     *
     * @param string $path The relative path of the asset.
     * @param bool $urlOnly Whether to return only the URL instead of the full HTML tag.
     * @return string The URL with a version query or the HTML tag.
     */
    private function generateAssetUrl(string $path, bool $urlOnly = false) : string
    {
        $fullPath = BASE_PATH . "public/" . $path;
        if (!file_exists($fullPath)) {
            return '';
        }

        $version = filemtime($fullPath);
        $url = url($path) . "?v={$version}";

        if ($urlOnly) {
            return $url;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return $this->generateTag($url, $extension);
    }

    /**
     * Processes an asset by extension, handling SCSS, CSS, JS, and images differently.
     *
     * @param string $extension The file extension.
     * @param string $expression The path or URL of the asset.
     * @param string $filePath The resolved file path of the asset.
     * @param bool $urlOnly Whether to return only the URL instead of the full HTML tag.
     * @return string The generated HTML tag or asset URL.
     */
    private function processAssetBasedOnExtension(string $extension, string $expression, string $filePath, bool $urlOnly = false) : string
    {
        switch ($extension) {
            case 'scss':
                return $this->processScssAsset($expression, $filePath);
            case 'css':
                return $this->processCssAsset($expression, $filePath);
            case 'js':
                return $this->processJsAsset($expression, $filePath);
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'webp':
            case 'svg':
                return $this->processImageAsset($expression, $filePath, $extension, $urlOnly);
            default:
                return '';
        }
    }

    /**
     * Adds a SCSS file to the list for a specified context if it exists and is valid.
     *
     * @param string $path The path to the SCSS file.
     * @param string $context The context ('main' or 'admin') for which this file should be added.
     */
    public function addScssFile(string $path, string $context) : void
    {
        if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'scss') {
            $this->additionalScssFiles[$context][] = $path;
        } else {
            logs()->warning("SCSS file not found or invalid: {$path}");
        }
    }

    /**
     * Returns the cache directory path for a given asset type (e.g., CSS, JS).
     *
     * @param string $type The asset type ('css', 'js', etc.).
     * @return string The cache directory path.
     */
    protected function getCacheDir(string $type) : string
    {
        return "assets/{$type}/cache/{$this->context}/";
    }

    public function addImportPath(string $path, string $context = 'main') : void
    {
        if ($context === $this->context) {
            $this->scssCompiler->addImportPath($path);
        }
    }

    /**
     * Compiles and caches a SCSS file, including additional files for the context, if it needs recompilation.
     *
     * @param string $expression The asset expression.
     * @param string $scssPath The path to the SCSS file.
     * @return string The generated HTML link tag for the compiled CSS.
     */
    private function processScssAsset(string $expression, string $scssPath) : string
    {
        if (!file_exists($scssPath)) {
            return '';
        }

        $hash = sha1($scssPath . implode(',', $this->additionalScssFiles[$this->context]));
        $cssCacheDir = $this->getCacheDir('css');
        $cssPath = $cssCacheDir . "{$hash}.css";
        $cssFullPath = BASE_PATH . "public/" . $cssPath;

        $this->ensureDirectoryExists(dirname($cssFullPath));

        // $needsRecompile = !file_exists($cssFullPath) || filemtime($scssPath) > filemtime($cssFullPath);
        $needsRecompile = !file_exists($cssFullPath) || filemtime($scssPath) > filemtime($cssFullPath) || $this->debugMode;

        if (!$needsRecompile) {
            foreach ($this->additionalScssFiles[$this->context] as $additionalFile) {
                if (filemtime($additionalFile) > filemtime($cssFullPath)) {
                    $needsRecompile = true;
                    break;
                }
            }
        }

        if ($needsRecompile) {
            $scssContents = [];

            $partialsContent = $this->loadSharedPartials();
            $scssContents[] = $partialsContent;

            $mainScssContent = file_get_contents($scssPath);
            if ($mainScssContent === false) {
                logs()->error("Unable to read SCSS file: {$scssPath}");
                return '';
            }
            $scssContents[] = $mainScssContent;

            foreach ($this->additionalScssFiles[$this->context] as $additionalFile) {
                $additionalContent = file_get_contents($additionalFile);
                if ($additionalContent === false) {
                    logs()->warning("Unable to read additional SCSS file: {$additionalFile}");
                    continue;
                }
                $scssContents[] = $additionalContent;
            }

            $css = $this->compileScss($scssContents);

            if ($css !== '') {
                $this->saveAsset($cssFullPath, $css);
            }
        }

        $version = filemtime($cssFullPath);
        $url = url($cssPath) . "?v={$version}";

        return "<link href=\"{$url}\" rel=\"stylesheet\">";
    }

    /**
     * Ensures that a specified directory exists, creating it if necessary.
     *
     * @param string $directory The path of the directory to check or create.
     */
    protected function ensureDirectoryExists(string $directory) : void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Compiles SCSS contents into CSS, catching any compilation errors.
     *
     * @param array $scssContents An array of SCSS content strings.
     * @return string The compiled CSS string.
     */
    private function compileScss(array $scssContents) : string
    {
        $scssContent = implode("\n", $scssContents);

        try {
            $css = $this->scssCompiler->compileString($scssContent)->getCss();
            return $css;
        } catch (SassException $e) {
            $message = sprintf("SCSS compilation error: %s", $e);

            if ($this->debugMode) {
                throw new CompilerException($message, 0, null);
            } else {
                logs()->error($message);
            }
        }

        return '';
    }

    /**
     * Loads shared SCSS partials, which are included in all SCSS compilations.
     *
     * @return string The combined contents of shared partial files.
     */
    private function loadSharedPartials() : string
    {
        $partialsContent = '';

        foreach ($this->additionalPartials as $partialPath) {
            $partialPath = path($partialPath);

            if (file_exists($partialPath)) {
                $content = file_get_contents($partialPath);
                if ($content !== false) {
                    $partialsContent .= $content . "\n";
                } else {
                    logs()->warning("Unable to read SCSS partial: {$partialPath}");
                }
            } else {
                logs()->warning("SCSS partial not found: {$partialPath}");
            }
        }

        return $partialsContent;
    }

    /**
     * Processes a CSS file, ensuring it's cached and returning the appropriate HTML link tag.
     *
     * @param string $expression The asset expression.
     * @param string $cssPathBase The path to the CSS file.
     * @return string The generated HTML link tag for the CSS file.
     */
    private function processCssAsset(string $expression, string $cssPathBase) : string
    {
        if (Validators::isUrl($expression)) {
            return $this->processRemoteAsset($expression, 'css');
        }

        if (!file_exists($cssPathBase)) {
            return '';
        }

        $hash = sha1($cssPathBase);
        $cssPath = self::CSS_CACHE_DIR . "{$hash}.css";
        $cssFullPath = BASE_PATH . "public/" . $cssPath;

        if (!file_exists($cssFullPath) || filemtime($cssPathBase) > filemtime($cssFullPath)) {
            $content = file_get_contents($cssPathBase);
            if ($content === false) {
                logs()->error("Unable to read CSS file: {$cssPathBase}");
                return '';
            }
            $this->saveAsset($cssFullPath, $content);
        }

        $version = filemtime($cssFullPath);
        $url = url($cssPath) . "?v={$version}";

        return "<link href=\"{$url}\" rel=\"stylesheet\">";
    }

    /**
     * Processes a JS file, ensuring it's cached and returning the appropriate HTML script tag.
     *
     * @param string $expression The asset expression.
     * @param string $jsPathBase The path to the JS file.
     * @return string The generated HTML script tag for the JS file.
     */
    private function processJsAsset(string $expression, string $jsPathBase) : string
    {
        if (Validators::isUrl($expression)) {
            return $this->processRemoteAsset($expression, 'js');
        }

        if (!file_exists($jsPathBase)) {
            return '';
        }

        $hash = sha1($jsPathBase);
        $jsPath = self::JS_CACHE_DIR . "{$hash}.js";
        $jsFullPath = BASE_PATH . "public/" . $jsPath;

        if (!file_exists($jsFullPath) || filemtime($jsPathBase) > filemtime($jsFullPath)) {
            $content = file_get_contents($jsPathBase);
            if ($content === false) {
                logs()->error("Unable to read JS file: {$jsPathBase}");
                return '';
            }
            $this->saveAsset($jsFullPath, $content);
        }

        $version = filemtime($jsFullPath);
        $url = url($jsPath) . "?v={$version}";

        return "<script src=\"{$url}\" defer></script>";
    }

    /**
     * Processes an image file, caching and converting it to WebP if necessary, and returns an HTML img tag.
     *
     * @param string $expression The asset expression.
     * @param string $imgPathBase The path to the image file.
     * @param string $extension The image file extension.
     * @param bool $urlOnly Whether to return only the URL instead of the full HTML tag.
     * @return string The generated HTML img tag or the URL of the image.
     */
    private function processImageAsset(string $expression, string $imgPathBase, string $extension, bool $urlOnly = false) : string
    {
        if (Validators::isUrl($expression)) {
            return $this->processRemoteAsset($expression, 'img');
        }

        if (!file_exists($imgPathBase) || !in_array($extension, self::SUPPORTED_IMAGE_EXTENSIONS)) {
            return 'not found';
        }

        $hash = $this->debugMode ? pathinfo($expression, PATHINFO_FILENAME) : sha1($expression);
        $imgPath = self::IMG_CACHE_DIR . "{$hash}.{$extension}";
        $imgFullPath = BASE_PATH . "public/" . $imgPath;

        if (in_array($extension, ['png', 'jpg', 'jpeg']) && config('app.convert_to_webp')) {
            $webpPath = self::IMG_CACHE_DIR . "{$hash}.webp";
            $webpFullPath = BASE_PATH . "public/" . $webpPath;

            if (!file_exists($webpFullPath) || filemtime($imgPathBase) > filemtime($webpFullPath)) {
                try {
                    WebPConvert::convert($imgPathBase, $webpFullPath);
                } catch (\Exception $e) {
                    logs()->error($e->getMessage());
                    return $this->generateAssetUrl($imgPath);
                }
            }

            $imgPath = $webpPath;
            $imgFullPath = $webpFullPath;
        }

        if (!file_exists($imgFullPath) || filemtime($imgPathBase) > filemtime($imgFullPath)) {
            $this->copyAsset($imgPathBase, $imgFullPath);
        }

        return $urlOnly ? url($imgPath) : "<img src=\"" . url($imgPath) . "\" alt=\"\" loading=\"lazy\">";
    }

    /**
     * Processes an external asset URL (CSS, JS, or image), caching it locally if it's not already present.
     *
     * @param string $url The URL of the external asset.
     * @param string $type The asset type ('css', 'js', or 'img').
     * @return string The generated HTML tag or local asset URL.
     */
    protected function processRemoteAsset(string $url, string $type) : string
    {
        if ($this->isLocalUrl($url)) {
            return $this->generateTag($url, $type);
        }

        $localUrl = $this->processCdnAsset($url, $type);
        return $this->generateTag($localUrl, $type);
    }

    /**
     * Checks if a given URL belongs to the same host as the application.
     *
     * @param string $url The URL to check.
     * @return bool True if the URL is local, false otherwise.
     */
    protected function isLocalUrl(string $url) : bool
    {
        $parsedUrl = parse_url($url);
        $parsedAppUrl = parse_url($this->appUrl);

        if (isset($parsedUrl['host'], $parsedAppUrl['host'])) {
            return $parsedUrl['host'] === $parsedAppUrl['host'];
        }

        return true;
    }

    /**
     * Generates the appropriate HTML tag (link, script, or img) for an asset.
     *
     * @param string $url The URL of the asset.
     * @param string $type The asset type ('css', 'js', or 'img').
     * @return string The HTML tag.
     */
    protected function generateTag(string $url, string $type) : string
    {
        switch ($type) {
            case 'css':
                return "<link href=\"{$url}\" rel=\"stylesheet\">";
            case 'js':
                return "<script src=\"{$url}\" defer></script>";
            case 'img':
                return "<img src=\"{$url}\" alt=\"\" loading=\"lazy\">";
            default:
                return '';
        }
    }

    /**
     * Downloads and caches an external asset from a CDN, storing it locally.
     *
     * @param string $url The URL of the CDN asset.
     * @param string $type The asset type ('js' by default).
     * @return string The URL of the cached local asset.
     */
    protected function processCdnAsset(string $url, string $type = "js") : string
    {
        $hash = sha1($url);
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: $type;
        $localPath = "assets/{$type}/cache/{$hash}.{$extension}";
        $fullLocalPath = BASE_PATH . "public/" . $localPath;

        if (!file_exists($fullLocalPath)) {
            $content = @file_get_contents($url);
            if ($content === false) {
                return Validators::isUrl($url) ? $url : '';
            }
            $this->saveAsset($fullLocalPath, $content);
        }

        $version = filemtime($fullLocalPath);
        return url($localPath) . "?v={$version}";
    }

    /**
     * Saves asset content to a specified path, with optional minification.
     *
     * @param string $path The path where the asset will be saved.
     * @param string $content The content to save.
     */
    protected function saveAsset(string $path, string $content) : void
    {
        $content = $this->minifyContent($path, $content);
        $this->ensureDirectoryExists(dirname($path));

        if (file_put_contents($path, $content, LOCK_EX) === false) {
            logs()->error("Failed to write asset to path: {$path}");
        }
    }

    /**
     * Copies an asset from a source path to a destination path, creating directories if necessary.
     *
     * @param string $sourcePath The source file path.
     * @param string $destinationPath The destination file path.
     */
    protected function copyAsset(string $sourcePath, string $destinationPath) : void
    {
        $this->ensureDirectoryExists(dirname($destinationPath));

        if (!copy($sourcePath, $destinationPath)) {
            logs()->error("Failed to copy asset from {$sourcePath} to {$destinationPath}");
        }
    }

    /**
     * Minifies asset content if minification is enabled, based on the asset's file type.
     *
     * @param string $path The path to the asset file.
     * @param string $content The content to minify.
     * @return string The minified content.
     */
    protected function minifyContent(string $path, string $content) : string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'js' && $this->minifyAssets) {
            $minifier = new Minify\JS();
            $minifier->add($content);
            return $minifier->minify();
        }

        if ($extension === 'css' && $this->minifyAssets) {
            // Performance issue with autoprefixer in debug mode
            if (!is_debug()) {
                $autoprefixer = new Autoprefixer($content);
                $content = $autoprefixer->compile();
            }

            $minifier = new Minify\CSS();
            $minifier->add($content);
            return $minifier->minify();
        }

        return $content;
    }

    /**
     * Retrieves the SCSS compiler instance for compiling SCSS content.
     *
     * @return Compiler The SCSS compiler instance.
     */
    public function getCompiler() : Compiler
    {
        return $this->scssCompiler;
    }
}
