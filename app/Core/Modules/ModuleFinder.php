<?php

namespace Flute\Core\Modules;

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

        $finder = finder();

        $finder->directories()->in($modulesPath);

        foreach ($finder as $dir) {
            $jsonFinder = finder();

            $jsonFinder->files()->name('module.json')->in($dir->getRealPath());


            foreach ($jsonFinder as $jsonFile) {
                $allModules[$dir->getBasename()] = $jsonFile->getRealPath();

                // На всякий случай.
                break;
            }
        }

        return $allModules;
    }

    /**
     * Get module json file
     * 
     * @param string $jsonPath
     * 
     * @return string
     */
    public static function getModuleJson( string $jsonPath ) : string
    {
        return file_get_contents($jsonPath);
    }
}