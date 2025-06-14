<?php

namespace Flute\Core\Modules\Icons\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IconFinder
{
    /**
     * @var Collection
     */
    private Collection $directories;

    /**
     * @var string
     */
    private string $width = '1em';

    /**
     * @var string
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
     * @param string $directory
     * @param string $prefix
     *
     * @return self
     */
    public function registerIconDirectory(string $prefix, string $directory): self
    {
        $this->directories = $this->directories->merge([
            $prefix => realpath($directory),
        ]);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function loadFile(string $name): ?string
    {
        if (Str::contains($name, 'svg')) {
            $decoded = html_entity_decode($name, ENT_QUOTES | ENT_HTML5);

            if (Str::contains($decoded, ['<svg']) && Str::contains($decoded, ['</svg>'])) {
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
            ->map(fn($dir) => $this->getContent($name, $prefix, $dir))
            ->filter()
            ->first();
    }

    /**
     * @param string $name
     * @param string $prefix
     * @param string $dir
     *
     * @return string
     */
    protected function getContent(string $name, string $prefix, string $dir)
    {
        $file = Str::of($name)
            ->when($prefix !== $name, fn($string) => $string->replaceFirst($prefix, ''))
            ->replaceFirst('.', '')
            ->replace('.', DIRECTORY_SEPARATOR);

        $path = $dir . DIRECTORY_SEPARATOR . $file . '.svg';

        try {
            return file_get_contents($path);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param string $width
     * @param string $height
     *
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
     *
     * @return string
     */
    public function getDefaultWidth(): string
    {
        return $this->width;
    }

    /**
     * Return the default height.
     *
     * @return string
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
        $icons = [];
        $dir = $this->directories->get($prefix);

        if (!$dir) {
            return $icons;
        }

        try {
            if ($category) {
                $categoryDir = $dir . DIRECTORY_SEPARATOR . $category;
                if (is_dir($categoryDir)) {
                    $files = $this->scanDirectory($categoryDir);
                    foreach ($files as $file) {
                        if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
                            $relativePath = str_replace($categoryDir . DIRECTORY_SEPARATOR, '', $file);
                            $iconName = pathinfo($relativePath, PATHINFO_FILENAME);
                            $icons[] = $category . '.' . $iconName;
                        }
                    }
                }
            } else {
                $files = $this->scanDirectory($dir);
                foreach ($files as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
                        $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $file);
                        $iconName = pathinfo($relativePath, PATHINFO_FILENAME);
                        $pathParts = explode(DIRECTORY_SEPARATOR, $relativePath);

                        if (count($pathParts) > 1) {
                            $categoryName = $pathParts[0];
                            $icons[] = $categoryName . '.' . $iconName;
                        } else {
                            $icons[] = $iconName;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }

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
        } catch (\Exception $e) {
        }

        return $categories;
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

    /**
     * Get list of all registered icon packages.
     *
     * @return array Array of package prefixes
     */
    public function getPackages(): array
    {
        return $this->directories->keys()->toArray();
    }
}
