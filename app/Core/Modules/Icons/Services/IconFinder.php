<?php

namespace Flute\Core\Modules\Icons\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IconFinder
{
    /**
     * Static in-request cache for loaded SVG contents to avoid repeated disk I/O.
     */
    private static array $fileContentCache = [];

    /**
     * In-request cache for directory scan results (icon lists).
     */
    private static array $iconListCache = [];

    /**
     */
    private Collection $directories;

    /**
     */
    private string $width = '1em';

    /**
     */
    private string $height = '1em';

    /**
     * IconFinder constructor.
     */
    public function __construct()
    {
        $this->directories = collect();
    }

    /**
     *
     */
    public function registerIconDirectory(string $prefix, string $directory): self
    {
        $this->directories = $this->directories->merge([
            $prefix => realpath($directory),
        ]);

        return $this;
    }

    /**
     *
     */
    public function loadFile(string $name): ?string
    {
        if (Str::contains($name, 'svg')) {
            $decoded = html_entity_decode($name, ENT_QUOTES | ENT_HTML5);
            if (Str::startsWith(trim($decoded), '<svg') && Str::contains($decoded, ['</svg>']) && !Str::contains(strtolower($decoded), ['<script', 'onload=', 'onerror='])) {
                return $decoded;
            }
        }

        $prefix = Str::of($name)->before('.')->toString();
        $dir = $this->directories->get($prefix);

        if ($dir !== null) {
            return $this->getContent($name, $prefix, $dir);
        }

        // Failed to find the icon
        return $this->directories
            ->map(fn ($dir) => $this->getContent($name, $prefix, $dir))
            ->filter()
            ->first();
    }

    /**
     * @return $this
     */
    public function setSize(string $width = '1em', string $height = '1em'): IconFinder
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * Return the default width.
     */
    public function getDefaultWidth(): string
    {
        return $this->width;
    }

    /**
     * Return the default height.
     */
    public function getDefaultHeight(): string
    {
        return $this->height;
    }

    /**
     * Get list of all available icons in the package.
     *
     * @param string $prefix Prefix of the icon package
     * @param string|null $category Category inside the package
     * @return array Array of icon names or categorized icons
     */
    public function getIconsInPackage(string $prefix, ?string $category = null): array
    {
        $cacheKey = $prefix . '|' . ($category ?? '*');

        if (isset(self::$iconListCache[$cacheKey])) {
            return self::$iconListCache[$cacheKey];
        }

        $dir = $this->directories->get($prefix);

        if (!$dir) {
            return [];
        }

        $icons = [];

        try {
            if ($category) {
                $categoryDir = $dir . DIRECTORY_SEPARATOR . $category;
                if (is_dir($categoryDir)) {
                    $pattern = $categoryDir . DIRECTORY_SEPARATOR . '*.svg';
                    foreach (glob($pattern) as $file) {
                        $icons[] = $category . '.' . pathinfo($file, PATHINFO_FILENAME);
                    }
                }
            } else {
                // First-level SVGs
                foreach (glob($dir . DIRECTORY_SEPARATOR . '*.svg') as $file) {
                    $icons[] = pathinfo($file, PATHINFO_FILENAME);
                }
                // Category subdirectories
                foreach (glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $catDir) {
                    $categoryName = basename($catDir);
                    foreach (glob($catDir . DIRECTORY_SEPARATOR . '*.svg') as $file) {
                        $icons[] = $categoryName . '.' . pathinfo($file, PATHINFO_FILENAME);
                    }
                }
            }
        } catch (Exception $e) {
        }

        self::$iconListCache[$cacheKey] = $icons;

        return $icons;
    }

    /**
     * Get categories in the icon package.
     *
     * @param string $prefix Prefix of the icon package
     * @return array Array of category names
     */
    public function getCategoriesInPackage(string $prefix): array
    {
        $categories = [];
        $dir = $this->directories->get($prefix);

        if (!$dir) {
            return $categories;
        }

        try {
            if (is_dir($dir)) {
                $items = scandir($dir);
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }

                    $path = $dir . DIRECTORY_SEPARATOR . $item;
                    if (is_dir($path)) {
                        $categories[] = $item;
                    }
                }
            }
        } catch (Exception $e) {
        }

        return $categories;
    }

    /**
     * Get list of all registered icon packages.
     *
     * @return array Array of package prefixes
     */
    public function getPackages(): array
    {
        return $this->directories->keys()->toArray();
    }

    /**
     * @return string
     */
    protected function getContent(string $name, string $prefix, string $dir)
    {
        $file = Str::of($name)
            ->when($prefix !== $name, static fn ($string) => $string->replaceFirst($prefix, ''))
            ->replaceFirst('.', '')
            ->replace('.', DIRECTORY_SEPARATOR);

        $path = $dir . DIRECTORY_SEPARATOR . $file . '.svg';

        if (isset(self::$fileContentCache[$path])) {
            return self::$fileContentCache[$path];
        }

        if (!is_file($path)) {
            self::$fileContentCache[$path] = null;

            return null;
        }

        try {
            $content = file_get_contents($path);
            if ($content !== false) {
                self::$fileContentCache[$path] = $content;
            }

            return $content ?: null;
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Recursively scans a directory and returns all files.
     *
     * @param string $dir Path to the directory
     * @return array Array of file paths
     */
    protected function scanDirectory(string $dir): array
    {
        $files = [];

        if (!is_dir($dir)) {
            return $files;
        }

        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $files = array_merge($files, $this->scanDirectory($path));
            } else {
                $files[] = $path;
            }
        }

        return $files;
    }
}
