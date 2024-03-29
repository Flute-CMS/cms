<?php

namespace Flute\Core\Modules\Actions;

use Flute\Core\Database\Entities\Module;
use Flute\Core\Modules\Contracts\ModuleActionInterface;
use Flute\Core\Modules\ModuleInformation;
use Flute\Core\Modules\ModuleManager;

class ModuleDisable implements ModuleActionInterface
{
    public function action(ModuleInformation &$module, ?ModuleManager $moduleManager = null): bool
    {
        $mm = $moduleManager ?? app(ModuleManager::class);

        $moduleGet = $mm->getModule($module->key);

        if (!$moduleGet)
            throw new \RuntimeException("Module wasn't found in the system");

        if ($moduleGet->status === 'notinstalled')
            throw new \RuntimeException("Module is not installed in the system");

        if ($moduleGet->status === 'disabled')
            throw new \RuntimeException("Module already disabled");

        $this->disable($moduleGet);

        return true;
    }

    protected function disable(ModuleInformation $moduleInformation): void
    {
        $module = rep(Module::class)->findOne([
            "key" => $moduleInformation->key,
        ]);

        $module->status = ModuleManager::DISABLED;

        transaction($module)->run();

        logs('modules')->info("Module {$module->key} was disabled in the Flute!");
    }

    protected function e(ModuleInformation $moduleInformation)
    {
        $event = new \Flute\Core\Modules\Events\ModuleDisable($moduleInformation->key, $moduleInformation);

        events()->dispatch($event, \Flute\Core\Modules\Events\ModuleDisable::NAME);
    }
}