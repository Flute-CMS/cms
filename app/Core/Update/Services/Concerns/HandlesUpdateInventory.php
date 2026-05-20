<?php

namespace Flute\Core\Update\Services\Concerns;

use Flute\Core\Update\Updaters\ModuleUpdater;
use Flute\Core\Update\Updaters\ThemeUpdater;

trait HandlesUpdateInventory
{
    private function getInstalledModules(): array
    {
        $modules = [];

        foreach ($this->moduleManager->getActive() as $module) {
            $updater = new ModuleUpdater($module);
            $modules[] = [
                'key' => $module->key,
                'version' => $updater->getCurrentVersion(),
            ];
        }

        return $modules;
    }

    private function getInstalledThemes(): array
    {
        $themes = [];

        foreach ($this->themeManager->getInstalledThemes() as $theme) {
            $themeData = $this->themeManager->getThemeData($theme->key);
            $updater = new ThemeUpdater($theme, $themeData);

            $themes[] = [
                'key' => $theme->key,
                'version' => $updater->getCurrentVersion(),
            ];
        }

        return $themes;
    }

    private function getPHPVersion(): string
    {
        return substr(PHP_VERSION, 0, 3);
    }
}
