<?php

namespace Flute\Core\Template;

/**
 * Utility class for resolving theme fallbacks efficiently.
 */
class ThemeFallbackResolver
{
    protected const CACHE_LIMIT = 2000;

    protected static array $pathCache = [];

    protected static array $themeCache = [];

    /**
     * Resolve file path with fallback across themes.
     */
    public static function resolveFile(string $relativePath, array $themes, string $type = 'views', string $basePath = ''): ?string
    {
        $cacheKey = self::getCacheKey($relativePath, $themes, $type);

        if (isset(self::$pathCache[$cacheKey])) {
            return self::$pathCache[$cacheKey];
        }

        $fullBasePath = $basePath ?: BASE_PATH . 'app/';

        foreach ($themes as $theme) {
            $filePath = $fullBasePath . "Themes/{$theme}/{$type}/{$relativePath}";

            if (file_exists($filePath)) {
                self::cacheResult($cacheKey, $filePath);

                return $filePath;
            }
        }

        self::cacheResult($cacheKey, null);

        return null;
    }

    /**
     * Resolve multiple files at once for better performance.
     */
    public static function resolveMultipleFiles(array $files, array $themes, string $type = 'views', string $basePath = ''): array
    {
        $results = [];

        foreach ($files as $file) {
            $results[$file] = self::resolveFile($file, $themes, $type, $basePath);
        }

        return $results;
    }

    /**
     * Check if theme extends another theme.
     */
    public static function getParentTheme(string $theme, string $basePath = ''): ?string
    {
        $cacheKey = "parent:{$theme}";

        if (isset(self::$themeCache[$cacheKey])) {
            return self::$themeCache[$cacheKey];
        }

        $fullBasePath = $basePath ?: BASE_PATH . 'app/';
        $themeConfigPath = $fullBasePath . "Themes/{$theme}/theme.json";

        if (!file_exists($themeConfigPath)) {
            self::$themeCache[$cacheKey] = null;

            return null;
        }

        $config = json_decode(file_get_contents($themeConfigPath), true);
        $parent = $config['extends'] ?? null;

        self::$themeCache[$cacheKey] = $parent;

        return $parent;
    }

    /**
     * Get complete theme hierarchy including parent themes.
     */
    public static function getThemeHierarchy(string $theme, string $standardTheme = 'standard', string $basePath = ''): array
    {
        $hierarchy = [$theme];
        $visited = [$theme]; // Prevent infinite loops

        $currentTheme = $theme;
        while ($parent = self::getParentTheme($currentTheme, $basePath)) {
            if (in_array($parent, $visited)) {
                break; // Prevent circular dependencies
            }

            $hierarchy[] = $parent;
            $visited[] = $parent;
            $currentTheme = $parent;
        }

        // Always add standard theme as final fallback
        if (!in_array($standardTheme, $hierarchy)) {
            $hierarchy[] = $standardTheme;
        }

        return $hierarchy;
    }

    /**
     * Scan directory for files matching pattern.
     */
    public static function scanDirectory(string $directory, string $pattern = '*.blade.php'): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $cacheKey = "scan:{$directory}:{$pattern}";

        if (isset(self::$pathCache[$cacheKey])) {
            return self::$pathCache[$cacheKey];
        }

        $files = glob($directory . '/' . $pattern, GLOB_BRACE);
        $files = $files ?: [];

        $subdirs = glob($directory . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $subFiles = self::scanDirectory($subdir, $pattern);
            $files = array_merge($files, $subFiles);
        }

        self::cacheResult($cacheKey, $files);

        return $files;
    }

    /**
     * Clear all caches.
     */
    public static function clearCache(): void
    {
        self::$pathCache = [];
        self::$themeCache = [];
    }

    /**
     * Get cache statistics.
     */
    public static function getCacheStats(): array
    {
        return [
            'path_cache_size' => count(self::$pathCache),
            'theme_cache_size' => count(self::$themeCache),
            'memory_usage' => memory_get_usage(true),
        ];
    }

    /**
     * Generate cache key.
     */
    protected static function getCacheKey(string $relativePath, array $themes, string $type): string
    {
        return hash('xxh64', $relativePath . ':' . implode(',', $themes) . ':' . $type);
    }

    /**
     * Cache result with size limit.
     *
     * @param mixed $value
     */
    protected static function cacheResult(string $key, $value): void
    {
        if (count(self::$pathCache) >= self::CACHE_LIMIT) {
            $keysToRemove = array_slice(array_keys(self::$pathCache), 0, 500);
            foreach ($keysToRemove as $oldKey) {
                unset(self::$pathCache[$oldKey]);
            }
        }

        self::$pathCache[$key] = $value;
    }
}
