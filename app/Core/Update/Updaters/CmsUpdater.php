<?php

namespace Flute\Core\Update\Updaters;

use Flute\Core\App;
use Flute\Core\Composer\ComposerManager;
use ZipArchive;

class CmsUpdater extends AbstractUpdater
{
    /**
     * Папки, которые нужно обновлять
     *
     * @var array
     */
    protected array $allowedFolders = [
        'app',
        'bootstrap',
        'i18n',
        'public',
        'storage',
    ];

    /**
     * Root-level files to copy from the update package
     *
     * @var array
     */
    protected array $allowedFiles = [
        'composer.json',
        'composer.lock',
    ];

    /**
     * Файлы, которые нужно исключить из обновления
     *
     * @var array
     */
    protected array $excludedFiles = [
        'favicon.ico',
        'social-image.jpg',
        'social-image.png',
        'social-image.jpeg',
        'social-image.webp',
    ];

    public function getCurrentVersion(): string
    {
        return App::VERSION;
    }

    public function getIdentifier(): ?string
    {
        return null;
    }

    public function getType(): string
    {
        return 'cms';
    }

    public function getName(): string
    {
        return 'Flute CMS';
    }

    public function getDescription(): string
    {
        return 'Основная система';
    }

    public function update(array $data): bool
    {
        if (empty($data['package_file']) || !file_exists($data['package_file'])) {
            logs()->error('Update package file not found: ' . ($data['package_file'] ?? 'null'));

            return false;
        }

        $packageFile = $data['package_file'];
        $extractDir = storage_path('app/temp/updates/cms-extract-' . time());

        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0o755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($packageFile) !== true) {
            logs()->error('Failed to open update package: ' . $packageFile);

            return false;
        }

        $zip->extractTo($extractDir);
        $zip->close();

        try {
            $this->createBackup();

            $dirs = array_filter(glob($extractDir . '/*'), 'is_dir');
            $rootDir = count($dirs) === 1 ? reset($dirs) : $extractDir;

            foreach ($this->allowedFolders as $folder) {
                $sourcePath = $rootDir . '/' . $folder;
                $targetPath = $this->getBasePath() . '/' . $folder;
                if (is_dir($sourcePath)) {
                    $this->copyDirectory($sourcePath, $targetPath);
                }
            }

            // Copy allowed root files (like composer.json / composer.lock)
            foreach ($this->allowedFiles as $file) {
                $sourceFile = $rootDir . '/' . $file;
                $targetFile = $this->getBasePath() . '/' . $file;
                if (is_file($sourceFile)) {
                    $this->copyFile($sourceFile, $targetFile);
                }
            }

            // If composer files changed or exist in the package, ensure dependencies are installed
            if (is_file($this->getBasePath() . '/composer.json')) {
                try {
                    (new ComposerManager())->install();
                } catch (\Throwable $e) {
                    logs()->error('Composer install failed after CMS update: ' . $e->getMessage());
                }
            }

            $this->clearCache();

            $this->removeDirectory($extractDir);

            return true;
        } catch (\Exception $e) {
            logs()->error('Error during CMS update: ' . $e->getMessage());
            $this->removeDirectory($extractDir);

            return false;
        }
    }

    /**
     * Получить корневой путь к проекту
     *
     * @return string
     */
    protected function getBasePath(): string
    {
        return BASE_PATH;
    }

    /**
     * Создать бэкап перед обновлением
     *
     * @return bool
     */
    protected function createBackup(): bool
    {
        if (!config('app.create_backup')) {
            return false;
        }

        $backupDir = storage_path('backup/cms-' . date('Y-m-d-His'));

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0o755, true);
        }

        $dirsToCopy = [
            'app/Core' => $backupDir . '/app/Core',
            'app/Helpers' => $backupDir . '/app/Helpers',
            'app/Themes' => $backupDir . '/app/Themes',
        ];

        foreach ($dirsToCopy as $source => $target) {
            $sourcePath = $this->getBasePath() . '/' . $source;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $target);
            }
        }

        logs()->info('CMS backup created: ' . $backupDir);

        return true;
    }

    /**
     * Копировать директорию рекурсивно
     *
     * @param string $source
     * @param string $destination
     * @return bool
     */
    protected function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        // If destination doesn't exist, create it with source's perms/owner/group.
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
            if ($file === '.' || $file === '..' || $this->shouldExcludeFile($file)) {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destinationPath = $destination . '/' . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath);
            } else {
                $preservePerms = null;
                $preserveOwner = null;
                $preserveGroup = null;

                if (is_file($destinationPath)) {
                    $preservePerms = fileperms($destinationPath) & 0o755;
                    $preserveOwner = fileowner($destinationPath);
                    $preserveGroup = filegroup($destinationPath);
                }

                copy($sourcePath, $destinationPath);

                if ($preservePerms !== null) {
                    chmod($destinationPath, $preservePerms);
                    $this->safeChown($destinationPath, $preserveOwner);
                    $this->safeChgrp($destinationPath, $preserveGroup);
                } else {
                    $filePerms = fileperms($sourcePath) & 0o755;
                    chmod($destinationPath, $filePerms);
                    $this->safeChown($destinationPath, fileowner($sourcePath));
                    $this->safeChgrp($destinationPath, filegroup($sourcePath));
                }
            }
        }

        closedir($directory);

        return true;
    }

    /**
     * Copy a single file with permissions/ownership preserved where possible
     */
    protected function copyFile(string $sourceFile, string $targetFile): void
    {
        $targetDir = dirname($targetFile);
        if (!is_dir($targetDir)) {
            $dirPerms = fileperms(dirname($sourceFile)) & 0o777;
            mkdir($targetDir, $dirPerms, true);
            chmod($targetDir, $dirPerms);
            $this->safeChown($targetDir, fileowner(dirname($sourceFile)));
            $this->safeChgrp($targetDir, filegroup(dirname($sourceFile)));
        }

        copy($sourceFile, $targetFile);
        // Apply a consistent permission mask for copied files
        $filePerms = fileperms($sourceFile) & 0o755;
        chmod($targetFile, $filePerms);
        $this->safeChown($targetFile, fileowner($sourceFile));
        $this->safeChgrp($targetFile, filegroup($sourceFile));
    }

    /**
     * Проверить, нужно ли исключить файл из обновления
     *
     * @param string $filename
     * @return bool
     */
    protected function shouldExcludeFile(string $filename): bool
    {
        return in_array($filename, $this->excludedFiles);
    }

    /**
     * Удалить директорию рекурсивно
     *
     * @param string $directory
     * @return bool
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
     *
     * @return void
     */
    protected function clearCache(): void
    {
        $viewsCachePath = storage_path('app/views');
        if (is_dir($viewsCachePath)) {
            $this->removeDirectory($viewsCachePath);
            mkdir($viewsCachePath, 0o755, true);
        }

        cache()->clear();

        if (function_exists('app') && app()->has('Flute\Core\Update\Services\UpdateService')) {
            app('Flute\Core\Update\Services\UpdateService')->clearCache();
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}
