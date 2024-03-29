<?php

namespace Flute\Core\Support;

use Flute\Core\Contracts\ThemeLoaderInterface;
use Flute\Core\Database\Entities\ThemeSettings;
use Flute\Core\Template\EditedBladeOne as BladeOne;
use Flute\Core\Template\Template;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

/**
 * Class AbstractThemeLoader
 *
 * Abstract class to implement ThemeLoaderInterface for loading themes.
 * 
 * @package Flute\Core
 */
abstract class AbstractThemeLoader implements ThemeLoaderInterface
{
    protected Template $template;
    protected string $name;
    protected string $key;
    protected string $version;
    protected string $author;
    protected string $description = "";
    protected array $requirements = [];
    protected array $replacements = [];
    protected array $settings;
    protected array $componentsLayouts = [
        "alert" => "components/alert",
        "toast" => "components/toast",
        "error" => "components/error",
        "table" => "components/table",
        "graph" => "components/graph",
        "flash" => "components/flash",
        "breadcrumb" => "components/breadcrumb",
    ];

    /**
     * Constructor method.
     *
     * Initialize properties of the class with the given arguments.
     */
    public function __construct(string $name, string $key, string $version, string $author, array $settings = [], string $description = "", array $requirements = [])
    {
        $this->name = $name;
        $this->key = $key;
        $this->version = $version;
        $this->author = $author;
        $this->description = $description;
        $this->requirements = $requirements;
        $this->settings = $settings;
    }

    /**
     * Method addCustomPath
     *
     * Add a custom path for module interface.
     */
    public function addCustomPath(string $moduleInterfacePath, string $replacedInterfacePath): void
    {
        $this->replacements[$moduleInterfacePath] = $replacedInterfacePath;
    }

    /**
     * Method register
     *
     * Register the template service.
     */
    public function register(\Flute\Core\Template\Template $templateService)
    {
    }

    public function createComponent(string $componentName)
    {
    }

    public function blade(BladeOne $bladeOne)
    {
    }

    public function install()
    {
    }

    public function disable()
    {
    }

    public function activate()
    {
    }

    public function uninstall()
    {
    }

    /**
     * Method info
     *
     * Return the information of the theme.
     *
     * @return array
     */
    public function info(): array
    {
        return [
            "key" => $this->key,
            "name" => $this->name,
            "author" => $this->author,
            "version" => $this->version,
            "description" => $this->description,
            "requirements" => $this->requirements,
            "settings" => $this->settings
        ];
    }

    public function addComponentLayout(string $componentName, string $component): void
    {
        $this->componentsLayouts[$componentName] = $component;
    }

    public function getComponentsLayout(): array
    {
        return $this->componentsLayouts;
    }

    /**
     * Method getComponentLayout
     *
     * Return the component layout for the given component name.
     *
     * @return string
     */
    public function getComponentLayout(string $componentName): string
    {
        return sprintf("Themes/%s/%s.blade.php", $this->key, $this->componentsLayouts[$componentName]);
    }

    public function getReplacement(?string $interfacePath = null)
    {
        return isset ($this->replacements[$interfacePath]) ? $this->replacements[$interfacePath] : $interfacePath;
    }

    protected function changeComponentLayout(string $componentName, string $path): self
    {
        $this->componentsLayouts[$componentName] = $path;
        return $this;
    }

    public function getLayoutArguments(): array
    {
        return [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function setRequirements(array $requirements): void
    {
        $this->requirements = $requirements;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function loadTranslations()
    {
        $this->loadFromDirectory('i18n', function ($file) {
            $locale = $file->getRelativePath();
            $domain = basename($file->getFilename(), '.php');
            translation()->addResource('file', $file->getPathname(), $locale, $domain);
        });
    }

    private function loadFromDirectory(string $subPath, callable $callback)
    {
        try {
            $dir = path("app/Themes/{$this->key}/{$subPath}");
            $finder = finder();
            $finder->files()->in($dir)->name('*.php');

            foreach ($finder as $file) {
                $callback($file);
            }
        } catch (DirectoryNotFoundException $e) {
            logs('templates')->error($e->getMessage());
        }
    }
}