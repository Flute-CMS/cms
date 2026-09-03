<?php

namespace Flute\Core\Template\Concerns\TemplateCore;

use Flute\Core\Template\ThemeFallbackResolver;
use Flute\Core\Theme\ThemeManager;
use RuntimeException;
use Throwable;

trait HandlesThemeCache
{
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

    protected function clearThemeCache(): void
    {
        $this->componentCache = [];
        $this->pathCache = [];
        $this->fallbackPaths = [];
        $this->cachedFallbackOrder = null;
    }

    protected function addToPathCache(string $key, $value): void
    {
        if (count($this->pathCache) >= $this->maxPathCacheSize) {
            array_shift($this->pathCache);
        }
        $this->pathCache[$key] = $value;
    }

    protected function addToComponentCache(string $key, $value): void
    {
        if (count($this->componentCache) >= $this->maxComponentCacheSize) {
            array_shift($this->componentCache);
        }
        $this->componentCache[$key] = $value;
    }

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

    protected function getThemeFallbackOrder(): array
    {
        if ($this->cachedFallbackOrder !== null) {
            return $this->cachedFallbackOrder;
        }

        return $this->cachedFallbackOrder = ThemeFallbackResolver::getThemeHierarchy(
            $this->currentTheme,
            $this->standardTheme,
        );
    }

    protected function initTheme(): void
    {
        try {
            $this->currentTheme = $this->themeManager->getCurrentTheme();
            $this->themeData = $this->themeManager->getThemeData($this->currentTheme) ?? [];

            if (empty($this->themeData)) {
                $this->fallbackToDefaultTheme();
            }
        } catch (Throwable $e) {
            logs('templates')->error('Failed to initialize theme: ' . $e->getMessage());
            $this->fallbackToDefaultTheme();
        }

        app()->setTheme($this->currentTheme);
    }

    protected function fallbackToDefaultTheme(): void
    {
        $defaultTheme = ThemeManager::DEFAULT_THEME;

        try {
            $this->themeManager->setTheme($defaultTheme);
            $this->currentTheme = $defaultTheme;
            $this->themeData = $this->themeManager->getThemeData($defaultTheme) ?? [];
            logs('templates')->warning("Fallback to default theme '{$defaultTheme}' due to invalid current theme.");
        } catch (Throwable $e) {
            throw new RuntimeException("Default theme '{$defaultTheme}' is not available. " . $e->getMessage());
        }
    }

    public function resolveViewReplacement(string $viewPath): string
    {
        return $this->searchReplacementForInterface($viewPath);
    }

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

    protected function resolveTemplatePath(string $path): string
    {
        return str_replace(['.blade.php', '/'], ['', '.'], $path);
    }
}
