<?php

namespace Flute\Core\ModulesManager\Actions;

use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\Module;
use Flute\Core\ModulesManager\Contracts\ModuleActionInterface;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\ModulesManager\ModuleManager;
use RuntimeException;

class ModuleUninstall implements ModuleActionInterface
{
    protected $moduleManager;

    public function action(ModuleInformation &$module, ?ModuleManager $moduleManager = null): bool
    {
        $this->moduleManager = $moduleManager ?? app(ModuleManager::class);
        $moduleGet = $this->moduleManager->getModule($module->key);
        $installerClassDir = sprintf('Flute\\Modules\\%s\\Installer', $module->key);

        if (!$moduleGet) {
            throw new RuntimeException("Module wasn't found in the system");
        }

        $hasComposerJson = fs()->exists(path('app/Modules/'.$module->key.'/composer.json'));

        if ($moduleGet->status !== 'notinstalled') {
            $directory = sprintf('app/Modules/%s/database/migrations', $module->key);

            if (fs()->exists(BASE_PATH.$directory)) {
                app(DatabaseConnection::class)->rollbackMigrations($directory);
            }
        }

        if (class_exists($installerClassDir)) {
            $installer = new $installerClassDir($module->key);

            if (!method_exists($installer, 'uninstall')) {
                throw new RuntimeException("Uninstall method in the {$installerClassDir} wasn't found");
            }

            $uninstall = $installer->uninstall($module);

            if (!$uninstall) {
                return false;
            }
        }

        $this->uninstall($moduleGet);

        if ($hasComposerJson) {
            $this->moduleManager->runComposerInstall(null, true);
        }

        app(DatabaseConnection::class)->forceRefreshSchemaDeferred();

        return true;
    }

    protected function uninstall(ModuleInformation $moduleInformation): void
    {
        $module = Module::findOne(["key" => $moduleInformation->key]);

        $module->delete();

        fs()->remove(BASE_PATH.'app/Modules/'.$moduleInformation->key);

        logs('modules')->info("Module {$module->key} was deleted from the Flute!");
    }

    protected function e(ModuleInformation $moduleInformation)
    {
        $event = new \Flute\Core\ModulesManager\Events\ModuleDelete($moduleInformation->key, $moduleInformation);

        events()->dispatch($event, \Flute\Core\ModulesManager\Events\ModuleDelete::NAME);
    }
}
