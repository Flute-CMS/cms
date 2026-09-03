<?php

namespace Flute\Admin\Packages\Marketplace\Services\Concerns;

use Exception;
use Flute\Core\ModulesManager\ModuleManager;

trait HandlesModuleInstall
{
    /**
     * @throws Exception
     */
    public function installModule(array $module): array
    {
        $this->keepProcessAlive();
        $this->ensureMarketplaceWorkspace();

        if (empty($this->moduleExtractPath) || !is_dir($this->moduleExtractPath)) {
            throw new Exception(__('admin-marketplace.messages.install_failed') . ': Распакованные файлы не найдены');
        }

        $source = $this->moduleExtractPath;

        if ($this->moduleKey) {
            $source = $this->moduleExtractPath . '/' . $this->moduleKey;
        }

        $moduleName = null;
        if ($this->moduleInfo && !empty($this->moduleInfo['name'])) {
            $moduleName = $this->moduleInfo['name'];
        }

        $moduleFolder = $this->sanitizeModuleFolderName(
            (string) ( $moduleName ?? $this->moduleKey ?? $module['slug'] ),
        );
        $destination = $this->modulesDir . '/' . $moduleFolder;

        $this->backupDir = null;
        if (is_dir($destination)) {
            if (config('app.create_backup')) {
                $this->backupDir = storage_path('backup/modules/' . $moduleFolder . '-' . date('Y-m-d-His'));

                $this->ensureWritableDirectory(dirname((string) $this->backupDir), true);
                $this->ensureWritableDirectory($this->backupDir, true);

                $this->copyDirectory($destination, $this->backupDir);
                $this->applyModuleFilesystemAcl($this->backupDir);
            }

            $this->removeDirectory($destination);
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0o755, true);
        }

        $this->copyDirectory($source, $destination);

        clearstatcache(true, $destination);
        $this->applyModuleFilesystemAcl($destination);
        $this->invalidatePhpOpcacheUnder($destination);

        $this->waitForCopiedModuleJson($destination);
        $this->moduleFolder = $moduleFolder;

        return [
            'success' => true,
            'message' => __('admin-marketplace.messages.install_success'),
            'moduleFolder' => $moduleFolder,
            'backupDir' => $this->backupDir,
        ];
    }

    public function finishInstallation(): array
    {
        try {
            if ($this->moduleArchivePath && file_exists($this->moduleArchivePath)) {
                @unlink($this->moduleArchivePath);
            }

            if ($this->moduleExtractPath && is_dir($this->moduleExtractPath)) {
                $this->removeDirectory($this->moduleExtractPath);
            }

            if ($this->moduleFolder !== null && $this->moduleFolder !== '') {
                $installedPath = $this->modulesDir . '/' . $this->moduleFolder;
                if (is_dir($installedPath)) {
                    $this->invalidatePhpOpcacheUnder($installedPath);
                }
            }

            app(ModuleManager::class)->clearCache();
            $this->flushCompiledTranslations();
            $this->logOpcacheProductionHints();

            if (function_exists('cache_warmup_mark')) {
                cache_warmup_mark();
            }

            return [
                'success' => true,
                'message' => __('admin-marketplace.messages.installation_complete'),
            ];
        } finally {
            $this->releaseMarketplaceLock();
        }
    }

    public function rollbackInstallation(string $moduleFolder, ?string $backupDir = null): array
    {
        $destination = $this->modulesDir . '/' . $moduleFolder;

        if (is_dir($destination)) {
            $this->removeDirectory($destination);
        }

        if ($backupDir && is_dir($backupDir)) {
            $this->copyDirectory($backupDir, $destination);
            if (is_dir($destination)) {
                $this->applyModuleFilesystemAcl($destination);
                $this->invalidatePhpOpcacheUnder($destination);
            }
            $this->removeDirectory($backupDir);
        }

        return [
            'success' => true,
            'message' => __('admin-marketplace.messages.rollback_success'),
        ];
    }

    protected function sanitizeModuleFolderName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || $name === '.' || $name === '..') {
            throw new Exception(__('admin-marketplace.messages.install_failed') . ': Некорректное имя модуля');
        }

        $normalized = str_replace('\\', '/', $name);
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;
        $normalized = trim($normalized, '/');

        if ($normalized === '' || $normalized === '.' || $normalized === '..') {
            throw new Exception(__('admin-marketplace.messages.install_failed') . ': Некорректное имя модуля');
        }

        if (preg_match('#(^|/)\.\.(?:/|$)#', $normalized)) {
            throw new Exception(__('admin-marketplace.messages.install_failed') . ': Некорректное имя модуля');
        }

        $base = basename($normalized);
        $base = trim($base);

        if ($base === '' || $base === '.' || $base === '..') {
            throw new Exception(__('admin-marketplace.messages.install_failed') . ': Некорректное имя модуля');
        }

        return $base;
    }
}
