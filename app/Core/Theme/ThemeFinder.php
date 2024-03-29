<?php

namespace Flute\Core\Theme;

use Flute\Core\Contracts\ThemeLoaderInterface;

class ThemeFinder
{
    /**
     * Получает ThemeLoader класс из каждого модуля в указанном пути модулей.
     *
     * @param string $themesPath Путь к директории с темами.
     * @return array Массив, где ключ - это имя темы, а значение - экземпляр класса ThemeLoader темы.
     */
    public function getAllThemeLoaders(string $themesPath): array
    {
        $allThemes = [];

        $finder = finder();

        $finder->directories()->in($themesPath);

        foreach ($finder as $dir) {
            $loaderFinder = finder();

            $loaderFinder->files()->name('ThemeLoader.php')->in($dir->getRealPath());

            foreach ($loaderFinder as $loaderFile) {
                $themeName = $dir->getBasename();
                $loaderClass = $this->getThemePath($themeName);

                // if (class_exists($loaderClass)) {
                    // logs()->info("Getting theme {$loaderClass}");

                    // $loaderInstance = app()->make($loaderClass);
                    $allThemes[$themeName] = $loaderClass;
                // }

                // На всякий случай.
                break;
            }
        }

        return $allThemes;
    }


    /**
     * Получает экземпляр ThemeLoader класса для указанной темы.
     * 
     * @param string $theme Какой шаблон будет использоваться для загрузки ThemeLoader
     * 
     * @return ThemeLoaderInterface|null Экземпляр класса ThemeLoader или null, если файл не существует.
     */
    public function getThemeLoader(string $theme): ?ThemeLoaderInterface
    {
        $loaderClass = $this->getThemePath($theme);

        if (class_exists($loaderClass)) {
            return new $loaderClass();
        }

        return null;
    }

    protected function getThemePath( string $name ) : string
    {
        return sprintf('\\Flute\\Themes\\%s\\ThemeLoader', $name);
    }
}