<?php

namespace Flute\Core\Template;

use Flute\Core\Theme\ThemeManager;
use MatthiasMullie\Minify;
use Nette\Utils\Validators;
use Padaliyajay\PHPAutoprefixer\Autoprefixer;
use ScssPhp\ScssPhp\Compiler;
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

    private const CSS_CACHE_DIR = 'assets/css/cache/';
    private const JS_CACHE_DIR = 'assets/js/cache/';
    private const IMG_CACHE_DIR = 'assets/img/cache/';
    private const SUPPORTED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /**
     * Accumulated time spent on compiling/minifying theme assets (scss, js, etc.)
     * @var float
     */
    protected static float $assetsCompileTime = 0.0;

    public function __construct()
    {
        $this->minifyAssets = config('assets.minify');
        $this->debugMode = false;
        $this->appUrl = config('app.url');

        if (is_development()) {
            $this->debugMode = true;
        }

        $this->scssCompiler = new Compiler();
        $this->scssCompiler->setOutputStyle($this->minifyAssets ? OutputStyle::COMPRESSED : OutputStyle::EXPANDED);

        $this->scssCompiler->addImportPath(path('app'));
    }

    /**
     * Initializes the template and context for asset handling, adding a custom directive
     * for embedding assets into the template.
     *
     * @param Template $template The template object to associate with this instance.
     * @param string $context The context in which assets are loaded (e.g., 'main' or 'admin').
     */
    public function init(Template $template, string $context = 'main'): void
    {
        $this->template = $template;
        $this->context = $context;

        $this->template->addDirective("at", function ($expression) {
            if (strpos($expression, ',') !== false) {
                return "<?php echo app('Flute\\Core\\Template\\TemplateAssets')->assetFunction($expression); ?>";
            }

            return "<?php echo app('Flute\\Core\\Template\\TemplateAssets')->assetFunction($expression, false); ?>";
        });

        $this->loadThemeScssAppends();
    }

    /**
     * Find asset file with fallback support across themes.
     *
     * @param string $relativePath Relative path from theme directory
     * @param string $type Asset type (scripts, images, sass, etc.)
     * @return string|null Found file path or null
     */
    protected function findAssetWithFallback(string $relativePath, string $type = 'scripts'): ?string
    {
        $cacheKey = "asset:{$type}:{$relativePath}";

        if (isset($this->assetPathCache[$cacheKey])) {
            return $this->assetPathCache[$cacheKey];
        }

        $themes = $this->getThemeFallbackOrder();

        foreach ($themes as $theme) {
            $assetPath = BASE_PATH . "app/Themes/{$theme}/assets/{$type}/{$relativePath}";
            if (file_exists($assetPath)) {
                return $this->assetPathCache[$cacheKey] = $assetPath;
            }
        }

        return $this->assetPathCache[$cacheKey] = null;
    }

    /**
     * Get theme fallback order.
     *
     * @return array
     */
    protected function getThemeFallbackOrder(): array
    {
        $currentTheme = app(ThemeManager::class)->getCurrentTheme() ?? $this->standardTheme;
        $themes = [$currentTheme];

        if ($currentTheme !== $this->standardTheme) {
            $themes[] = $this->standardTheme;
        }

        return $themes;
    }

    /**
     * Generates the appropriate URL or HTML tag for an asset based on its type (CSS, JS, image).
     *
     * @param string $expression The path or URL of the asset.
     * @param bool $urlOnly Whether to return only the URL instead of the full HTML tag.
     * @return string The generated HTML tag or asset URL.
     */
    public function assetFunction(string $expression, bool $urlOnly = false): string
    {
        $expression = $this->applyAssetReplacement($expression);

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
     * Apply theme.json asset replacement rules to the asset expression.
     * Supports:
     *  - asset_replacements: direct mapping
     *  - asset_module_replacements: regex mapping
     *  - asset_wildcard_replacements: fnmatch-style mapping
     */
    private function applyAssetReplacement(string $expression): string
    {
        // normalize / and \
        $expression = str_replace(['/', '\\'], '/', $expression);
        $normalizeBasePath = str_replace(['/', '\\'], '/', BASE_PATH);
        
        $expression = str_replace([$normalizeBasePath, '/app/'], ['', ''], $expression);

        try {
            /** @var ThemeManager $themeManager */
            $themeManager = app(ThemeManager::class);
            $themeData = $themeManager->getThemeData($themeManager->getCurrentTheme()) ?? [];

            // 1) Direct mappings
            $replacements = $themeData['asset_replacements'] ?? [];
            if (isset($replacements[$expression])) {
                return (string) $replacements[$expression];
            }

            // 2) Regex mappings
            $regexReplacements = $themeData['asset_module_replacements'] ?? [];
            foreach ($regexReplacements as $pattern => $replacement) {
                // suppress invalid pattern warnings
                $ok = @preg_match($pattern, $expression);
                if ($ok === 1) {
                    $new = @preg_replace($pattern, (string) $replacement, $expression);
                    if (is_string($new) && $new !== '') {
                        return $new;
                    }
                }
            }

            // 3) Wildcard mappings
            $wildcardReplacements = $themeData['asset_wildcard_replacements'] ?? [];
            foreach ($wildcardReplacements as $pattern => $replacement) {
                if (fnmatch($pattern, $expression)) {
                    $base = basename($expression);
                    return str_replace('*', $base, (string) $replacement);
                }
            }
        } catch (\Throwable $e) {
            logs('templates')->error('Asset replacement failed: ' . $e->getMessage());
        }

        return $expression;
    }

    /**
     * Load additional SCSS files to append from theme.json.
     * Expected structure in theme.json:
     * {
     *   "asset_scss_append": {
     *     "main": ["Themes/mytheme/assets/sass/overrides.scss", ...],
     *     "admin": ["Themes/mytheme/assets/sass/admin-overrides.scss", ...]
     *   }
     * }
     */
    private function loadThemeScssAppends(): void
    {
        try {
            /** @var ThemeManager $themeManager */
            $themeManager = app(ThemeManager::class);
            $themeData = $themeManager->getThemeData($themeManager->getCurrentTheme()) ?? [];

            $append = $themeData['asset_scss_append'] ?? [];

            // Backward-compatible: allow a flat array to mean current context
            if (isset($append[0]) && is_string($append[0])) {
                $append = [
                    $this->context => $append,
                ];
            }

            foreach (['main', 'admin'] as $ctx) {
                if (!empty($append[$ctx]) && is_array($append[$ctx])) {
                    foreach ($append[$ctx] as $expr) {
                        if (!is_string($expr) || trim($expr) === '') {
                            continue;
                        }

                        $resolved = $this->resolveFilePath($expr);
                        $this->addScssFile($resolved, $ctx);
                    }
                }
            }
        } catch (\Throwable $e) {
            logs('templates')->error('Failed to load theme SCSS appends: ' . $e->getMessage());
        }
    }

    /**
     * Extracts just the URL from a generated HTML tag.
     *
     * @param string $htmlTag The HTML tag containing the URL.
     * @return string The extracted URL.
     */
    private function extractUrl(string $htmlTag): string
    {
        if (preg_match('/(?:href|src)=["\'](.*?)["\']/i', $htmlTag, $matches)) {
            return $matches[1];
        }

        return $htmlTag;
    }

    /**
     * Resolves the file path for a given asset expression with fallback support.
     *
     * @param string $expression The relative or absolute path of the asset.
     * @return string The resolved file path.
     */
    private function resolveFilePath(string $expression): string
    {
        if (strpos($expression, BASE_PATH) !== false) {
            return $expression;
        }

        // Support expressions that already start with 'app/...'
        if (strpos($expression, 'app/') === 0) {
            return path($expression);
        }

        // Try to find with fallback for theme assets
        if (strpos($expression, 'Themes/') === 0) {
            $pathParts = explode('/', $expression);
            if (count($pathParts) >= 4) {
                $theme = $pathParts[1];
                $type = $pathParts[3]; // assets/scripts, assets/sass, etc.
                $relativePath = implode('/', array_slice($pathParts, 4));

                $foundPath = $this->findAssetWithFallback($relativePath, $type);
                if ($foundPath) {
                    return $foundPath;
                }
            }
        }

        return BASE_PATH . "app/" . $expression;
    }

    /**
     * Retrieves the file extension of an asset from its path or URL.
     *
     * @param string $expression The asset path or URL.
     * @param string $filePath The full path to the asset file.
     * @return string The file extension in lowercase.
     */
    private function getFileExtension(string $expression, string $filePath): string
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
    private function generateAssetUrl(string $path, bool $urlOnly = false): string
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
    private function processAssetBasedOnExtension(string $extension, string $expression, string $filePath, bool $urlOnly = false): string
    {
        switch ($extension) {
            case 'scss':
                return $this->processScssAsset($expression, $filePath);
            case 'css':
                return $this->processCssAsset($expression, $filePath);
            case 'js':
                return $this->processJsAsset($expression, $filePath, $urlOnly);
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
    public function addScssFile(string $path, string $context): void
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
    protected function getCacheDir(string $type): string
    {
        return "assets/{$type}/cache/{$this->context}/";
    }

    /**
     * Optimized SCSS compilation with enhanced caching and fallback.
     */
    private function processScssAsset(string $expression, string $scssPath): string
    {
        // Try fallback resolution if file doesn't exist
        if (!file_exists($scssPath)) {
            $pathParts = explode('/', $expression);
            if (count($pathParts) >= 4 && $pathParts[0] === 'Themes') {
                $relativePath = implode('/', array_slice($pathParts, 4));
                $fallbackPath = $this->findAssetWithFallback($relativePath, 'sass');
                if ($fallbackPath) {
                    $scssPath = $fallbackPath;
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }

        $cacheKey = sha1($scssPath . implode(',', $this->additionalScssFiles[$this->context]) . implode(',', $this->additionalPartials) . $this->context);

        // Check compilation cache first
        if (isset($this->compilationCache[$cacheKey]) && !$this->debugMode) {
            return $this->compilationCache[$cacheKey];
        }

        $cssCacheDir = $this->getCacheDir('css');
        $cssPath = $cssCacheDir . "{$cacheKey}.css";
        $cssFullPath = BASE_PATH . "public/" . $cssPath;

        $this->ensureDirectoryExists(dirname($cssFullPath));

        $needsRecompile = true;
        if (!is_development()) {
            $needsRecompile = !file_exists($cssFullPath) || filemtime($scssPath) > filemtime($cssFullPath) || $this->debugMode;
        }

        if (!$needsRecompile) {
            foreach ($this->additionalScssFiles[$this->context] as $additionalFile) {
                if (file_exists($additionalFile) && filemtime($additionalFile) > filemtime($cssFullPath)) {
                    $needsRecompile = true;

                    break;
                }
            }
        }

        if (!$needsRecompile) {
            foreach ($this->additionalPartials as $partial) {
                $partialPath = path($partial);
                if (file_exists($partialPath) && filemtime($partialPath) > filemtime($cssFullPath)) {
                    $needsRecompile = true;

                    break;
                }
            }
        }

        if ($needsRecompile) {
            $scssContents = $this->gatherScssContents($scssPath);
            $css = $this->compileScss($scssContents);

            if ($css !== '') {
                $this->saveAsset($cssFullPath, $css);
            }
        }

        $version = filemtime($cssFullPath);
        $url = url($cssPath) . "?v={$version}";
        $result = "<link href=\"{$url}\" rel=\"stylesheet\">";

        $this->compilationCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Gather SCSS contents with fallback support.
     *
     * @param string $mainScssPath
     * @return array
     */
    protected function gatherScssContents(string $mainScssPath): array
    {
        $scssContents = [];

        // Load shared partials first
        $partialsContent = $this->loadSharedPartials();
        $scssContents[] = $partialsContent;

        // Main SCSS content
        $mainScssContent = file_get_contents($mainScssPath);
        if ($mainScssContent === false) {
            logs()->error("Unable to read SCSS file: {$mainScssPath}");

            return [];
        }
        $scssContents[] = $mainScssContent;

        // Additional SCSS files for context
        foreach ($this->additionalScssFiles[$this->context] as $additionalFile) {
            if (file_exists($additionalFile)) {
                $additionalContent = file_get_contents($additionalFile);
                if ($additionalContent !== false) {
                    $scssContents[] = $additionalContent;
                } else {
                    logs()->warning("Unable to read additional SCSS file: {$additionalFile}");
                }
            }
        }

        return $scssContents;
    }

    /**
     * Compiles SCSS contents into CSS, catching any compilation errors.
     *
     * @param array $scssContents An array of SCSS content strings.
     * @return string The compiled CSS string.
     */
    private function compileScss(array $scssContents): string
    {
        $scssContent = implode("\n", $scssContents);

        $start = microtime(true);

        try {
            $css = $this->scssCompiler->compileString($scssContent)->getCss();

            self::$assetsCompileTime += microtime(true) - $start;

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
    private function loadSharedPartials(): string
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
    private function processCssAsset(string $expression, string $cssPathBase): string
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
     * Process JS asset with fallback support.
     */
    private function processJsAsset(string $expression, string $jsPathBase, bool $urlOnly = false): string
    {
        if (Validators::isUrl($expression)) {
            return $this->processRemoteAsset($expression, 'js');
        }

        // Try fallback resolution if file doesn't exist
        if (!file_exists($jsPathBase)) {
            $pathParts = explode('/', $expression);
            if (count($pathParts) >= 4 && $pathParts[0] === 'Themes') {
                $relativePath = implode('/', array_slice($pathParts, 4));
                $fallbackPath = $this->findAssetWithFallback($relativePath, 'scripts');
                if ($fallbackPath) {
                    $jsPathBase = $fallbackPath;
                } else {
                    return '';
                }
            } else {
                return '';
            }
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

        return $urlOnly ? $url : "<script src=\"{$url}\" defer></script>";
    }

    /**
     * Process image asset with fallback support.
     */
    private function processImageAsset(string $expression, string $imgPathBase, string $extension, bool $urlOnly = false): string
    {
        if (Validators::isUrl($expression)) {
            return $this->processRemoteAsset($expression, 'img');
        }

        // Try fallback resolution if file doesn't exist
        if (!file_exists($imgPathBase) || !in_array($extension, self::SUPPORTED_IMAGE_EXTENSIONS)) {
            $pathParts = explode('/', $expression);
            if (count($pathParts) >= 4 && $pathParts[0] === 'Themes') {
                $relativePath = implode('/', array_slice($pathParts, 4));
                $fallbackPath = $this->findAssetWithFallback($relativePath, 'images');
                if ($fallbackPath && in_array($extension, self::SUPPORTED_IMAGE_EXTENSIONS)) {
                    $imgPathBase = $fallbackPath;
                } else {
                    return 'not found';
                }
            } else {
                return 'not found';
            }
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
    protected function processRemoteAsset(string $url, string $type): string
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
    protected function isLocalUrl(string $url): bool
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
    protected function generateTag(string $url, string $type): string
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
    protected function processCdnAsset(string $url, string $type = "js"): string
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
    protected function saveAsset(string $path, string $content): void
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
    protected function copyAsset(string $sourcePath, string $destinationPath): void
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
    protected function minifyContent(string $path, string $content): string
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

                try {
                    $content = $autoprefixer->compile();
                } catch (\Throwable $e) {
                    logs()->error("Autoprefixer failed: " . $e->getMessage());
                }
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
    public function getCompiler(): Compiler
    {
        return $this->scssCompiler;
    }

    /**
     * Get the accumulated time spent on compiling/minifying theme assets (scss, js, etc.)
     *
     * @return float
     */
    public static function getAssetsCompileTime(): float
    {
        return self::$assetsCompileTime;
    }

    /**
     * Ensure that a specified directory exists, creating it if necessary.
     *
     * @param string $directory The path of the directory to check or create.
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }
    }

    /**
     * Add import path with context support.
     */
    public function addImportPath(string $path, string $context = 'main'): void
    {
        if ($context === $this->context && is_dir($path)) {
            $this->scssCompiler->addImportPath($path);
        }
    }

    /**
     * Clear all caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->assetPathCache = [];
        $this->compilationCache = [];
        $this->fallbackAssetPaths = [];
    }

    /**
     * Clear style cache files and internal caches.
     *
     * @return void
     */
    public function clearStyleCache(): void
    {
        $cssCachePath = BASE_PATH . '/public/assets/css/cache/*';
        $filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $filesystem->remove(glob($cssCachePath));
        $this->clearCache();
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        return [
            'asset_path_cache_size' => count($this->assetPathCache),
            'compilation_cache_size' => count($this->compilationCache),
            'debug_mode' => $this->debugMode,
            'context' => $this->context,
        ];
    }
}
