<?php

namespace Flute\Core\Support\Concerns;

use Flute\Admin\AdminPanel;
use Flute\Admin\Contracts\AdminPackageInterface;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Services\ConfigurationService;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

trait HandlesModuleResources
{
    public function loadRoutesFrom(string $path): void
    {
        $fullPath = path($path);

        if (!is_file($fullPath)) {
            if (function_exists('logs')) {
                logs()->warning("Routes from {$path} wasn't found");
            }

            return;
        }

        require $fullPath;
    }

    public function loadRoutes(): void
    {
        $routesCacheKey = "module.{$this->getModuleName()}.routes_loaded";
        if (isset($this->loadedStatus[$routesCacheKey])) {
            return;
        }

        $routesPath = "app/Modules/{$this->getModuleName()}/routes.php";
        if (file_exists(path($routesPath))) {
            require path($routesPath);
            $this->loadedStatus[$routesCacheKey] = true;
        }
    }

    public function loadEntities(): void
    {
        $entitiesCacheKey = "module.{$this->getModuleName()}.entities_loaded";
        if (isset($this->loadedStatus[$entitiesCacheKey])) {
            return;
        }

        try {
            $entDir = $this->getModulePath('database/Entities');
            if (!is_dir($entDir)) {
                return;
            }

            $cacheKey = "module.{$this->getModuleName()}.entities_dir";
            cache()->tagKey("module.{$this->getModuleName()}", $cacheKey);
            $hasEntities = cache()->callback(
                $cacheKey,
                static function () use ($entDir) {
                    $finder = finder();
                    $finder->files()->in($entDir)->name('*.php');

                    return $finder->count() > 0;
                },
                $this->defaultCacheDuration,
            );

            if ($hasEntities) {
                $db = app(DatabaseConnection::class);
                $db->addDir($entDir);
                $this->loadedStatus[$entitiesCacheKey] = true;
            }
        } catch (DirectoryNotFoundException $e) {
            logs('modules')->warning("Directory not found for module {$this->getModuleName()}: " . $e->getMessage());
        } catch (\Throwable $e) {
            logs('modules')->error("Error loading entities for module {$this->getModuleName()}: " . $e->getMessage());
        }
    }

    public function loadPackage(AdminPackageInterface $package): void
    {
        if (!is_admin_path() || !user()->can('admin')) {
            return;
        }

        try {
            app(AdminPanel::class)->registerPackage($package);
        } catch (\Throwable $e) {
            if (is_debug()) {
                throw $e;
            }

            logs('modules')->error('Failed to load package ' . $package::class . ': ' . $e->getMessage());
        }
    }

    public function loadComponent(string $component, string $name): void
    {
        template()->registerComponent($name, $component);
    }

    public function loadTranslations(): void
    {
        $translationsCacheKey = "module.{$this->getModuleName()}.translations_loaded";
        if (isset($this->loadedStatus[$translationsCacheKey])) {
            return;
        }

        $translationsDir = $this->getModulePath('Resources/lang');
        if (!is_dir($translationsDir)) {
            return;
        }

        translation()->loadTranslationsFromDirectory($translationsDir, $this->defaultCacheDuration);
        $this->loadedStatus[$translationsCacheKey] = true;
    }

    public function loadConfigs(): void
    {
        $configsCacheKey = "module.{$this->getModuleName()}.configs_loaded";
        if (isset($this->loadedStatus[$configsCacheKey])) {
            return;
        }

        $configDir = $this->getModulePath('Resources/config');
        if (!is_dir($configDir)) {
            return;
        }

        $cacheKey = "module.{$this->getModuleName()}.configs.v2." . md5($configDir);
        cache()->tagKey("module.{$this->getModuleName()}", $cacheKey);
        $configFiles = cache()->callback(
            $cacheKey,
            static function () use ($configDir) {
                $finder = finder();
                $finder->files()->in($configDir)->name('*.php');

                $files = [];
                foreach ($finder as $file) {
                    $files[] = [
                        'relative' => $file->getRelativePathname(),
                        'name' => basename($file->getFilename(), '.php'),
                    ];
                }

                return $files;
            },
            $this->defaultCacheDuration,
        );

        if (!empty($configFiles)) {
            $configService = app(ConfigurationService::class);

            foreach ($configFiles as $file) {
                $path = $configDir . DIRECTORY_SEPARATOR . $file['relative'];
                if (!is_file($path)) {
                    logs('modules')->warning("Module config file not found: {$path}");

                    continue;
                }

                $configService->loadCustomConfig($path, $file['name']);
            }

            $this->loadedStatus[$configsCacheKey] = true;
        }
    }
}
