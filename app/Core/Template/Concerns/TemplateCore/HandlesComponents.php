<?php

namespace Flute\Core\Template\Concerns\TemplateCore;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait HandlesComponents
{
    public function getBladeFiles(string $dir): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isFile() && strpos($file->getFilename(), '.blade.php') !== false) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    protected function loadComponents(): void
    {
        if ($this->isAdminPath() || !is_installed()) {
            return;
        }

        $cacheKey = "flute.template.components.{$this->currentTheme}";

        if (isset($this->componentCache[$cacheKey])) {
            $this->registerCachedComponents($this->componentCache[$cacheKey]);
            $this->setupThemeNamespaces();

            return;
        }

        $components = cache()->get($cacheKey);

        if ($components === null) {
            $components = [];
            $themes = $this->getThemeFallbackOrder();

            foreach ($themes as $theme) {
                $componentsDir = $this->getTemplatePath("Themes/{$theme}/views/components");

                if (is_dir($componentsDir)) {
                    $themeComponents = $this->discoverComponents($componentsDir, $theme);

                    foreach ($themeComponents as $alias => $componentData) {
                        if (!isset($components[$alias])) {
                            $components[$alias] = $componentData;
                        }
                    }
                }
            }

            cache()->set($cacheKey, $components, 3600);
        }

        $this->addToComponentCache($cacheKey, $components);
        $this->registerCachedComponents($components);

        $this->setupThemeNamespaces();
    }

    protected function discoverComponents(string $componentsDir, string $theme): array
    {
        $components = [];
        $componentFiles = $this->getBladeFiles($componentsDir);

        foreach ($componentFiles as $componentFile) {
            $relativePath = str_replace([$componentsDir . DIRECTORY_SEPARATOR, '.blade.php'], '', $componentFile);
            $alias = str_replace(DIRECTORY_SEPARATOR, '.', $relativePath);
            $componentView = "Themes.{$theme}.views.components." . $alias;

            $components[$alias] = [
                'view' => $componentView,
                'theme' => $theme,
                'path' => $componentFile,
            ];
        }

        return $components;
    }

    protected function registerCachedComponents(array $components): void
    {
        foreach ($components as $alias => $componentData) {
            $this->blade->compiler()->component($componentData['view'], $alias);
        }
    }

    protected function setupThemeNamespaces(): void
    {
        static $initialized = false;

        if ($initialized) {
            return;
        }

        $themes = $this->getThemeFallbackOrder();
        $viewPaths = [];

        foreach ($themes as $theme) {
            $themePath = $this->getTemplatePath("Themes/{$theme}/views");
            if (is_dir($themePath)) {
                $viewPaths[] = $themePath;
            }
        }

        if (!empty($viewPaths)) {
            $this->addNamespace('flute', $viewPaths);
        }

        foreach ($themes as $theme) {
            $sassPath = $this->getTemplatePath("Themes/{$theme}/assets/sass");
            if (is_dir($sassPath)) {
                $this->templateAssets->addImportPath($sassPath);
            }
        }

        $initialized = true;
    }
}
