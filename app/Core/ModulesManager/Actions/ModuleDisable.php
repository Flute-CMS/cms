<?php

namespace Flute\Core\ModulesManager\Actions;

use Flute\Core\Database\Entities\Module;
use Flute\Core\ModulesManager\Contracts\ModuleActionInterface;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\ModulesManager\ModuleManager;
use RuntimeException;

class ModuleDisable implements ModuleActionInterface
{
    public function action(ModuleInformation &$module, ?ModuleManager $moduleManager = null): bool
    {
        $mm = $moduleManager ?? app(ModuleManager::class);

        $moduleGet = $mm->getModule($module->key);

        if (!$moduleGet) {
            throw new RuntimeException("Module wasn't found in the system");
        }

        if ($moduleGet->status === 'notinstalled') {
            throw new RuntimeException("Module is not installed in the system");
        }

        if ($moduleGet->status === 'disabled') {
            throw new RuntimeException("Module already disabled");
        }

        $this->disable($moduleGet);

        return true;
    }

    protected function disable(ModuleInformation $moduleInformation): void
    {
        $module = Module::findOne(["key" => $moduleInformation->key]);

        $module->status = ModuleManager::DISABLED;

        $module->save();

        logs('modules')->info("Module {$module->key} was disabled in the Flute!");
    }

    protected function e(ModuleInformation $moduleInformation)
    {
        $event = new \Flute\Core\ModulesManager\Events\ModuleDisable($moduleInformation->key, $moduleInformation);

        events()->dispatch($event, \Flute\Core\ModulesManager\Events\ModuleDisable::NAME);
    }
}
