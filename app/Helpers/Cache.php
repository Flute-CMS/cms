<?php

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Cache\AbstractCacheDriver;
use Flute\Core\Cache\CacheManager;
use Flute\Core\Services\CacheWarmupService;

if (!function_exists("cache")) {
    /**
     * Get the cache instance
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @return AbstractCacheDriver|mixed
     */
    function cache(?string $key = null)
    {
        /** @var AbstractCacheDriver $instance */
        static $instance = null;
        static $epoch = null;

        $currentEpoch = $GLOBALS['flute_cache_epoch'] ?? null;
        if (!is_int($currentEpoch)) {
            $epochFile = function_exists('storage_path')
                ? storage_path('app/cache_epoch')
                : (defined('BASE_PATH') ? BASE_PATH . 'storage/app/cache_epoch' : 'cache_epoch');

            $currentEpoch = 0;
            $epochContent = @file_get_contents($epochFile);
            if (is_string($epochContent) && $epochContent !== '') {
                $currentEpoch = (int) trim($epochContent);
            }

            $GLOBALS['flute_cache_epoch'] = $currentEpoch;
        }

        if ($instance === null || $epoch !== $currentEpoch) {
            $cacheManager = app(CacheManager::class);
            try {
                $cacheManager->create((array) config('cache'));
            } catch (Throwable) {
            }

            $instance = $cacheManager->getAdapter();
            $epoch = $currentEpoch;
        }

        return $key ?
            $instance->get($key) :
            $instance;
    }
}

if (!function_exists('cache_bump_epoch')) {
    function cache_bump_epoch(): int
    {
        $epochFile = function_exists('storage_path')
            ? storage_path('app/cache_epoch')
            : (defined('BASE_PATH') ? BASE_PATH . 'storage/app/cache_epoch' : 'cache_epoch');

        $currentEpoch = 0;
        $epochContent = @file_get_contents($epochFile);
        if (is_string($epochContent) && $epochContent !== '') {
            $currentEpoch = (int) trim($epochContent);
        }

        $next = $currentEpoch + 1;
        @file_put_contents($epochFile, (string) $next, LOCK_EX);
        $GLOBALS['flute_cache_epoch'] = $next;

        return $next;
    }
}

if (!function_exists('cache_warmup_mark')) {
    function cache_warmup_mark(): void
    {
        if (function_exists('app') && app()->has(CacheWarmupService::class)) {
            app(CacheWarmupService::class)->markNeeded();

            return;
        }

        $path = function_exists('storage_path')
            ? storage_path('app/cache_warmup_needed')
            : (defined('BASE_PATH') ? BASE_PATH . 'storage/app/cache_warmup_needed' : 'cache_warmup_needed');

        @mkdir(dirname($path), 0o755, true);
        @file_put_contents($path, (string) time(), LOCK_EX);
    }
}
