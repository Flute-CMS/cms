<?php

namespace Flute\Admin\Packages\Marketplace\Services;

use Exception;
use FilesystemIterator;
use Flute\Core\App;
use Flute\Core\Composer\ComposerManager;
use Flute\Core\ModulesManager\Actions\Concerns\FlushesTranslationCache;
use Flute\Core\ModulesManager\ModuleManager;
use Flute\Core\Support\FileUploader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Throwable;

class ModuleInstallerService
{
    use FlushesTranslationCache;

    private const ZIP_MAGIC_LOCAL = "PK\x03\x04";

    private const ZIP_MAGIC_EMPTY = "PK\x05\x06";

    private const ZIP_MAGIC_SPANNED = "PK\x07\x08";

    /**
     * Official Flute marketplace hosts allowed for module ZIP downloads (always merged with config mirrors).
     */
    private const TRUSTED_FLUTE_DOWNLOAD_HOSTS = [
        'flute-cms.com',
        'api.flute-cms.com',
        'mirror.flute-cms.com',
    ];

    /**
     * Single-flight lock for download → install → composer (concurrent admin tabs).
     *
     * @var resource|null
     */
    private $marketplaceLockHandle = null;

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
            'timeout' => 600.0,
            'connect_timeout' => 25.0,
            'http_errors' => false,
            'verify' => true,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => true,
                'track_redirects' => false,
            ],
        ]);

        $this->tempDir = storage_path('app/temp/marketplace');
        $this->modulesDir = path('app/Modules');

        $this->ensureWritableDirectory($this->tempDir, true);
    }

    /**
     * Загрузить модуль
     *
     * @throws Exception
     */
    public function downloadModule(array $module): array
    {
        $this->keepProcessAlive();
        $this->ensureMarketplaceWorkspace();

        if (empty($module['downloadUrl'])) {
            throw new Exception(__('admin-marketplace.messages.download_failed') . ': URL не указан');
        }

        $this->acquireMarketplaceLock();

        try {
            $downloadUrl = $module['downloadUrl'];
            $this->moduleArchivePath = $this->tempDir . '/' . $module['slug'] . '-' . time() . '.zip';

            if (is_file($this->moduleArchivePath)) {
                @unlink($this->moduleArchivePath);
            }

            if (!str_starts_with($downloadUrl, 'http')) {
                $api = new \Flute\Core\Services\FluteApiClient();
                $downloadUrl = rtrim($api->getActiveBaseUrl(), '/') . '/' . ltrim($downloadUrl, '/');
            }

            $requestUrl = $this->buildDownloadRequestUrl($downloadUrl);
            $this->assertDownloadUrlAllowed($requestUrl);

            $response = $this->client->get($requestUrl, [
                'sink' => $this->moduleArchivePath,
            ]);

            if ($response->getStatusCode() === 401) {
                $this->cleanupFailedDownloadArtifact();
                throw new Exception('MARKETPLACE_BAD_REQUEST');
            }
            if ($response->getStatusCode() !== 200) {
                $this->cleanupFailedDownloadArtifact();
                throw new Exception(__('admin-marketplace.messages.download_failed'));
            }

            clearstatcache(true, $this->moduleArchivePath);
            $this->assertValidZipFile($this->moduleArchivePath);

            return [
                'success' => true,
                'message' => __('admin-marketplace.messages.download_success'),
                'path' => $this->moduleArchivePath,
            ];
        } catch (GuzzleException $e) {
            $this->cleanupFailedDownloadArtifact();
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
        $this->ensureMarketplaceWorkspace();

        if (empty($this->moduleArchivePath) || !file_exists($this->moduleArchivePath)) {
            throw new Exception(__('admin-marketplace.messages.extract_failed') . ': Архив не найден');
        }

        $this->moduleExtractPath = $this->tempDir . '/extract-' . $module['slug'] . '-' . time();

        if (!is_dir($this->moduleExtractPath)) {
            mkdir($this->moduleExtractPath, 0o755, true);
        }

        app(FileUploader::class)->safeExtractZip($this->moduleArchivePath, $this->moduleExtractPath);

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
                throw new Exception(
                    __('admin-marketplace.messages.validate_failed') . ': Требуется PHP ' . $requiredPhp,
                );
            }
        }

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['flute'])) {
            $requiredFlute = $moduleJson['requires']['flute'];

            if (!$this->checkFluteVersion($requiredFlute)) {
                throw new Exception(
                    __('admin-marketplace.messages.validate_failed') . ': Требуется Flute ' . $requiredFlute,
                );
            }
        }

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['modules'])) {
            $requiredModules = $moduleJson['requires']['modules'];
            $missingModules = $this->checkModuleDependencies($requiredModules);

            if (!empty($missingModules)) {
                throw new Exception(
                    __('admin-marketplace.messages.validate_failed') . ': Отсутствуют модули: '
                        . implode(', ', $missingModules),
                );
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

            clearstatcache(true, $this->modulesDir . '/' . $this->moduleFolder);
            $this->invalidateComposerAutoloadCaches();
            $modulePath = $this->modulesDir . '/' . $this->moduleFolder;
            if (is_dir($modulePath)) {
                $this->invalidatePhpOpcacheUnder($modulePath);
            }

            return [
                'success' => true,
                'message' => __('admin-marketplace.messages.composer_success'),
            ];
        } catch (Throwable $e) {
            throw new Exception(__('admin-marketplace.messages.composer_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Завершить установку
     */
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

    protected function waitForCopiedModuleJson(string $destinationDir, int $timeoutSeconds = 20): void
    {
        $this->keepProcessAlive();

        $start = microtime(true);
        $moduleJsonPath = $destinationDir . '/module.json';

        while (( microtime(true) - $start ) < $timeoutSeconds) {
            clearstatcache(true, $destinationDir);
            clearstatcache(true, $moduleJsonPath);

            if (is_file($moduleJsonPath)) {
                $content = @file_get_contents($moduleJsonPath);
                if ($content !== false && strlen($content) > 10) {
                    if (function_exists('opcache_invalidate')) {
                        @opcache_invalidate($moduleJsonPath, true);
                    }

                    return;
                }
            }

            if (is_dir($destinationDir)) {
                try {
                    $jsonFinder = finder();
                    $jsonFinder->files()->name('module.json')->in($destinationDir)->depth('== 1');

                    foreach ($jsonFinder as $jsonFile) {
                        if ($jsonFile->isFile()) {
                            $nestedPath = $jsonFile->getRealPath();
                            $content = @file_get_contents($nestedPath);
                            if ($content !== false && strlen($content) > 10) {
                                if (function_exists('opcache_invalidate')) {
                                    @opcache_invalidate($nestedPath, true);
                                }

                                return;
                            }
                        }
                    }
                } catch (DirectoryNotFoundException $e) {
                    logs('marketplace')->debug('module.json finder: ' . $e->getMessage());
                }
            }

            usleep(300000);
        }

        throw new Exception(
            __('admin-marketplace.messages.install_failed')
            . ': Файл module.json не найден после копирования ('
            . $moduleJsonPath
            . ')',
        );
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
            mkdir($destination, 0o755, true);
        }

        $directory = opendir($source);
        if ($directory === false) {
            return false;
        }

        while (( $file = readdir($directory) ) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destinationPath = $destination . '/' . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath);
            } else {
                if (copy($sourcePath, $destinationPath)) {
                    chmod($destinationPath, 0o644);
                }
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

    protected function ensureWritableDirectory(string $dir, bool $create): void
    {
        if ($create && !is_dir($dir)) {
            if (!@mkdir($dir, 0o755, true) && !is_dir($dir)) {
                throw new Exception(__('admin-marketplace.messages.directory_not_writable', ['path' => $dir]));
            }
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            throw new Exception(__('admin-marketplace.messages.directory_not_writable', ['path' => $dir]));
        }
    }

    protected function ensureMarketplaceWorkspace(): void
    {
        $this->ensureWritableDirectory($this->tempDir, true);
        $this->ensureWritableDirectory($this->modulesDir, true);
    }

    protected function acquireMarketplaceLock(): void
    {
        if ($this->marketplaceLockHandle !== null) {
            return;
        }

        $lockPath = $this->tempDir . '/.marketplace-install.lock';
        $handle = @fopen($lockPath, 'cb');
        if ($handle === false) {
            throw new Exception(__('admin-marketplace.messages.install_failed') . ': lock file');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new Exception(__('admin-marketplace.messages.concurrent_marketplace_operation'));
        }

        $this->marketplaceLockHandle = $handle;
    }

    protected function releaseMarketplaceLock(): void
    {
        if ($this->marketplaceLockHandle === null) {
            return;
        }

        flock($this->marketplaceLockHandle, LOCK_UN);
        fclose($this->marketplaceLockHandle);
        $this->marketplaceLockHandle = null;
    }

    protected function buildDownloadRequestUrl(string $downloadUrl): string
    {
        $key = (string) config('app.flute_key', '');
        $separator = str_contains($downloadUrl, '?') ? '&' : '?';

        return $downloadUrl . $separator . 'accessKey=' . rawurlencode($key);
    }

    /**
     * Limit server-side downloads to configured marketplace hosts (mitigates SSRF if API JSON is tampered).
     *
     * @return array<string,bool> map host(lower) => true
     */
    protected function getTrustedMarketplaceDownloadHostMap(): array
    {
        $map = [];
        $primary = rtrim((string) config('app.flute_market_url', 'https://flute-cms.com'), '/');
        $mirrors = config('app.flute_market_mirrors', []);
        $extra = config('app.flute_market_download_hosts', []);

        foreach ([$primary, ...( is_array($mirrors) ? $mirrors : [] )] as $url) {
            if (!is_string($url) || $url === '') {
                continue;
            }
            $parsed = parse_url($url);
            if (!empty($parsed['host'])) {
                $map[strtolower((string) $parsed['host'])] = true;
            }
        }

        foreach (is_array($extra) ? $extra : [] as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }
            if (str_contains($item, '://')) {
                $parsed = parse_url($item);
                if (!empty($parsed['host'])) {
                    $map[strtolower((string) $parsed['host'])] = true;
                }
            } else {
                $map[strtolower($item)] = true;
            }
        }

        foreach (self::TRUSTED_FLUTE_DOWNLOAD_HOSTS as $host) {
            $map[strtolower($host)] = true;
        }

        return $map;
    }

    protected function assertDownloadUrlAllowed(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            throw new Exception(__('admin-marketplace.messages.download_url_not_allowed'));
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);

        $allowHttp = function_exists('is_debug') && is_debug() || (bool) config('app.development_mode', false);
        if ($scheme !== 'https' && !( $scheme === 'http' && $allowHttp )) {
            throw new Exception(__('admin-marketplace.messages.download_url_not_allowed'));
        }

        $trusted = $this->getTrustedMarketplaceDownloadHostMap();
        if ($trusted === [] || !isset($trusted[$host])) {
            throw new Exception(__('admin-marketplace.messages.download_url_not_allowed'));
        }
    }

    protected function cleanupFailedDownloadArtifact(): void
    {
        if ($this->moduleArchivePath !== null && is_file($this->moduleArchivePath)) {
            @unlink($this->moduleArchivePath);
        }
    }

    protected function assertValidZipFile(string $path): void
    {
        if (!is_file($path)) {
            throw new Exception(__('admin-marketplace.messages.invalid_zip'));
        }

        $size = filesize($path);
        if ($size === false || $size < 22) {
            @unlink($path);
            throw new Exception(__('admin-marketplace.messages.invalid_zip'));
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            @unlink($path);
            throw new Exception(__('admin-marketplace.messages.invalid_zip'));
        }

        $magic = fread($handle, 4);
        fclose($handle);

        if (
            $magic !== self::ZIP_MAGIC_LOCAL
            && $magic !== self::ZIP_MAGIC_EMPTY
            && $magic !== self::ZIP_MAGIC_SPANNED
        ) {
            @unlink($path);
            throw new Exception(__('admin-marketplace.messages.invalid_zip'));
        }
    }

    /**
     * Normalize permissions for production (dirs 0755, files 0644).
     */
    protected function applyModuleFilesystemAcl(string $root): void
    {
        if (!is_dir($root)) {
            return;
        }

        @chmod($root, 0o755);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @chmod($file->getPathname(), 0o755);
            } elseif ($file->isFile()) {
                @chmod($file->getPathname(), 0o644);
            }
        }
    }

    protected function invalidatePhpOpcacheUnder(string $root): void
    {
        if (!function_exists('opcache_invalidate') || !is_dir($root)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            @opcache_invalidate($file->getPathname(), true);
        }
    }

    protected function invalidateComposerAutoloadCaches(): void
    {
        if (!function_exists('opcache_invalidate')) {
            return;
        }

        $dir = path('vendor/composer');
        if (!is_dir($dir)) {
            return;
        }

        $files = [
            'autoload_real.php',
            'autoload_static.php',
            'autoload_classmap.php',
            'autoload_psr4.php',
            'autoload_files.php',
            'autoload_namespaces.php',
            'installed.php',
        ];

        foreach ($files as $name) {
            $full = $dir . DIRECTORY_SEPARATOR . $name;
            if (is_file($full)) {
                @opcache_invalidate($full, true);
            }
        }
    }

    /**
     * When opcache.validate_timestamps=0, invalidate may not refresh all FPM workers until process reload.
     */
    protected function logOpcacheProductionHints(): void
    {
        if (!function_exists('opcache_get_status')) {
            return;
        }

        $status = @opcache_get_status(false);
        if (!is_array($status) || empty($status['opcache_enabled'])) {
            return;
        }

        $validate = ini_get('opcache.validate_timestamps');
        if ($validate === '0' || $validate === false || strtolower((string) $validate) === 'off') {
            logs('marketplace')->warning(
                'OPcache validate_timestamps is off: some PHP-FPM workers may serve stale code until reload. '
                . 'After marketplace install/update, reload php-fpm (or enable validate_timestamps in php.ini).',
            );
        }
    }
}
