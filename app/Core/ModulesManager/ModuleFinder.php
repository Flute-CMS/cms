<?php

namespace Flute\Core\ModulesManager;

/**
 * Класс ModuleFinder отвечает за поиск всех модулей и их JSON файлов.
 */
class ModuleFinder
{
    /**
     * Получает module.json файл из каждого модуля в указанном пути модулей.
     *
     * @param string $modulesPath Путь к директории с модулями.
     * @return array Массив, где ключ - это имя модуля, а значение - путь к JSON файлу модуля.
     */
    public static function getAllJson(string $modulesPath): array
    {
        $allModules = [];

        if (!is_dir($modulesPath)) {
            return $allModules;
        }

        $items = @scandir($modulesPath);
        if ($items === false) {
            return $allModules;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.disabled') {
                continue;
            }

            $moduleDir = rtrim($modulesPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;
            if (!is_dir($moduleDir)) {
                continue;
            }

            // Fast path: module.json is expected in module root.
            $jsonPath = $moduleDir . DIRECTORY_SEPARATOR . 'module.json';
            if (is_file($jsonPath)) {
                $real = realpath($jsonPath);
                $allModules[$item] = $real !== false ? $real : $jsonPath;

                continue;
            }

            // Fallback: handle rare cases where archive/unpack adds an extra wrapper directory.
            // Keep it shallow to avoid expensive recursive scans on every subdirectory.
            $jsonFinder = finder();
            $jsonFinder
                ->files()
                ->name('module.json')
                ->in($moduleDir)
                ->depth('== 1');

            foreach ($jsonFinder as $jsonFile) {
                $allModules[$item] = $jsonFile->getRealPath();

                break;
            }
        }

        return $allModules;
    }

    /**
     * Get module json file
     */
    public static function getModuleJson(string $jsonPath): string
    {
        return file_get_contents($jsonPath);
    }
}
