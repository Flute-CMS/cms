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

    protected array $updateChannel = [];

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

    public function getUpdateChannel()
    {
        if (empty($this->updateChannel))
            return false;

        return "https://api.github.com/repos/{$this->updateChannel['org']}/{$this->updateChannel['rep']}/releases/latest";
    }

    public function setUpdateChannel(string $org, string $rep)
    {
        $this->updateChannel = [
            "org" => $org,
            "rep" => $rep
        ];

        return $this;
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
            logs('modules')->error($e);
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
            $dir = path("app/Modules/{$this->getModuleName()}/{$subPath}");
            $finder = finder();
            $finder->files()->in($dir)->name('*.php');

            foreach ($finder as $file) {
                $callback($file);
            }
        } catch (DirectoryNotFoundException $e) {
            logs('modules')->error($e);
        }
    }
}