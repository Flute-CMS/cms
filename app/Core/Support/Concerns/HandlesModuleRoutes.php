<?php

namespace Flute\Core\Support\Concerns;

use InvalidArgumentException;

trait HandlesModuleRoutes
{
    public function loadViewPages(): void
    {
        $pagesDir = $this->getModulePath('Resources/pages');
        if (!is_dir($pagesDir)) {
            return;
        }

        $viewPagesCacheKey = "module.{$this->getModuleName()}.view_pages_loaded";
        if (isset($this->loadedStatus[$viewPagesCacheKey])) {
            return;
        }

        $namespace = $this->kebabCase($this->getModuleName());
        $this->loadViews('Resources/pages', $namespace);

        $this->recursivelyRegisterViewPages($pagesDir, '');
        $this->loadedStatus[$viewPagesCacheKey] = true;
    }

    public function loadRouterAttributes(): void
    {
        $attributesCacheKey = "module.{$this->getModuleName()}.router_attributes_loaded";
        if (isset($this->loadedStatus[$attributesCacheKey])) {
            return;
        }

        $moduleRoot = $this->getModulePath();
        if (!is_dir($moduleRoot)) {
            logs('modules')->warning(
                "Module {$this->getModuleName()} is registered but its directory is missing; skipping attribute routes.",
            );

            return;
        }

        $httpControllersPath = $this->getModulePath('Http/Controllers');
        $controllersPath = $this->getModulePath('Controllers');

        if (!is_dir($httpControllersPath) && !is_dir($controllersPath)) {
            return;
        }

        try {
            if (is_dir($httpControllersPath)) {
                $moduleNamespace = "Flute\\Modules\\{$this->getModuleName()}\\Http\\Controllers";
                router()->registerAttributeRoutes([$httpControllersPath], $moduleNamespace);
            }

            if (is_dir($controllersPath)) {
                $moduleNamespace = "Flute\\Modules\\{$this->getModuleName()}\\Controllers";
                router()->registerAttributeRoutes([$controllersPath], $moduleNamespace);
            }

            $submodulesPath = $this->getModulePath('Submodules');

            if (is_dir($submodulesPath)) {
                $cacheKey = "module.{$this->getModuleName()}.submodules.v2." . md5($submodulesPath);
                cache()->tagKey("module.{$this->getModuleName()}", $cacheKey);
                $submodules = cache()->callback(
                    $cacheKey,
                    function () use ($submodulesPath) {
                        $result = [];
                        foreach ($this->getFilesFromDirectory($submodulesPath) as $submodule) {
                            if (!$submodule->isDir()) {
                                continue;
                            }

                            $controllersRelativePath = $submodule->getBasename() . '/Controllers';
                            $controllersPath = $submodulesPath . '/' . $controllersRelativePath;
                            if (!is_dir($controllersPath)) {
                                continue;
                            }

                            $result[] = [
                                'name' => $submodule->getBasename(),
                                'controllers_relative_path' => $controllersRelativePath,
                            ];
                        }

                        return $result;
                    },
                    $this->defaultCacheDuration,
                );

                foreach ($submodules as $submodule) {
                    $subControllersPath = $submodulesPath . '/' . $submodule['controllers_relative_path'];
                    if (!is_dir($subControllersPath)) {
                        continue;
                    }

                    $submoduleNamespace =
                        "Flute\\Modules\\{$this->getModuleName()}\\Submodules\\" . $submodule['name'] . "\\Controllers";
                    router()->registerAttributeRoutes([$subControllersPath], $submoduleNamespace);
                }
            }

            $this->loadedStatus[$attributesCacheKey] = true;
        } catch (\Throwable $e) {
            logs()->error("Error loading route attributes in module {$this->getModuleName()}: " . $e->getMessage());
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function loadViews(string $viewDirectory, string $namespace): void
    {
        $viewsCacheKey = "module.{$this->getModuleName()}.views_{$namespace}_loaded";
        if (isset($this->loadedStatus[$viewsCacheKey])) {
            return;
        }

        $fullPath = $this->getModulePath($viewDirectory);

        if (!is_dir($fullPath)) {
            if (function_exists('logs')) {
                logs()->warning("View directory does not exist: {$fullPath}");
            }

            return;
        }

        $this->namespace = $namespace;
        template()->addNamespace($namespace, $fullPath);
        $this->loadedStatus[$viewsCacheKey] = true;
    }

    protected function recursivelyRegisterViewPages(string $directory, string $prefix): void
    {
        $cacheKey = "module.{$this->getModuleName()}.view_pages." . md5($directory . $prefix);

        cache()->tagKey("module.{$this->getModuleName()}", $cacheKey);

        $viewPageData = cache()->callback(
            $cacheKey,
            function () use ($directory, $prefix) {
                $namespace = $this->kebabCase($this->getModuleName());
                $routes = [];

                foreach ($this->getFilesFromDirectory($directory) as $file) {
                    $relativePath = ltrim(
                        str_replace($this->getModulePath('Resources/pages'), '', $file->getPathname()),
                        DIRECTORY_SEPARATOR,
                    );

                    if ($file->isDir()) {
                        continue;
                    }

                    if ($file->getExtension() !== 'php') {
                        continue;
                    }

                    $filename = str_replace('.blade', '', pathinfo($file->getBasename(), PATHINFO_FILENAME));
                    $routeUri = ltrim($prefix . ( $filename === 'index' ? '' : '/' . $filename ), '/');
                    $viewPath = str_replace('.blade.php', '', $relativePath);

                    $routes[] = [
                        'uri' => $routeUri,
                        'view' => $namespace . '::' . $viewPath,
                    ];
                }

                $subDirs = [];
                foreach ($this->getFilesFromDirectory($directory) as $file) {
                    if ($file->isDir()) {
                        $newPrefix = trim($prefix . '/' . $file->getBasename(), '/');
                        $subDirs[] = [
                            'path' => $file->getPathname(),
                            'prefix' => $newPrefix,
                        ];
                    }
                }

                return [
                    'routes' => $routes,
                    'subDirs' => $subDirs,
                ];
            },
            $this->defaultCacheDuration,
        );

        foreach ($viewPageData['routes'] as $route) {
            router()->view($route['uri'], $route['view']);
        }

        foreach ($viewPageData['subDirs'] as $subDir) {
            $this->recursivelyRegisterViewPages($subDir['path'], $subDir['prefix']);
        }
    }
}
