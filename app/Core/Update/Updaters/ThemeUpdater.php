<?php

namespace Flute\Core\Update\Updaters;

use Exception;
use Flute\Core\Database\Entities\Theme;
use ZipArchive;

class ThemeUpdater extends AbstractUpdater
{
    /**
     * Информация о теме
     */
    protected Theme $theme;

    /**
     * Данные темы
     */
    protected array $themeData;

    /**
     * ThemeUpdater constructor.
     */
    public function __construct(Theme $theme, array $themeData)
    {
        $this->theme = $theme;
        $this->themeData = $themeData;
    }

    public function getCurrentVersion(): string
    {
        return $this->theme->version ?? '1.0.0';
    }

    public function getIdentifier(): ?string
    {
        return $this->theme->key;
    }

    public function getType(): string
    {
        return 'theme';
    }

    public function getName(): string
    {
        return $this->theme->name;
    }

    public function getDescription(): string
    {
        return $this->theme->description;
    }

    public function update(array $data): bool
    {
        // Проверяем, есть ли файл с обновлением
        if (empty($data['package_file']) || !file_exists($data['package_file'])) {
            logs()->error('Theme update package file not found: ' . ($data['package_file'] ?? 'null'));

            return false;
        }

        $maintenanceEnabled = $this->enableUpdateMaintenance();

        $packageFile = $data['package_file'];
        $extractDir = storage_path('app/temp/updates/theme-' . $this->theme->key . '-' . time());

        // Создаем временную директорию
        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0o755, true);
        }

        // Распаковываем архив
        $zip = new ZipArchive();
        if ($zip->open($packageFile) !== true) {
            logs()->error('Failed to open theme update package: ' . $packageFile);

            return false;
        }

        $zip->extractTo($extractDir);
        $zip->close();

        // Определяем корневую директорию в архиве, может содержать один корневой каталог
        $rootDir = $extractDir;
        $items = scandir($extractDir);
        if (count($items) === 3) { // '.', '..' и одна директория
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($extractDir . '/' . $item)) {
                    $rootDir = $extractDir . '/' . $item;

                    break;
                }
            }
        }

        // Получаем путь к директории темы
        $themeDir = $this->getThemeDirectory();

        // Копируем файлы
        try {
            try {
                // Создаем бэкап перед обновлением
                $this->createBackup();

                // Копируем файлы
                $this->copyDirectory($rootDir, $themeDir);

                // Очищаем кэш
                $this->clearCache();

                // Удаляем временные файлы
                $this->removeDirectory($extractDir);

                return true;
            } catch (Exception $e) {
                logs()->error('Error during theme update: ' . $e->getMessage());
                // Удаляем временные файлы
                $this->removeDirectory($extractDir);

                return false;
            }
        } finally {
            $this->disableUpdateMaintenance($maintenanceEnabled);
        }
    }

    /**
     * Получить путь к директории темы
     */
    protected function getThemeDirectory(): string
    {
        $basePath = dirname(dirname(dirname(dirname(__DIR__))));

        return $basePath . '/app/Themes/' . $this->theme->key;
    }

    /**
     * Создать бэкап перед обновлением
     */
    protected function createBackup(): bool
    {
        if (!config('app.create_backup')) {
            return false;
        }

        $backupDir = storage_path('backup/themes/' . $this->theme->key . '-' . date('Y-m-d-His'));

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0o755, true);
        }

        $themeDir = $this->getThemeDirectory();
        $this->copyDirectory($themeDir, $backupDir);

        logs()->info('Theme backup created: ' . $backupDir);

        return true;
    }

    /**
     * Копировать директорию рекурсивно
     */
    protected function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination)) {
            $dirPerms = fileperms($source) & 0o777;
            mkdir($destination, $dirPerms, true);
            chmod($destination, $dirPerms);
            $this->safeChown($destination, fileowner($source));
            $this->safeChgrp($destination, filegroup($source));
        }

        $directory = opendir($source);
        if ($directory === false) {
            return false;
        }

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destinationPath = $destination . '/' . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath);
            } else {
                copy($sourcePath, $destinationPath);
                $filePerms = fileperms($sourcePath) & 0o777;
                chmod($destinationPath, $filePerms);
                $this->safeChown($destinationPath, fileowner($sourcePath));
                $this->safeChgrp($destinationPath, filegroup($sourcePath));
            }
        }

        closedir($directory);

        return true;
    }

    /**
     * Удалить директорию рекурсивно
     */
    protected function removeDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($directory);
    }

    /**
     * Очистить кэш
     */
    protected function clearCache(): void
    {
        // Очищаем кэш CSS и JS
        $assetsCachePath = public_path('assets/cache');
        if (is_dir($assetsCachePath)) {
            $this->removeDirectory($assetsCachePath);
            mkdir($assetsCachePath, 0o755, true);
        }

        // Очищаем кэш приложения
        cache()->forget('themes_list');
        cache()->forget('active_theme');

        if (function_exists('cache_warmup_mark')) {
            cache_warmup_mark();
        }
    }

    /**
     * Получить путь к публичной директории
     */
    protected function public_path(string $path = ''): string
    {
        $basePath = dirname(dirname(dirname(dirname(__DIR__))));

        return $basePath . '/public/' . $path;
    }
}
