<?php

declare(strict_types = 1);

namespace Flute\Core\ModulesManager\Actions\Concerns;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Flushes compiled Symfony translation catalogues, translation discovery
 * caches and compiled asset caches so that newly installed/updated/removed
 * module translations and styles take effect immediately.
 */
trait FlushesTranslationCache
{
    protected function flushCompiledTranslations(): void
    {
        $dir = storage_path('app/translations');

        if (is_dir($dir)) {
            $files = glob($dir . '/catalogue.*.php*');

            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }

        \Flute\Core\Cache\SWRQueue::flush();

        if (function_exists('cache_bump_epoch')) {
            cache_bump_epoch();
        }

        if (function_exists('cache')) {
            try {
                $cache = cache();

                $cache->deleteImmediately('translation.known_dirs.v2');
                $cache->deleteImmediately('translation.core.files');

                $availableLangs = (array) config('lang.available', []);
                foreach ($availableLangs as $locale) {
                    $cache->deleteImmediately('translation.core.files.' . $locale);
                }
            } catch (\Throwable) { // @mago-expect no-empty-catch-clause
            }
        }

        $this->flushAssetCaches();

        if (function_exists('cache_warmup_mark')) {
            cache_warmup_mark();
        }
    }

    protected function flushAssetCaches(): void
    {
        $filesystem = new Filesystem();

        $dirs = [
            public_path('assets/css/cache'),
            public_path('assets/css/cache_stale'),
            public_path('assets/js/cache'),
            public_path('assets/js/cache_stale'),
        ];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $filesystem->remove($dir);
                @mkdir($dir, 0o775, true);
            }
        }

        $viewFiles = glob(storage_path('app/views/*'));
        if ($viewFiles) {
            $filesystem->remove($viewFiles);
        }
    }
}
