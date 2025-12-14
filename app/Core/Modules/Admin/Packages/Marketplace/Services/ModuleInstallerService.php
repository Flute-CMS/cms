<?php

namespace Flute\Admin\Packages\Marketplace\Services;

use Exception;
use Flute\Core\App;
use Flute\Core\Composer\ComposerManager;
use Flute\Core\ModulesManager\ModuleManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use ZipArchive;

class ModuleInstallerService
{
    /**
     * HTTP клиент
     */
    protected Client $client;

    /**
     * Временная директория для загрузки модулей
     */
    protected string $tempDir;

    /**
     * Директория для модулей
     */
    protected string $modulesDir;

    /**
     * Путь к архиву модуля
     */
    protected ?string $moduleArchivePath = null;

    /**
     * Путь к распакованному модулю
     */
    protected ?string $moduleExtractPath = null;

    /**
     * Ключ модуля
     */
    protected ?string $moduleKey = null;

    /**
     * Информация о модуле из module.json
     */
    protected ?array $moduleInfo = null;

    /**
     * Директория для резервного копирования модуля
     */
    protected ?string $backupDir = null;

    /**
     * Директория установленного модуля
     */
    protected ?string $moduleFolder = null;

    /**
     * ModuleInstallerService constructor.
     */
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'http_errors' => false,
        ]);

        $this->tempDir = storage_path('app/temp/marketplace');
        $this->modulesDir = path('app/Modules');

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0o755, true);
        }
    }

    /**
     * Загрузить модуль
     *
     * @throws Exception
     */
    public function downloadModule(array $module): array
    {
        $this->keepProcessAlive();

        if (empty($module['downloadUrl'])) {
            throw new Exception(__('admin-marketplace.messages.download_failed') . ': URL не указан');
        }

        try {
            $downloadUrl = $module['downloadUrl'];
            $this->moduleArchivePath = $this->tempDir . '/' . $module['slug'] . '-' . time() . '.zip';

            if (strpos($downloadUrl, 'http') !== 0) {
                $downloadUrl = rtrim(config('app.flute_market_url', 'https://flute-cms.com'), '/') . $downloadUrl;
            }

            $response = $this->client->get($downloadUrl . '&accessKey=' . config('app.flute_key', ''), [
                'sink' => $this->moduleArchivePath,
                'allow_redirects' => false,
            ]);

            $body = method_exists($response, 'getBody') ? (string)$response->getBody() : '';
            if (
                $response->getStatusCode() === 401
            ) {
                throw new Exception('MARKETPLACE_BAD_REQUEST');
            }
            if ($response->getStatusCode() !== 200) {
                throw new Exception(__('admin-marketplace.messages.download_failed'));
            }

            return [
                'success' => true,
                'message' => __('admin-marketplace.messages.download_success'),
                'path' => $this->moduleArchivePath,
            ];
        } catch (GuzzleException $e) {
            throw new Exception(__('admin-marketplace.messages.download_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Распаковать модуль
     *
     * @throws Exception
     */
    public function extractModule(array $module): array
    {
        $this->keepProcessAlive();

        if (empty($this->moduleArchivePath) || !file_exists($this->moduleArchivePath)) {
            throw new Exception(__('admin-marketplace.messages.extract_failed') . ': Архив не найден');
        }

        $zip = new ZipArchive();

        if ($zip->open($this->moduleArchivePath) !== true) {
            throw new Exception(__('admin-marketplace.messages.extract_failed') . ': Не удалось открыть архив');
        }

        $this->moduleExtractPath = $this->tempDir . '/extract-' . $module['slug'] . '-' . time();

        if (!is_dir($this->moduleExtractPath)) {
            mkdir($this->moduleExtractPath, 0o755, true);
        }

        $zip->extractTo($this->moduleExtractPath);
        $zip->close();

        $rootDir = $this->moduleExtractPath;
        $items = scandir($this->moduleExtractPath);

        if (count($items) === 3) { // '.', '..' и одна директория
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($this->moduleExtractPath . '/' . $item)) {
                    $rootDir = $this->moduleExtractPath . '/' . $item;
                    $this->moduleKey = $item;

                    break;
                }
            }
        }

        if (!file_exists($rootDir . '/module.json')) {
            throw new Exception(__('admin-marketplace.messages.extract_failed') . ': Отсутствует файл module.json');
        }

        return [
            'success' => true,
            'message' => __('admin-marketplace.messages.extract_success'),
            'path' => $rootDir,
            'key' => $this->moduleKey,
        ];
    }

    /**
     * Проверить совместимость модуля
     *
     * @throws Exception
     */
    public function validateModule(array $module): array
    {
        if (empty($this->moduleExtractPath) || !is_dir($this->moduleExtractPath)) {
            throw new Exception(__('admin-marketplace.messages.validate_failed') . ': Распакованные файлы не найдены');
        }

        $moduleJsonPath = $this->moduleExtractPath;

        if ($this->moduleKey) {
            $moduleJsonPath .= '/' . $this->moduleKey . '/module.json';
        } else {
            $moduleJsonPath .= '/module.json';
        }

        if (!file_exists($moduleJsonPath)) {
            throw new Exception(__('admin-marketplace.messages.validate_failed') . ': Отсутствует файл module.json');
        }

        $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);

        if (empty($moduleJson)) {
            throw new Exception(__('admin-marketplace.messages.validate_failed') . ': Неверный формат module.json');
        }

        // Сохраняем информацию о модуле для дальнейшего использования
        $this->moduleInfo = $moduleJson;

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['php'])) {
            $requiredPhp = $moduleJson['requires']['php'];

            if (!$this->checkPhpVersion($requiredPhp)) {
                throw new Exception(__('admin-marketplace.messages.validate_failed') . ': Требуется PHP ' . $requiredPhp);
            }
        }

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['flute'])) {
            $requiredFlute = $moduleJson['requires']['flute'];

            if (!$this->checkFluteVersion($requiredFlute)) {
                throw new Exception(__('admin-marketplace.messages.validate_failed') . ': Требуется Flute ' . $requiredFlute);
            }
        }

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['modules'])) {
            $requiredModules = $moduleJson['requires']['modules'];
            $missingModules = $this->checkModuleDependencies($requiredModules);

            if (!empty($missingModules)) {
                throw new Exception(__('admin-marketplace.messages.validate_failed') . ': Отсутствуют модули: ' . implode(', ', $missingModules));
            }
        }

        return [
            'success' => true,
            'message' => __('admin-marketplace.messages.validate_success'),
            'moduleInfo' => $moduleJson,
        ];
    }

    /**
     * Установить модуль
     *
     * @throws Exception
     */
    public function installModule(array $module): array
    {
        $this->keepProcessAlive();

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

        $moduleFolder = $this->sanitizeModuleFolderName((string) ($moduleName ?? $this->moduleKey ?? $module['slug']));
        $destination = $this->modulesDir . '/' . $moduleFolder;

        $this->backupDir = null;
        if (is_dir($destination)) {
            if (config('app.create_backup')) {
                $this->backupDir = storage_path('backup/modules/' . $moduleFolder . '-' . date('Y-m-d-His'));

                if (!is_dir($this->backupDir)) {
                    mkdir($this->backupDir, 0o755, true);
                }

                $this->copyDirectory($destination, $this->backupDir);
            }

            $this->removeDirectory($destination);
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0o755, true);
        }

        $this->copyDirectory($source, $destination);
        $this->waitForCopiedModuleJson($destination);
        $this->moduleFolder = $moduleFolder;

        return [
            'success' => true,
            'message' => __('admin-marketplace.messages.install_success'),
            'moduleFolder' => $moduleFolder,
            'backupDir' => $this->backupDir,
        ];
    }

    /**
     * Обновить зависимости Composer
     *
     * @throws Exception
     */
    public function updateComposerDependencies(): array
    {
        $this->keepProcessAlive();

        if (empty($this->moduleFolder)) {
            return [
                'success' => true,
                'message' => 'No module folder specified for composer update',
            ];
        }

        $composerJsonPath = $this->modulesDir . '/' . $this->moduleFolder . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            return [
                'success' => true,
                'message' => 'No composer dependencies to update',
            ];
        }

        try {
            /** @var ComposerManager $composerManager */
            $composerManager = app(ComposerManager::class);
            $composerManager->install();

            return [
                'success' => true,
                'message' => __('admin-marketplace.messages.composer_success'),
            ];
        } catch (Exception $e) {
            throw new Exception(__('admin-marketplace.messages.composer_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Завершить установку
     */
    public function finishInstallation(): array
    {
        if ($this->moduleArchivePath && file_exists($this->moduleArchivePath)) {
            unlink($this->moduleArchivePath);
        }

        if ($this->moduleExtractPath && is_dir($this->moduleExtractPath)) {
            $this->removeDirectory($this->moduleExtractPath);
        }

        app(\Flute\Core\ModulesManager\ModuleManager::class)->clearCache();

        if (function_exists('cache_warmup_mark')) {
            cache_warmup_mark();
        }

        return [
            'success' => true,
            'message' => __('admin-marketplace.messages.installation_complete'),
        ];
    }

    /**
     * Откатить установку модуля
     */
    public function rollbackInstallation(string $moduleFolder, ?string $backupDir = null): array
    {
        $destination = $this->modulesDir . '/' . $moduleFolder;

        if (is_dir($destination)) {
            $this->removeDirectory($destination);
        }

        if ($backupDir && is_dir($backupDir)) {
            $this->copyDirectory($backupDir, $destination);
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

    protected function waitForCopiedModuleJson(string $destinationDir, int $timeoutSeconds = 15): void
    {
        $this->keepProcessAlive();

        $start = microtime(true);

        while ((microtime(true) - $start) < $timeoutSeconds) {
            clearstatcache(true);

            if (is_file($destinationDir . '/module.json')) {
                return;
            }

            $jsonFinder = finder();
            $jsonFinder
                ->files()
                ->name('module.json')
                ->in($destinationDir)
                ->depth('== 1');

            foreach ($jsonFinder as $jsonFile) {
                if ($jsonFile->isFile()) {
                    return;
                }
            }

            usleep(250000);
        }

        throw new Exception(__('admin-marketplace.messages.install_failed') . ': Файл module.json не найден после копирования');
    }

    /**
     * Проверить версию PHP
     */
    protected function checkPhpVersion(string $requiredVersion): bool
    {
        return version_compare(PHP_VERSION, $requiredVersion, '>=');
    }

    /**
     * Проверить версию Flute
     */
    protected function checkFluteVersion(string $requiredVersion): bool
    {
        $fluteVersion = App::VERSION;

        return version_compare($fluteVersion, $requiredVersion, '>=');
    }

    /**
     * Проверить зависимости от других модулей
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
            @chown($destination, fileowner($source));
            @chgrp($destination, filegroup($source));
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
                @chown($destinationPath, fileowner($sourcePath));
                @chgrp($destinationPath, filegroup($sourcePath));
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
     * Ensure long-running operations (download/extract/install) are not interrupted
     */
    protected function keepProcessAlive(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
    }
}
