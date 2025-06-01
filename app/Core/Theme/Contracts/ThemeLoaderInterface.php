<?php

namespace Flute\Core\Theme\Contracts;

use Jenssegers\Blade\Blade;

interface ThemeLoaderInterface
{
    /**
     * Register the template service.
     *
     * @param \Flute\Core\Template\Template $templateService
     * @return void
     */
    public function register(\Flute\Core\Template\Template $templateService);

    /**
     * Set the Blade instance.
     *
     * @param Blade $bladeOne
     * @return void
     */
    public function blade(Blade $bladeOne);

    /**
     * Active the template event.
     *
     * @return mixed
     */
    public function activate();

    /**
     * Install the template event.
     *
     * @return mixed
     */
    public function install();

    /**
     * Uninstall the template event.
     *
     * @return mixed
     */
    public function uninstall();

    /**
     * Disable the template event.
     *
     * @return mixed
     */
    public function disable();

    /**
     * Get the layout arguments for the template.
     *
     * @return array
     */
    public function getLayoutArguments(): array;

    /**
     * Add custom path to the some file
     * 
     * @param string $moduleInterfacePath path in the modules directory
     * @param string $replacedInterfacePath path in the current template
     * 
     * @return void
     */
    public function addCustomPath(string $moduleInterfacePath, string $replacedInterfacePath): void;

    public function getReplacement( ?string $interfacePath = null );
    public function getName(): string;

    public function setName(string $name): void;

    public function getKey(): string;

    public function setKey(string $key): void;

    public function getVersion(): string;

    public function setVersion(string $version): void;

    public function getAuthor(): string;

    public function setAuthor(string $author): void;

    public function getDescription(): string;

    public function setDescription(string $description): void;

    public function getRequirements(): array;

    public function setRequirements(array $requirements): void;

    public function getSettings(): array;

    public function setSettings(array $settings): void;
}