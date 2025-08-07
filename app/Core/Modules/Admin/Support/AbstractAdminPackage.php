<?php

namespace Flute\Admin\Support;

use Flute\Admin\Contracts\AdminPackageInterface;
use InvalidArgumentException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

/**
 * Class AbstractAdminPackage
 *
 * Provides a base implementation for admin packages.
 */
abstract class AbstractAdminPackage implements AdminPackageInterface
{
    protected int $defaultCacheDuration = 36000;
    private array $loaded = [];

    /**
     * Already flushed locales in current request to avoid repeated cache deletion.
     * @var array<string,bool>
     */
    public static array $flushedLocales = [];

    /**
     * {@inheritdoc}
     */
    public function initialize() : void {}

    /**
     * {@inheritdoc}
     */
    public function getPermissions() : array
    {
        return ['admin'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMenuItems() : array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function registerServiceProviders() : array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function boot() : void {}

    /**
     * Returns the priority of the admin package.
     *
     * The priority defines the order in which the admin packages are loaded and
     * executed. Lower numbers indicate a higher priority.
     *
     * @return int
     */
    public function getPriority() : int
    {
        return 100;
    }

    /**
     * Load routes from a specified file.
     *
     * This method allows the package to load its routes from a given PHP file.
     *
     * @param string $file The path to the routes file relative to the package's base path.
     * @return void
     *
     * @throws InvalidArgumentException If the file does not exist.
     */
    public function loadRoutesFromFile(string $file) : void
    {
        $fullPath = $this->getBasePath() . DIRECTORY_SEPARATOR . $file;

        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            throw new InvalidArgumentException("Routes file does not exist: {$fullPath}");
        }

        $router = router();

        require $fullPath;
    }

    /**
     * Load an array of routes.
     *
     * This method allows the package to define its routes programmatically.
     *
     * @param array $routes An array of routes to be loaded.
     * @return void
     */
    public function loadRoutes(array $routes) : void
    {
        foreach ($routes as $route) {
            router()->addRoute($route['method'], $route['uri'], $route['action']);
        }
    }

    /**
     * Load views from the package's view directory.
     *
     * This method registers the package's view namespace with the templating engine.
     *
     * @param string $viewDirectory The directory where the package's views are located relative to the base path.
     * @param string $namespace The namespace to assign to the views.
     * @return void
     *
     * @throws InvalidArgumentException If the view directory does not exist.
     */
    public function loadViews(string $viewDirectory, string $namespace) : void
    {
        $fullPath = $this->getBasePath() . DIRECTORY_SEPARATOR . $viewDirectory;

        if (!is_dir($fullPath)) {
            throw new InvalidArgumentException("View directory does not exist: {$fullPath}");
        }

        template()->addNamespace($namespace, $fullPath);
    }

    /**
     * Load translations from the package's lang directory.
     *
     * This method registers the package's translation files with the localization system.
     *
     * @param string $langDirectory The directory where the package's language files are located relative to the base path.
     * @return void
     *
     * @throws InvalidArgumentException If the language directory does not exist.
     */
    public function loadTranslations(string $langDirectory) : void
    {
        if (isset($this->loaded['translations'])) {
            return;
        }

        $fullPath = $this->getBasePath() . DIRECTORY_SEPARATOR . $langDirectory;

        if (!is_dir($fullPath)) {
            return;
        }

        $packageName = basename($this->getBasePath());
        $cacheKey = "admin.package.{$packageName}.translations";

        $translationFiles = cache()->callback($cacheKey, function () use ($fullPath) {
            $finder = finder();
            $finder->files()->in($fullPath)->name('*.php');

            $files = [];
            foreach ($finder as $file) {
                $files[] = [
                    'path' => $file->getPathname(),
                    'locale' => $file->getRelativePath(),
                    'domain' => basename($file->getFilename(), '.php')
                ];
            }

            return $files;
        }, $this->defaultCacheDuration);

        if (empty($translationFiles)) {
            return;
        }

        $translationService = translation();
        $currentLocale = app()->getLang();

        $filesByLocale = [];
        foreach ($translationFiles as $f) {
            $filesByLocale[$f['locale']][] = $f;
        }

        $localesToProcess = config('lang.cache') ? [$currentLocale] : array_keys($filesByLocale);

        foreach ($localesToProcess as $locale) {
            $filesForLocale = $filesByLocale[$locale] ?? [];
            if (empty($filesForLocale)) {
                continue;
            }

            if (config('lang.cache') && !isset(self::$flushedLocales[$locale])) {
                $translationService->flushLocaleCache($locale);
                self::$flushedLocales[$locale] = true;
            }

            foreach ($filesForLocale as $file) {
                if (config('lang.cache') && $file['locale'] !== $currentLocale) {
                    continue;
                }
                $translationService->registerResource($file['path'], $file['locale'], $file['domain']);
            }

            // // Build/refresh the catalogue to include newly registered resources.
        }

        $this->loaded['translations'] = true;
    }

    public function registerScss(string $assetsFile) : void
    {
        $fullPath = rtrim($this->getBasePath() . DIRECTORY_SEPARATOR . $assetsFile, DIRECTORY_SEPARATOR);

        if (!file_exists($fullPath)) {
            throw new InvalidArgumentException("Assets file does not exist: {$fullPath}");
        }

        template()->getTemplateAssets()->addScssFile($fullPath, 'admin');
    }

    /**
     * Get the base path of the package.
     *
     * This method determines the base directory of the package using reflection based on the class's file location.
     *
     * @return string The base path of the package.
     */
    public function getBasePath() : string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }
}
