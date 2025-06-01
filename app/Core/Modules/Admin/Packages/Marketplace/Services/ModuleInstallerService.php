<?php

namespace Flute\Admin\Packages\Marketplace\Services;

use Exception;
use Flute\Core\App;
use ZipArchive;
use Flute\Core\Composer\ComposerManager;
use Flute\Core\ModulesManager\ModuleActions;
use Flute\Core\ModulesManager\ModuleManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ModuleInstallerService
{
    /**
     * HTTP клиент
     * 
     * @var Client
     */
    protected Client $client;

    /**
     * Временная директория для загрузки модулей
     * 
     * @var string
     */
    protected string $tempDir;

    /**
     * Директория для модулей
     * 
     * @var string
     */
    protected string $modulesDir;

    /**
     * Путь к архиву модуля
     * 
     * @var string|null
     */
    protected ?string $moduleArchivePath = null;

    /**
     * Путь к распакованному модулю
     * 
     * @var string|null
     */
    protected ?string $moduleExtractPath = null;

    /**
     * Ключ модуля
     * 
     * @var string|null
     */
    protected ?string $moduleKey = null;

    /**
     * Информация о модуле из module.json
     * 
     * @var array|null
     */
    protected ?array $moduleInfo = null;

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
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Загрузить модуль
     * 
     * @param array $module
     * @return array
     * @throws Exception
     */
    public function downloadModule(array $module): array
    {
        if (empty($module['downloadUrl'])) {
            throw new Exception(__('admin-marketplace.messages.download_failed') . ': URL не указан');
        }

        try {
            $downloadUrl = $module['downloadUrl'];
            $this->moduleArchivePath = $this->tempDir . '/' . $module['slug'] . '-' . time() . '.zip';

            if (strpos($downloadUrl, 'http') !== 0) {
                $downloadUrl = config('app.flute_market_url', 'https://flute-cms.com/api') . $downloadUrl;
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
     * @param array $module
     * @return array
     * @throws Exception
     */
    public function extractModule(array $module): array
    {
        if (empty($this->moduleArchivePath) || !file_exists($this->moduleArchivePath)) {
            throw new Exception(__('admin-marketplace.messages.extract_failed') . ': Архив не найден');
        }

        $zip = new ZipArchive();

        if ($zip->open($this->moduleArchivePath) !== true) {
            throw new Exception(__('admin-marketplace.messages.extract_failed') . ': Не удалось открыть архив');
        }

        $this->moduleExtractPath = $this->tempDir . '/extract-' . $module['slug'] . '-' . time();

        if (!is_dir($this->moduleExtractPath)) {
            mkdir($this->moduleExtractPath, 0755, true);
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
     * @param array $module
     * @return array
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
     * @param array $module
     * @return array
     * @throws Exception
     */
    public function installModule(array $module): array
    {
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

        $moduleFolder = $moduleName ?? $this->moduleKey ?? $module['slug'];
        $destination = $this->modulesDir . '/' . $moduleFolder;

        if (is_dir($destination)) {
            if (config('app.create_backup')) {
                $backupDir = storage_path('backup/modules/' . $moduleFolder . '-' . date('Y-m-d-His'));

                if (!is_dir($backupDir)) {
                    mkdir($backupDir, 0755, true);
                }

                $this->copyDirectory($destination, $backupDir);
            }

            $this->removeDirectory($destination);
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $this->copyDirectory($source, $destination);

        try {
            /** @var ModuleManager $moduleManager */
            $moduleManager = app(ModuleManager::class);
            $moduleManager->refreshModules();

            $moduleKey = $moduleFolder;

            if ($moduleManager->issetModule($moduleKey)) {
                $moduleInfo = $moduleManager->getModule($moduleKey);

                $moduleActions = new ModuleActions();

                if ($moduleInfo->status === ModuleManager::NOTINSTALLED) {
                    $moduleActions->installModule($moduleInfo, $moduleManager);
                } else {
                    $moduleActions->updateModule($moduleInfo, $moduleManager);
                }

                $moduleManager->refreshModules();

                if ($moduleInfo->status !== ModuleManager::ACTIVE) {
                    $moduleActions->activateModule($moduleInfo, $moduleManager);
                }
            } else {
                throw new Exception(__('admin-marketplace.messages.install_failed') . ': Модуль не найден после копирования файлов');
            }
        } catch (Exception $e) {
            throw new Exception(__('admin-marketplace.messages.install_failed') . ': ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => __('admin-marketplace.messages.install_success'),
        ];
    }

    /**
     * Обновить зависимости Composer
     * 
     * @return array
     * @throws Exception
     */
    public function updateComposerDependencies(): array
    {
        try {
            /** @var ComposerManager $composerManager */
            $composerManager = app(ComposerManager::class);
            $composerManager->update();

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
     * 
     * @return array
     */
    public function finishInstallation(): array
    {
        if ($this->moduleArchivePath && file_exists($this->moduleArchivePath)) {
            unlink($this->moduleArchivePath);
        }

        if ($this->moduleExtractPath && is_dir($this->moduleExtractPath)) {
            $this->removeDirectory($this->moduleExtractPath);
        }

        cache()->clear();

        $viewsCachePath = storage_path('app/views');
        if (is_dir($viewsCachePath)) {
            $files = scandir($viewsCachePath);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $path = $viewsCachePath . '/' . $file;
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        return [
            'success' => true,
            'message' => __('admin-marketplace.messages.installation_complete'),
        ];
    }

    /**
     * Проверить версию PHP
     * 
     * @param string $requiredVersion
     * @return bool
     */
    protected function checkPhpVersion(string $requiredVersion): bool
    {
        return version_compare(PHP_VERSION, $requiredVersion, '>=');
    }

    /**
     * Проверить версию Flute
     * 
     * @param string $requiredVersion
     * @return bool
     */
    protected function checkFluteVersion(string $requiredVersion): bool
    {
        $fluteVersion = App::VERSION;
        return version_compare($fluteVersion, $requiredVersion, '>=');
    }

    /**
     * Проверить зависимости от других модулей
     * 
     * @param array $requiredModules
     * @return array
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
     * 
     * @param string $source
     * @param string $destination
     * @return bool
     */
    protected function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $directory = opendir($source);

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
            }
        }

        closedir($directory);
        return true;
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
}
