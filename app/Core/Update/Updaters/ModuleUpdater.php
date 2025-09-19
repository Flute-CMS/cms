<?php

namespace Flute\Core\Update\Updaters;

use Exception;
use Flute\Core\Composer\ComposerManager;
use Flute\Core\ModulesManager\ModuleActions;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\ModulesManager\ModuleManager;
use ZipArchive;

class ModuleUpdater extends AbstractUpdater
{
    /**
     * Information about the module
     */
    protected ModuleInformation $module;

    /**
     * Directories that should not be updated
     */
    protected array $excludedPaths = [
        'Resources/config',
        'config',
    ];

    protected ?string $backupDir = null;

    /**
     * ModuleUpdater constructor.
     */
    public function __construct(ModuleInformation $module)
    {
        $this->module = $module;
    }

    /**
     * Get the current version
     */
    public function getCurrentVersion(): string
    {
        return $this->module->version ?? '1.0.0';
    }

    /**
     * Get the identifier
     */
    public function getIdentifier(): ?string
    {
        return $this->module->key;
    }

    /**
     * Get the type
     */
    public function getType(): string
    {
        return 'module';
    }

    /**
     * Get the name
     */
    public function getName(): string
    {
        return $this->module->name;
    }

    /**
     * Get the description
     */
    public function getDescription(): string
    {
        return $this->module->description;
    }

    /**
     * Update the module
     */
    public function update(array $data): bool
    {
        if (empty($data['package_file']) || !file_exists($data['package_file'])) {
            logs()->error('Module update package file not found: ' . ($data['package_file'] ?? 'null'));

            return false;
        }

        $packageFile = $data['package_file'];
        $extractDir = storage_path('app/temp/updates/module-' . $this->module->key . '-' . time());

        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0o755, true);
        }

        try {
            $this->createBackup();

            $modulePath = $this->extractModuleArchive($packageFile, $extractDir);
            if (!$modulePath) {
                return false;
            }

            if (!$this->validateModule($modulePath)) {
                logs()->error('Module validation failed: incompatible with current CMS version');

                return false;
            }

            $moduleDir = $this->getModuleDirectory();
            $this->copyModuleFiles($modulePath, $moduleDir);

            // Update composer dependencies
            /** @var ComposerManager $composerManager */
            $composerManager = app(ComposerManager::class);

            try {
                $composerManager->install();
            } catch (Exception $e) {
                // Rollback
                $this->removeDirectory($moduleDir);
                if ($this->backupDir && is_dir($this->backupDir)) {
                    $this->copyDirectory($this->backupDir, $moduleDir);
                }

                throw $e;
            }

            $this->updateModuleInformation();

            $this->clearCache();
            cache()->clear();
            /** @var ModuleManager $moduleManager */
            $moduleManager = app(ModuleManager::class);
            $moduleManager->clearCache();
            $moduleManager->refreshModules();

            $this->removeDirectory($extractDir);

            return true;
        } catch (Exception $e) {
            logs()->error('Error during module update: ' . $e->getMessage());
            if (is_dir($extractDir)) {
                $this->removeDirectory($extractDir);
            }

            return false;
        }
    }

    /**
     * Extract the module archive
     *
     * @return string|false
     */
    protected function extractModuleArchive(string $packageFile, string $extractDir)
    {
        $zip = new ZipArchive();
        if ($zip->open($packageFile) !== true) {
            logs()->error('Failed to open module update package: ' . $packageFile);

            return false;
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $rootDir = $extractDir;
        $items = scandir($extractDir);

        if (count($items) === 3) { // '.', '..' and one directory
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($extractDir . '/' . $item)) {
                    $rootDir = $extractDir . '/' . $item;

                    break;
                }
            }
        }

        if (!file_exists($rootDir . '/module.json')) {
            logs()->error('Invalid module archive: module.json not found');

            return false;
        }

        return $rootDir;
    }

    /**
     * Check compatibility of the module
     */
    protected function validateModule(string $modulePath): bool
    {
        $moduleJsonPath = $modulePath . '/module.json';
        if (!file_exists($moduleJsonPath)) {
            logs()->error('Module validation failed: module.json not found');

            return false;
        }

        $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);
        if (empty($moduleJson)) {
            logs()->error('Module validation failed: invalid module.json format');

            return false;
        }

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['php'])) {
            $requiredPhp = $moduleJson['requires']['php'];
            if (!$this->checkPhpVersion($requiredPhp)) {
                logs()->error('Module validation failed: PHP version ' . $requiredPhp . ' required');

                return false;
            }
        }

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['flute'])) {
            $requiredFlute = $moduleJson['requires']['flute'];
            if (!$this->checkFluteVersion($requiredFlute)) {
                logs()->error('Module validation failed: Flute version ' . $requiredFlute . ' required');

                return false;
            }
        }

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['modules'])) {
            $requiredModules = $moduleJson['requires']['modules'];
            $missingModules = $this->checkModuleDependencies($requiredModules);

            if (!empty($missingModules)) {
                logs()->error('Module validation failed: missing required modules: ' . implode(', ', $missingModules));

                return false;
            }
        }

        return true;
    }

    /**
     * Update the module information
     */
    protected function updateModuleInformation(): bool
    {
        try {
            /** @var ModuleManager $moduleManager */
            $moduleManager = app(ModuleManager::class);
            $moduleManager->refreshModules();

            if ($moduleManager->issetModule($this->module->key)) {
                $moduleInfo = $moduleManager->getModule($this->module->key);
                $moduleActions = new ModuleActions();
                $moduleActions->updateModule($moduleInfo, $moduleManager);

                return true;
            }

            logs()->error('Failed to update module information: module not found after updating files');

            return false;
        } catch (Exception $e) {
            logs()->error('Failed to update module information: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get the path to the module directory
     */
    protected function getModuleDirectory(): string
    {
        $basePath = BASE_PATH;

        return $basePath . '/app/Modules/' . $this->module->key;
    }

    /**
     * Create a backup before updating
     */
    protected function createBackup(): bool
    {
        if (!config('app.create_backup')) {
            return false;
        }

        $this->backupDir = storage_path('backup/modules/' . $this->module->key . '-' . date('Y-m-d-His'));

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0o755, true);
        }

        $moduleDir = $this->getModuleDirectory();
        $this->copyDirectory($moduleDir, $this->backupDir);

        logs()->info('Module backup created: ' . $this->backupDir);

        return true;
    }

    /**
     * Copy module files, excluding specified directories
     */
    protected function copyModuleFiles(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        $directory = opendir($source);

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destinationPath = $destination . '/' . $file;

            $isExcluded = false;
            foreach ($this->excludedPaths as $excludedPath) {
                $relativePath = str_replace($source . '/', '', $sourcePath);
                if ($relativePath === $excludedPath || strpos($relativePath, $excludedPath . '/') === 0) {
                    $isExcluded = true;
                    logs()->info('Skipping excluded path: ' . $relativePath);

                    break;
                }
            }

            if ($isExcluded) {
                continue;
            }

            if (is_dir($sourcePath)) {
                if (!is_dir($destinationPath)) {
                    $dirPerms = fileperms($sourcePath) & 0o777;
                    mkdir($destinationPath, $dirPerms, true);
                    chmod($destinationPath, $dirPerms);
                    $this->safeChown($destinationPath, fileowner($sourcePath));
                    $this->safeChgrp($destinationPath, filegroup($sourcePath));
                }
                $this->copyModuleFiles($sourcePath, $destinationPath);
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
     * Copy directory recursively
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
     * Remove directory recursively
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
     * Clear the cache
     */
    protected function clearCache(): void
    {
        $viewsCachePath = storage_path('app/views');
        if (is_dir($viewsCachePath)) {
            $files = scandir($viewsCachePath);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $path = $viewsCachePath . '/' . $file;
                if (is_file($path) && strpos($file, $this->module->key) !== false) {
                    unlink($path);
                }
            }
        }

        cache()->clear();
    }

    /**
     * Check the PHP version
     */
    protected function checkPhpVersion(string $requiredVersion): bool
    {
        return version_compare(PHP_VERSION, $requiredVersion, '>=');
    }

    /**
     * Check the Flute version
     */
    protected function checkFluteVersion(string $requiredVersion): bool
    {
        $fluteVersion = \Flute\Core\App::VERSION;

        return version_compare($fluteVersion, $requiredVersion, '>=');
    }

    /**
     * Check the dependencies of other modules
     */
    protected function checkModuleDependencies(array $requiredModules): array
    {
        /** @var ModuleManager $moduleManager */
        $moduleManager = app(ModuleManager::class);
        $missingModules = [];

        foreach ($requiredModules as $moduleKey => $moduleVersion) {
            if (!$moduleManager->issetModule($moduleKey)) {
                $missingModules[] = $moduleKey;

                continue;
            }

            $moduleInfo = $moduleManager->getModule($moduleKey);

            if ($moduleInfo->status !== ModuleManager::ACTIVE) {
                $missingModules[] = $moduleKey;

                continue;
            }

            if (!empty($moduleVersion) && version_compare($moduleInfo->version ?? '1.0.0', $moduleVersion, '<')) {
                $missingModules[] = "{$moduleKey} (требуется версия {$moduleVersion})";
            }
        }

        return $missingModules;
    }
}
