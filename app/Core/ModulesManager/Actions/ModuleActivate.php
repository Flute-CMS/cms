<?php

namespace Flute\Core\ModulesManager\Actions;

use Flute\Core\Database\Entities\Module;
use Flute\Core\ModulesManager\Contracts\ModuleActionInterface;
use Flute\Core\ModulesManager\Exceptions\ModuleDependencyException;
use Flute\Core\ModulesManager\ModuleDependencies;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Theme\ThemeManager;

class ModuleActivate implements ModuleActionInterface
{
    protected ModuleDependencies $dependencies;
    protected ModuleManager $moduleManager;

    public function action(ModuleInformation &$module, ?ModuleManager $moduleManager = null): bool
    {
        $this->moduleManager = $moduleManager ?? app(ModuleManager::class);
        $this->dependencies = $this->moduleManager->getModuleDependencies();

        $moduleGet = $this->moduleManager->getModule($module->key);

        if (!$moduleGet) {
            throw new \RuntimeException("Module wasn't found in the system");
        }

        if ($moduleGet->status === 'notinstalled') {
            throw new \RuntimeException("Module is not installed in the system");
        }

        if ($moduleGet->status === 'active') {
            throw new \RuntimeException("Module already activated");
        }

        $this->checkModuleDependencies($moduleGet);

        $this->activate($moduleGet);

        return true;
    }

    protected function checkModuleDependencies(ModuleInformation $module)
    {
        try {
            /** @var ThemeManager $themeManager */
            $themeManager = app(ThemeManager::class);

            $this->dependencies->checkDependencies($module->dependencies, $this->moduleManager->getActive(), $themeManager->getThemeInfo());
        } catch (ModuleDependencyException $e) {
            logs('modules')->emergency("Flute module \"" . $module->key . "\" dependency check failed - " . $e->getMessage());

            throw new ModuleDependencyException($e->getMessage());
        }
    }

    protected function activate(ModuleInformation $moduleInformation): void
    {
        $module = Module::findOne(["key" => $moduleInformation->key]);

        $module->status = ModuleManager::ACTIVE;

        $module->save();

        logs('modules')->info("Module {$module->key} was activated in the Flute!");
    }

    protected function e(ModuleInformation $moduleInformation)
    {
        $event = new \Flute\Core\ModulesManager\Events\ModuleActivate($moduleInformation->key, $moduleInformation);

        events()->dispatch($event, \Flute\Core\ModulesManager\Events\ModuleActivate::NAME);
    }
}
