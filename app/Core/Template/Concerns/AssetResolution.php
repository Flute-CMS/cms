<?php

namespace Flute\Core\Template\Concerns;

use Flute\Core\Theme\ThemeManager;
use Throwable;

trait AssetResolution
{
    public function assetFunction(string $expression, bool $urlOnly = false): string
    {
        $expression = $this->applyAssetReplacement($expression);

        $filePath = $this->resolveFilePath($expression);
        $extension = $this->getFileExtension($expression, $filePath);
        $pathParts = explode('/', $expression);
        $firstSegment = $pathParts[0] ?? '';

        if ($firstSegment === 'assets') {
            $url = $this->generateAssetUrl($expression);

            return $urlOnly ? $this->extractUrl($url) : $url;
        }

        return $this->processAssetBasedOnExtension($extension, $expression, $filePath, $urlOnly);
    }

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

    protected function getThemeFallbackOrder(): array
    {
        if ($this->cachedThemeFallbackOrder !== null) {
            return $this->cachedThemeFallbackOrder;
        }

        $currentTheme = app(ThemeManager::class)->getCurrentTheme() ?? $this->standardTheme;
        $themes = [$currentTheme];

        if ($currentTheme !== $this->standardTheme) {
            $themes[] = $this->standardTheme;
        }

        return $this->cachedThemeFallbackOrder = $themes;
    }

    private function applyAssetReplacement(string $expression): string
    {
        $expression = str_replace(['/', '\\'], '/', $expression);
        $normalizeBasePath = str_replace(['/', '\\'], '/', BASE_PATH);

        $expression = str_replace([$normalizeBasePath, '/app/'], ['', ''], $expression);

        try {
            /** @var ThemeManager $themeManager */
            $themeManager = app(ThemeManager::class);
            $themeData = $themeManager->getThemeData($themeManager->getCurrentTheme()) ?? [];

            $replacements = $themeData['asset_replacements'] ?? [];
            if (isset($replacements[$expression])) {
                return (string) $replacements[$expression];
            }

            $regexReplacements = $themeData['asset_module_replacements'] ?? [];
            foreach ($regexReplacements as $pattern => $replacement) {
                $ok = @preg_match($pattern, $expression);
                if ($ok === 1) {
                    $new = @preg_replace($pattern, (string) $replacement, $expression);
                    if (is_string($new) && $new !== '') {
                        return $new;
                    }
                }
            }

            $wildcardReplacements = $themeData['asset_wildcard_replacements'] ?? [];
            foreach ($wildcardReplacements as $pattern => $replacement) {
                if (fnmatch($pattern, $expression)) {
                    $base = basename($expression);

                    return str_replace('*', $base, (string) $replacement);
                }
            }
        } catch (Throwable $e) {
            logs('templates')->error('Asset replacement failed: ' . $e->getMessage());
        }

        return $expression;
    }

    private function loadThemeScssAppends(): void
    {
        try {
            /** @var ThemeManager $themeManager */
            $themeManager = app(ThemeManager::class);
            $themeData = $themeManager->getThemeData($themeManager->getCurrentTheme()) ?? [];

            $append = $themeData['asset_scss_append'] ?? [];

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
        } catch (Throwable $e) {
            logs('templates')->error('Failed to load theme SCSS appends: ' . $e->getMessage());
        }
    }

    private function extractUrl(string $htmlTag): string
    {
        if (preg_match('/(?:href|src)=["\'](.*?)["\']/i', $htmlTag, $matches)) {
            return $matches[1];
        }

        return $htmlTag;
    }

    private function resolveFilePath(string $expression): string
    {
        if (str_contains($expression, BASE_PATH)) {
            return $expression;
        }

        if (str_starts_with($expression, 'app/')) {
            return path($expression);
        }

        if (str_starts_with($expression, 'Themes/')) {
            $pathParts = explode('/', $expression);
            if (count($pathParts) >= 4) {
                $theme = $pathParts[1];
                $type = $pathParts[3];
                $relativePath = implode('/', array_slice($pathParts, 4));

                $foundPath = $this->findAssetWithFallback($relativePath, $type);
                if ($foundPath) {
                    return $foundPath;
                }
            }
        }

        return BASE_PATH . 'app/' . $expression;
    }

    private function getFileExtension(string $expression, string $filePath): string
    {
        $path = parse_url($expression, PHP_URL_PATH) ?: $filePath;

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    private function generateAssetUrl(string $path, bool $urlOnly = false): string
    {
        $url = $this->buildPublicAssetUrl($path);
        if ($url === '') {
            return '';
        }

        if ($urlOnly) {
            return $url;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = $this->getTagTypeFromExtension($extension);
        if ($type === '') {
            return '';
        }

        return $this->generateTag($url, $type);
    }

    private function processAssetBasedOnExtension(
        string $extension,
        string $expression,
        string $filePath,
        bool $urlOnly = false,
    ): string {
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

    private function getTagTypeFromExtension(string $extension): string
    {
        $extension = strtolower($extension);

        return self::EXTENSION_TO_TYPE[$extension] ?? '';
    }

    private function buildPublicAssetUrl(string $relativePublicPath): string
    {
        $relativePublicPath = ltrim(str_replace('\\', '/', $relativePublicPath), '/');
        $fullPath = BASE_PATH . "public/{$relativePublicPath}";
        if (!file_exists($fullPath)) {
            return '';
        }

        $version = filemtime($fullPath);

        return url($relativePublicPath) . "?v={$version}";
    }
}
