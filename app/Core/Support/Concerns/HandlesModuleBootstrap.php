<?php

namespace Flute\Core\Support\Concerns;

use Exception;

trait HandlesModuleBootstrap
{
    /**
     * @throws Exception
     */
    public function bootstrapModule(): void
    {
        $bootstrapCacheKey = "module.{$this->getModuleName()}.bootstrapped";
        if (isset($this->loadedStatus[$bootstrapCacheKey])) {
            return;
        }

        try {
            $manifest = $this->getModuleManifest();

            if ($manifest['hasEntities']) {
                $this->loadEntities();
            }
            if ($manifest['hasConfigs']) {
                $this->loadConfigs();
            }
            if ($manifest['hasTranslations']) {
                $this->loadTranslations();
            }
            if ($manifest['hasViewPages']) {
                $this->loadViewPages();
            }
            if ($manifest['hasControllers']) {
                $this->loadRouterAttributes();
            }
            if ($manifest['hasComponents']) {
                $this->loadComponents();
            }
            if ($manifest['hasWidgets']) {
                $this->loadWidgets();
            }

            $this->loadedStatus[$bootstrapCacheKey] = true;
        } catch (\Throwable $e) {
            logs('modules')->error("Error bootstrapping module {$this->getModuleName()}: " . $e->getMessage());

            if (is_debug()) {
                throw $e;
            }
        }
    }

    protected function getModuleManifest(): array
    {
        $base = $this->getModulePath();
        if (!is_dir($base)) {
            return [
                'hasEntities' => false,
                'hasConfigs' => false,
                'hasTranslations' => false,
                'hasViewPages' => false,
                'hasControllers' => false,
                'hasComponents' => false,
                'hasWidgets' => false,
            ];
        }

        if (is_debug()) {
            return [
                'hasEntities' => is_dir($base . '/database/Entities'),
                'hasConfigs' => is_dir($base . '/Resources/config'),
                'hasTranslations' => is_dir($base . '/Resources/lang'),
                'hasViewPages' => is_dir($base . '/Resources/pages'),
                'hasControllers' => is_dir($base . '/Http/Controllers') || is_dir($base . '/Controllers'),
                'hasComponents' => is_dir($base . '/Components'),
                'hasWidgets' => is_dir($base . '/Widgets'),
            ];
        }

        $cacheKey = "module.{$this->getModuleName()}.manifest.v2";
        cache()->tagKey("module.{$this->getModuleName()}", $cacheKey);

        $manifest = cache()->callback(
            $cacheKey,
            static function () use ($base) {
                return [
                    'hasEntities' => is_dir($base . '/database/Entities'),
                    'hasConfigs' => is_dir($base . '/Resources/config'),
                    'hasTranslations' => is_dir($base . '/Resources/lang'),
                    'hasViewPages' => is_dir($base . '/Resources/pages'),
                    'hasControllers' => is_dir($base . '/Http/Controllers') || is_dir($base . '/Controllers'),
                    'hasComponents' => is_dir($base . '/Components'),
                    'hasWidgets' => is_dir($base . '/Widgets'),
                    '_v' => 2,
                ];
            },
            $this->defaultCacheDuration,
        );

        if (!isset($manifest['_v']) || $manifest['_v'] < 2) {
            cache()->delete($cacheKey);

            return [
                'hasEntities' => true,
                'hasConfigs' => true,
                'hasTranslations' => true,
                'hasViewPages' => true,
                'hasControllers' => true,
                'hasComponents' => true,
                'hasWidgets' => true,
            ];
        }

        return $manifest;
    }

    public function register(\DI\Container $container): void
    {
    }

    public function boot(\DI\Container $container): void
    {
        $this->bootstrapModule();
    }
}
