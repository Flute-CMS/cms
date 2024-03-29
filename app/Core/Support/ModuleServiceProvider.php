<?php

namespace Flute\Core\Support;

use Flute\Core\Contracts\ModuleServiceProviderInterface;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Services\ConfigurationService;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

/**
 * Абстрактный класс поставщика услуг модуля.
 * Предоставляет базовые реализации и утилиты для модулей.
 */
abstract class ModuleServiceProvider implements ModuleServiceProviderInterface
{
    /**
     * Расширения для регистрации.
     *
     * @var array
     */
    public array $extensions = [];

    /**
     * Имя модуля.
     *
     * @var string|null
     */
    protected ?string $moduleName = '';

    /**
     * Слушатели событий.
     *
     * @var array
     */
    protected $listen = [];

    /**
     * {@inheritdoc}
     */
    public function getEventListeners(): array
    {
        return $this->listen;
    }

    /**
     * {@inheritdoc}
     */
    public function setModuleName(string $moduleName): void
    {
        $this->moduleName = $moduleName;
    }

    /**
     * {@inheritdoc}
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function loadRoutesFrom(string $path)
    {
        // mb temporarly
        $router = router();

        require path($path);
    }

    /**
     * Should module manager call all registered extensions
     * 
     * @return bool
     */
    public function isExtensionsCallable(): bool
    {
        return true;
    }

    public function loadEntities(): void
    {
        $moduleName = $this->getModuleName();

        try {
            $entDir = path("app/Modules/$moduleName/database/Entities");
            $finder = finder();
            $finder->files()->in($entDir)->name('*.php');

            if ($finder->count() > 0) {
                app(DatabaseConnection::class)->addDir($entDir);
            }
        } catch (DirectoryNotFoundException $e) {
            logs('modules')->error($e->getMessage());
        }
    }

    public function loadTranslations(): void
    {
        $this->loadFromDirectory('i18n', function ($file) {
            $locale = $file->getRelativePath();
            $domain = basename($file->getFilename(), '.php');
            translation()->addResource('file', $file->getPathname(), $locale, $domain);
        });
    }

    public function loadConfigs()
    {
        $this->loadFromDirectory('config', function ($file) {
            $name = basename($file->getFilename(), '.php');
            app(ConfigurationService::class)->loadCustomConfig($file->getPathname(), $name);
        });
    }

    private function loadFromDirectory(string $subPath, callable $callback)
    {
        try {
            $dir = path("app/Modules/{$this->moduleName}/{$subPath}");
            $finder = finder();
            $finder->files()->in($dir)->name('*.php');

            foreach ($finder as $file) {
                $callback($file);
            }
        } catch (DirectoryNotFoundException $e) {
            logs('modules')->error($e->getMessage());
        }
    }
}