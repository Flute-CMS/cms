<?php

namespace Flute\Core\Support\Concerns;

use SplFileInfo;
use Throwable;

trait HandlesModuleMeta
{
    public function getEventListeners(): array
    {
        return $this->listen;
    }

    public function setModuleName(string $moduleName): void
    {
        $this->moduleName = $moduleName;
    }

    public function getModuleName(): string
    {
        return (string) $this->moduleName;
    }

    public function setUpdateChannel(string $org, string $rep): self
    {
        $this->updateChannel = [
            'org' => $org,
            'rep' => $rep,
        ];

        return $this;
    }

    public function getUpdateChannel()
    {
        if (empty($this->updateChannel)) {
            return false;
        }

        return "https://api.github.com/repos/{$this->updateChannel['org']}/{$this->updateChannel['rep']}/releases/latest";
    }

    public function setCacheDuration(int $seconds): self
    {
        $this->defaultCacheDuration = $seconds;

        return $this;
    }

    public function isExtensionsCallable(): bool
    {
        return true;
    }

    public function clearFileCache(): void
    {
        $moduleName = $this->getModuleName();

        $this->directoryCache = [];
        $this->modulePathCache = [];
        $this->loadedStatus = [];

        cache()->deleteByTag("module.{$moduleName}");

        try {
            $langDir = $this->getModulePath('Resources/lang');
            if (is_dir($langDir)) {
                $finder = finder();
                $finder->files()->in($langDir)->name('*.php');
                $locales = [];
                foreach ($finder as $file) {
                    $relativePath = trim($file->getRelativePath(), DIRECTORY_SEPARATOR);
                    if ($relativePath !== '') {
                        $locales[$relativePath] = true;
                    }
                }
                foreach (array_keys($locales) as $locale) {
                    translation()->flushLocaleCache($locale);
                }
            }
        } catch (Throwable $e) {
        }
    }

    /**
     * @return SplFileInfo[]
     */
    public function refreshDirectoryCache(string $directory): array
    {
        $moduleName = $this->getModuleName();
        $cacheKey = "module.{$moduleName}.files." . md5($directory);
        cache()->delete($cacheKey);

        unset($this->directoryCache[md5($directory)]);

        return $this->getFilesFromDirectory($directory);
    }
}
