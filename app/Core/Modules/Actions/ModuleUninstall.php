<?php

namespace Flute\Core\Modules\Actions;

use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\Module;
use Flute\Core\Modules\Contracts\ModuleActionInterface;
use Flute\Core\Modules\ModuleInformation;
use Flute\Core\Modules\ModuleManager;

class ModuleUninstall implements ModuleActionInterface
{
    protected $moduleManager;

    public function action(ModuleInformation &$module, ?ModuleManager $moduleManager = null): bool
    {
        $this->moduleManager = $moduleManager ?? app(ModuleManager::class);
        $moduleGet = $this->moduleManager->getModule($module->key);
        $installerClassDir = sprintf('Flute\\Modules\\%s\\Installer', $module->key);

        if (!$moduleGet)
            throw new \RuntimeException("Module wasn't found in the system");

        if ($moduleGet->status !== 'notinstalled') {
            $directory = sprintf('app/Modules/%s/database/migrations', $module->key);

            if (fs()->exists(BASE_PATH . $directory))
                app(DatabaseConnection::class)->rollbackMigrations($directory);
        }

        if (class_exists($installerClassDir)) {
            $installer = new $installerClassDir($module->key);

            if (!method_exists($installer, 'uninstall'))
                throw new \RuntimeException("Uninstall method in the {$installerClassDir} wasn't found");

            $uninstall = $installer->uninstall($module);

            if (!$uninstall)
                return false;
        }

        $this->uninstall($moduleGet);

        return true;
    }

    protected function uninstall(ModuleInformation $moduleInformation): void
    {
        $module = rep(Module::class)->findOne([
            "key" => $moduleInformation->key,
        ]);

        transaction($module, 'delete')->run();

        fs()->remove(BASE_PATH . 'app/Modules/' . $moduleInformation->key);

        logs('modules')->info("Module {$module->key} was deleted from the Flute!");
    }

    protected function e(ModuleInformation $moduleInformation)
    {
        $event = new \Flute\Core\Modules\Events\ModuleDelete($moduleInformation->key, $moduleInformation);

        events()->dispatch($event, \Flute\Core\Modules\Events\ModuleDelete::NAME);
    }
}