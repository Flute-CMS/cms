<?php

namespace Flute\Core\ModulesManager\Actions;

use Exception;
use Flute\Core\Database\DatabaseConnection;
use Flute\Core\Database\Entities\Module;
use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\ModulesManager\Contracts\ModuleActionInterface;
use Flute\Core\ModulesManager\Exceptions\ModuleDependencyException;
use Flute\Core\ModulesManager\ModuleDependencies;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Support\AbstractModuleInstaller;
use Flute\Core\Theme\ThemeManager;
use RuntimeException;

class ModuleInstall implements ModuleActionInterface
{
    protected ModuleManager $moduleManager;

    protected ModuleDependencies $moduleDependencies;

    public function action(ModuleInformation &$module, ?ModuleManager $moduleManager = null): bool
    {
        $this->moduleManager = $moduleManager ?? app(ModuleManager::class);
        $this->moduleDependencies = $this->moduleManager->getModuleDependencies();

        $moduleGet = $this->moduleManager->getModule($module->key);

        $installerClassDir = sprintf('\Flute\\Modules\\%s\\Installer', $module->key);

        $moduleGet = $this->moduleManager->getModule($module->key);

        if (!$moduleGet) {
            throw new Exception("Module {$module->key} wasn't found in the system");
        }

        if ($moduleGet->status !== 'notinstalled') {
            throw new RuntimeException('Module already installed');
        }

        $this->checkModuleDependencies($moduleGet);

        $directory = sprintf('app/Modules/%s/database/migrations', $module->key);

        if (fs()->exists(BASE_PATH . $directory)) {
            try {
                app(DatabaseConnection::class)->runMigrations($directory);
            } catch (Exception $e) {
                app(DatabaseConnection::class)->rollbackMigrations($directory);

                throw $e;
            }
        }

        if (class_exists($installerClassDir)) {
            /** @var AbstractModuleInstaller */
            $installer = new $installerClassDir($module->key);

            if (!method_exists($installer, 'install')) {
                throw new RuntimeException("install method into {$installerClassDir} wasn't found");
            }

            $install = $installer->install($module);

            if (!$install) {
                return false;
            }

            if (!empty($item = $installer->getNavItem())) {
                $this->createNavItem($item);
            }
        }

        $this->initDb($module);
        $this->e($module);

        $this->moduleManager->runComposerInstall($module);

        app(DatabaseConnection::class)->forceRefreshSchemaDeferred([$module->key]);

        return true;
    }

    protected function createNavItem(NavbarItem $navbarItem)
    {
        transaction($navbarItem)->run();
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

    protected function initDb(ModuleInformation $moduleInformation): void
    {
        $module = Module::findOne(["key" => $moduleInformation->key]);

        $module->status = ModuleManager::DISABLED;
        $module->installedVersion = $moduleInformation->version;

        $module->save();

        logs('modules')->info("Module {$module->key} was installed in the Flute!");
    }

    protected function e(ModuleInformation $moduleInformation)
    {
        $event = new \Flute\Core\ModulesManager\Events\ModuleInstall($moduleInformation->key, $moduleInformation);

        events()->dispatch($event, \Flute\Core\ModulesManager\Events\ModuleInstall::NAME);
    }
}
