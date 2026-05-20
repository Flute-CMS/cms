<?php

namespace Flute\Admin\Packages\Marketplace\Services\Concerns;

use Exception;
use Flute\Core\Support\FileUploader;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

trait HandlesModuleExtraction
{
    /**
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

        if (count($items) === 3) {
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
}
