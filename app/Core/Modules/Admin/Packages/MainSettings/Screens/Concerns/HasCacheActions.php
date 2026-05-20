<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

trait HasCacheActions
{
    public function clearCache()
    {
        $cacheDir = storage_path('app/cache');
        $cacheStaleDir = storage_path('app/cache_stale');
        $cssCacheDir = public_path('assets/css/cache');
        $cssCacheStaleDir = public_path('assets/css/cache_stale');
        $jsCacheDir = public_path('assets/js/cache');
        $jsCacheStaleDir = public_path('assets/js/cache_stale');

        $full = (bool) request()->input('full', false);

        $cachePaths = [
            storage_path('app/views/*'),
            storage_path('app/translations/*'),
            storage_path('app/proxies/*'),
        ];

        if (!is_performance() || $full) {
            $cachePaths[] = storage_path('logs/*');
        }

        try {
            $filesystem = fs();

            \Flute\Core\Cache\SWRQueue::flush();

            if (function_exists('cache_bump_epoch')) {
                cache_bump_epoch();
            }
            if (function_exists('cache_warmup_mark')) {
                cache_warmup_mark();
            }

            $this->forceRemoveDir($filesystem, $cacheStaleDir);
            $this->forceRemoveDir($filesystem, $cacheDir);
            @mkdir($cacheDir, 0o755, true);
            @mkdir($cacheStaleDir, 0o755, true);

            $this->forceRemoveDir($filesystem, $cssCacheStaleDir);
            $this->forceRemoveDir($filesystem, $cssCacheDir);
            @mkdir($cssCacheDir, 0o755, true);

            $this->forceRemoveDir($filesystem, $jsCacheStaleDir);
            $this->forceRemoveDir($filesystem, $jsCacheDir);
            @mkdir($jsCacheDir, 0o755, true);

            foreach ($cachePaths as $path) {
                $files = glob($path);
                if ($files) {
                    foreach ($files as $file) {
                        $this->forceRemoveDir($filesystem, $file);
                    }
                }
            }

            $this->clearOpcache();

            \Flute\Core\Cache\OrphanSweeper::sweep(storage_path('app'), 3600);
            \Flute\Core\Cache\OrphanSweeper::sweep(public_path('assets/css'), 3600);
            \Flute\Core\Cache\OrphanSweeper::sweep(public_path('assets/js'), 3600);

            $this->flashMessage(__('admin-main-settings.messages.cache_cleared_successfully'));
        } catch (IOException $e) {
            logs()->warning($e);
            $this->flashMessage(
                __('admin-main-settings.messages.cache_cleared_successfully') . ' (' . $e->getMessage() . ')',
                'warning',
            );
        }
    }

    protected function invalidateConfig(string $configName): void
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(path('config/' . $configName . '.php'), true);
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    protected function forceRemoveDir(Filesystem $filesystem, string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        try {
            $filesystem->remove($path);
        } catch (IOException) {
            $this->chmodRecursive($path);
            $filesystem->remove($path);
        }
    }

    protected function chmodRecursive(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @chmod($path, 0o666);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        @chmod($path, 0o777);

        $items = @scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $this->chmodRecursive($path . '/' . $item);
        }
    }

    /**
     * Invalidate caches that depend on main settings (layout, navbar, footer, etc.).
     */
    protected function invalidateSettingsCache(): void
    {
        try {
            cache()->deleteImmediately('flute.global.layout');
            cache()->deleteByTag(\Flute\Core\Services\NavbarService::CACHE_TAG);
            cache()->deleteByTag(\Flute\Core\Services\FooterService::CACHE_TAG);
        } catch (Throwable) {
            // Do not break admin flow if cache clearing fails
        }
    }
}
