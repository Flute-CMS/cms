<?php

namespace Flute\Core\Support\Concerns;

use SplFileInfo;

trait HandlesModulePaths
{
    protected function getModulePath(string $path = ''): string
    {
        $cacheKey = $this->getModuleName() . ( $path ? '_' . md5($path) : '' );

        if (isset($this->modulePathCache[$cacheKey])) {
            return $this->modulePathCache[$cacheKey];
        }

        $result = path("app/Modules/{$this->getModuleName()}") . ( $path ? '/' . $path : '' );
        $this->modulePathCache[$cacheKey] = $result;

        return $result;
    }

    protected function kebabCase(string $string): string
    {
        $string = preg_replace('/Component$/', '', $string);

        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }

    /**
     * @return SplFileInfo[]
     */
    protected function getFilesFromDirectory(string $directory, ?int $cacheDuration = null): array
    {
        $dirHash = md5($directory);

        if (isset($this->directoryCache[$dirHash])) {
            return $this->directoryCache[$dirHash];
        }

        $moduleName = $this->getModuleName();
        $cacheKey = "module.{$moduleName}.files." . $dirHash;
        $duration = $cacheDuration ?? $this->defaultCacheDuration;

        cache()->tagKey("module.{$moduleName}", $cacheKey);

        $filePaths = cache()->callback(
            $cacheKey,
            static function () use ($directory) {
                if (!is_dir($directory)) {
                    return [];
                }

                $paths = [];
                $finder = finder()->files()->in($directory);

                foreach ($finder as $file) {
                    $paths[] = $file->getPathname();
                }

                return $paths;
            },
            $duration,
        );

        $result = array_map(static fn($path) => new SplFileInfo($path), $filePaths);

        $this->directoryCache[$dirHash] = $result;

        return $result;
    }
}
