<?php

namespace Flute\Core\ModulesManager\Actions;

use Flute\Core\Database\Entities\Module;
use Flute\Core\ModulesManager\Contracts\ModuleActionInterface;
use Flute\Core\ModulesManager\Exceptions\ModuleDependencyException;
use Flute\Core\ModulesManager\ModuleDependencies;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Theme\ThemeManager;

class ModuleUpdate implements ModuleActionInterface
{
    protected ModuleManager $moduleManager;
    protected ModuleDependencies $moduleDependencies;

    public function action(ModuleInformation &$module, ?ModuleManager $moduleManager = null): bool
    {
        $this->moduleManager = $moduleManager ?? app(ModuleManager::class);
        $this->moduleDependencies = $this->moduleManager->getModuleDependencies();

        $moduleGet = $this->moduleManager->getModule($module->key);

        $updaterClassDir = sprintf('\Flute\\Modules\\%s\\Updater', $module->key);

        if (!$moduleGet) {
            throw new \Exception("Module {$module->key} wasn't found in the system");
        }

        if ($moduleGet->status === 'notinstalled') {
            throw new \RuntimeException('Module is not installed');
        }

        $this->checkModuleDependencies($moduleGet);

        if (class_exists($updaterClassDir)) {
            $updater = new $updaterClassDir($module->key);

            $currentVersion = $moduleGet->installedVersion;
            $newVersion = $module->version;

            if (version_compare($newVersion, $currentVersion, '>')) {
                $this->runUpdates($updater, $currentVersion, $newVersion);
            }
        } else {
            $newVersion = $module->version;
        }

        $this->updateDb($module, $newVersion);
        $this->dispatchUpdateEvent($module);

        $this->moduleManager->runComposerInstall($module);

        return true;
    }

    protected function checkModuleDependencies(ModuleInformation $module)
    {
        try {
            /** @var ThemeManager $themeManager */
            $themeManager = app(ThemeManager::class);

            $this->moduleDependencies->checkDependencies($module->dependencies, $this->moduleManager->getActive(), $themeManager->getThemeInfo());
        } catch (ModuleDependencyException $e) {
            logs('modules')->emergency("Flute module \"" . $module->key . "\" dependency check failed - " . $e->getMessage());

            throw new ModuleDependencyException($e->getMessage());
        }
    }

    protected function runUpdates($updater, $currentVersion, $newVersion)
    {
        $currentVersionParts = explode('.', $currentVersion);
        $newVersionParts = explode('.', $newVersion);

        while (version_compare($currentVersion, $newVersion, '<')) {
            $currentVersion = $this->incrementVersion($currentVersionParts);
            $updateMethod = 'update_' . implode('_', $currentVersionParts);

            if (method_exists($updater, $updateMethod)) {
                $updater->$updateMethod();
            }
        }
    }

    protected function incrementVersion(&$versionParts)
    {
        $versionParts[count($versionParts) - 1]++;
        for ($i = count($versionParts) - 1; $i > 0; $i--) {
            if ($versionParts[$i] >= 10) {
                $versionParts[$i] = 0;
                $versionParts[$i - 1]++;
            }
        }

        return implode('.', $versionParts);
    }

    protected function updateDb(ModuleInformation $moduleInformation, $newVersion): void
    {
        $module = Module::findOne(["key" => $moduleInformation->key]);

        $module->installedVersion = $newVersion;

        $module->save();

        logs('modules')->info("Module {$module->key} was updated to version $newVersion in the Flute!");
    }

    protected function dispatchUpdateEvent(ModuleInformation $moduleInformation)
    {
        $event = new \Flute\Core\ModulesManager\Events\ModuleUpdate($moduleInformation->key, $moduleInformation);

        events()->dispatch($event, \Flute\Core\ModulesManager\Events\ModuleUpdate::NAME);
    }
}
