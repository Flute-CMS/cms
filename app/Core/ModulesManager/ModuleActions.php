<?php

namespace Flute\Core\ModulesManager;

use Flute\Core\ModulesManager\Actions\ModuleActivate;
use Flute\Core\ModulesManager\Actions\ModuleDisable;
use Flute\Core\ModulesManager\Actions\ModuleInstall;
use Flute\Core\ModulesManager\Actions\ModuleUninstall;
use Flute\Core\ModulesManager\Actions\ModuleUpdate;

class ModuleActions
{
    public function installModule(ModuleInformation $module, ?ModuleManager $moduleManager = null)
    {
        return $this->action(ModuleInstall::class, $module, $moduleManager);
    }

    public function uninstallModule(ModuleInformation $module, ?ModuleManager $moduleManager = null)
    {
        return $this->action(ModuleUninstall::class, $module, $moduleManager);
    }

    public function disableModule(ModuleInformation $module, ?ModuleManager $moduleManager = null)
    {
        return $this->action(ModuleDisable::class, $module, $moduleManager);
    }

    public function activateModule(ModuleInformation $module, ?ModuleManager $moduleManager = null)
    {
        return $this->action(ModuleActivate::class, $module, $moduleManager);
    }

    public function updateModule(ModuleInformation $module, ?ModuleManager $moduleManager = null)
    {
        return $this->action(ModuleUpdate::class, $module, $moduleManager);
    }

    protected function action(string $className, ModuleInformation &$module, ?ModuleManager $moduleManager = null)
    {
        return (new $className())->action($module, $moduleManager);
    }
}
