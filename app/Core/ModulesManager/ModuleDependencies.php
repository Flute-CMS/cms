<?php

namespace Flute\Core\ModulesManager;

use Composer\InstalledVersions;
use Flute\Core\App;
use Flute\Core\Database\Entities\Theme;
use Flute\Core\ModulesManager\Exceptions\ModuleDependencyException;
use Illuminate\Support\Collection;

class ModuleDependencies
{
    protected array $dependencies;

    protected Collection $activeModules;

    protected Theme $activeTheme;

    /**
     * Checks the dependencies of the modules.
     *
     * @param array $dependencies     The module dependencies.
     * @param Collection $activeModules    The collection of active modules.
     * @param Theme $activeTheme  The actived theme.
     *
     * @throws ModuleDependencyException If a dependency requirement is not met.
     */
    public function checkDependencies(
        array $dependencies,
        Collection $activeModules,
        Theme $activeTheme
    ) {
        $this->dependencies = $dependencies;
        $this->activeModules = $activeModules;
        $this->activeTheme = $activeTheme;

        $this->checkPhpDependencies();
        $this->checkExtensionsDependencies();
        $this->checkComposerPackageDependencies();
        $this->checkModulesDependencies();
        $this->checkFluteDependencies();
        $this->checkTemplateDependencies();
    }

    /**
     * Checks the PHP version dependency.
     *
     * @throws ModuleDependencyException If the required PHP version is not met.
     */
    protected function checkPhpDependencies()
    {
        if (isset($this->dependencies['php'])) {
            if (!version_compare(PHP_VERSION, $this->dependencies['php'], '>=')) {
                throw new ModuleDependencyException("PHP version " . $this->dependencies['php'] . " or higher is required.");
            }
        }
    }

    /**
     * Checks the module dependencies.
     *
     * @throws ModuleDependencyException If a required module version is not met.
     */
    protected function checkModulesDependencies()
    {
        if (isset($this->dependencies['modules'])) {
            foreach ($this->dependencies['modules'] as $module => $version) {
                if (!$this->activeModules->offsetExists($module) || version_compare($this->activeModules->get($module)->version, $version, '<')) {
                    throw new ModuleDependencyException("Module {$module} version {$version} or higher is required.");
                }
            }
        }
    }

    /**
     * Checks the Flute dependency.
     *
     * @throws ModuleDependencyException If the required Flute version is not met.
     */
    protected function checkFluteDependencies()
    {
        if (isset($this->dependencies['flute'])) {
            if (!version_compare(App::VERSION, $this->dependencies['flute'], '>=')) {
                throw new ModuleDependencyException("Flute version " . $this->dependencies['flute'] . " or higher is required.");
            }
        }
    }

    /**
     * Checks the template dependencies.
     *
     * @throws ModuleDependencyException If a required template version is not met.
     */
    protected function checkTemplateDependencies()
    {
        if (isset($this->dependencies['theme'])) {
            $theme = $this->dependencies['theme'];
            $key = key($theme);

            if (!$this->activeTheme->key === $key || version_compare($this->activeTheme->version, $theme->{$key}, '<')) {
                throw new ModuleDependencyException("Theme {$key} version {$theme->{$key}} or higher is required.");
            }
        }
    }

    /**
     * Checks the PHP extension dependencies.
     *
     * @throws ModuleDependencyException If a required PHP extension is not loaded.
     */
    protected function checkExtensionsDependencies()
    {
        if (isset($this->dependencies['extensions'])) {
            foreach ($this->dependencies['extensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    throw new ModuleDependencyException("PHP extension {$extension} is required.");
                }
            }
        }
    }

    /**
     * Checks the Composer package dependencies.
     *
     * @throws ModuleDependencyException If a required Composer package version is not met.
     */
    protected function checkComposerPackageDependencies()
    {
        if (isset($this->dependencies['composer'])) {
            foreach ($this->dependencies['composer'] as $package => $version) {
                if (!InstalledVersions::isInstalled($package) || version_compare(InstalledVersions::getVersion($package), $version, '<')) {
                    throw new ModuleDependencyException("Composer package {$package} version {$version} or higher is required.");
                }
            }
        }
    }
}
