<?php

use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Cache\AbstractCacheDriver;
use Flute\Core\Cache\CacheManager;

if (!function_exists("cache")) {
    /**
     * Get the cache instance
     *
     * @param string|null $key
     * @return AbstractCacheDriver|mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    function cache(string $key = null)
    {
        /** @var AbstractCacheDriver $instance */
        static $instance = null;

        if ($instance === null) {
            $instance = app(CacheManager::class)->getAdapter();
        }

        return $key ?
            $instance->get($key) :
            $instance;
    }
}