<?php

namespace Flute\Core\Cache;

use Flute\Core\Cache\Contracts\CacheInterface;
use Flute\Core\Services\FileLockService;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Throwable;

/**
 * Abstract class that describes the basic functionality for cache drivers.
 *
 * Includes built-in SWR (stale-while-revalidate) support when $staleCache
 * is configured by a concrete driver. Stale data is served immediately
 * while fresh data is recomputed in the background after the response is sent.
 */
abstract class AbstractCacheDriver implements CacheInterface
{
    protected array $config;

    protected AdapterInterface $cache;

    /**
     * Optional secondary cache adapter for stale data (SWR).
     * Concrete drivers set this in their constructors.
     */
    protected ?AdapterInterface $staleCache = null;

    /**
     * TTL for stale cache entries (default 24 hours).
     */
    protected int $staleTtl = 86400;

    protected LoggerInterface $logger;

    protected array $memoryCache = [];

    protected int $memoryCacheMaxSize = 512;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->cache;
    }

    /**
     * @param mixed $default
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (array_key_exists($key, $this->memoryCache)) {
            return $this->memoryCache[$key];
        }

        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            $value = $item->get();
            $this->memoryCacheStore($key, $value);

            return $value;
        }

        return $default;
    }

    /**
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function set(string $key, $value, int $ttl = 0): bool
    {
        $this->memoryCacheStore($key, $value);

        $item = $this->cache->getItem($key);

        $item->set($value);
        if ($ttl > 0) {
            $item->expiresAfter($ttl);
        }

        $result = $this->cache->save($item);

        if (!$result) {
            $adapter = is_object($this->cache) ? get_class($this->cache) : gettype($this->cache);
            $type = is_object($value) ? $value::class : gettype($value);
            $sizeHint = null;

            try {
                $sizeHint = is_string($value) ? strlen($value) : ( is_array($value) ? count($value) : null );
            } catch (Throwable) {
            }
            $this->logger->error("Failed to save cache for key: {$key}", [
                'adapter' => $adapter,
                'ttl' => $ttl,
                'value_type' => $type,
                'value_size_hint' => $sizeHint,
            ]);
        }

        $this->saveToStale($key, $value, $ttl);

        return $result;
    }

    /**
     * Delete value from main cache. If stale cache is configured, the current
     * value is preserved in stale for SWR revalidation.
     *
     * @throws InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        unset($this->memoryCache[$key]);

        if ($this->staleCache) {
            try {
                $item = $this->cache->getItem($key);
                if ($item->isHit()) {
                    $this->saveToStale($key, $item->get(), 0);
                }
            } catch (Throwable) {
            }
        }

        return $this->cache->deleteItem($key);
    }

    /**
     * Delete a cache key from both main and stale caches immediately.
     * Unlike delete(), this does NOT preserve the value in stale cache.
     * Use this when admin explicitly changes data and must see fresh results.
     *
     * @throws InvalidArgumentException
     */
    public function deleteImmediately(string $key): bool
    {
        unset($this->memoryCache[$key]);

        if ($this->staleCache) {
            try {
                $this->staleCache->deleteItem($key);
            } catch (Throwable) {
            }
        }

        return $this->cache->deleteItem($key);
    }

    /**
     * Clear all cache. Flushes SWR queue and clears stale cache too.
     */
    public function clear(): bool
    {
        $this->memoryCache = [];

        SWRQueue::flush();

        if ($this->staleCache) {
            try {
                $this->staleCache->clear();
            } catch (Throwable) {
            }
        }

        return $this->cache->clear();
    }

    public function commit(): bool
    {
        return $this->cache->commit();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->memoryCache)) {
            return true;
        }

        $item = $this->cache->getItem($key);

        return $item->isHit();
    }

    /**
     * Execute callback and save result in cache if key doesn't exist.
     * Uses file locking to prevent cache stampede (thundering herd).
     * When stale cache is available and app is in production mode,
     * serves stale data immediately and queues background revalidation.
     *
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function callback(string $key, callable $callback, int $ttl = 0)
    {
        if (array_key_exists($key, $this->memoryCache)) {
            return $this->memoryCache[$key];
        }

        $item = $this->cache->getItem($key);
        if ($item->isHit()) {
            $value = $item->get();
            $this->memoryCacheStore($key, $value);

            return $value;
        }

        if ($this->staleCache) {
            $staleItem = $this->staleCache->getItem($key);
            if ($staleItem->isHit() && function_exists('is_debug') && !is_debug()) {
                $value = $staleItem->get();
                $this->memoryCacheStore($key, $value);

                SWRQueue::queue('cache.revalidate.' . md5($key), function () use ($key, $callback, $ttl): void {
                    $this->revalidate($key, $callback, $ttl);
                });

                return $value;
            }
        }

        $lockDir = function_exists('path') ? path('storage/app/cache/locks') : 'storage/app/cache/locks';
        $lockFile = $lockDir . '/' . md5($key) . '.lock';

        $lockHandle = FileLockService::acquireLock($lockFile);

        if ($lockHandle !== false) {
            try {
                $item = $this->cache->getItem($key);
                if ($item->isHit()) {
                    $value = $item->get();
                    $this->memoryCacheStore($key, $value);

                    return $value;
                }

                try {
                    $value = $callback();
                } catch (\Throwable $e) {
                    if ($this->staleCache) {
                        $staleItem = $this->staleCache->getItem($key);
                        if ($staleItem->isHit()) {
                            $value = $staleItem->get();
                            $this->memoryCacheStore($key, $value);

                            return $value;
                        }
                    }

                    throw $e;
                }

                $item->set($value);
                if ($ttl > 0) {
                    $item->expiresAfter($ttl);
                }

                $this->cache->save($item);
                $this->saveToStale($key, $value, $ttl);
                $this->memoryCacheStore($key, $value);

                return $value;
            } finally {
                FileLockService::releaseLock($lockHandle);
            }
        }

        $item = $this->cache->getItem($key);
        if ($item->isHit()) {
            $value = $item->get();
            $this->memoryCacheStore($key, $value);

            return $value;
        }

        try {
            $value = $callback();
        } catch (\Throwable $e) {
            if ($this->staleCache) {
                $staleItem = $this->staleCache->getItem($key);
                if ($staleItem->isHit()) {
                    $value = $staleItem->get();
                    $this->memoryCacheStore($key, $value);

                    return $value;
                }
            }

            throw $e;
        }

        $this->memoryCacheStore($key, $value);

        return $value;
    }

    protected array $tagMemory = [];

    /**
     * Register a cache key under a tag for grouped invalidation.
     * Uses file locking for atomic read-modify-write.
     * Tag registry has a 7-day TTL to prevent unbounded growth.
     */
    public function tagKey(string $tag, string $key): void
    {
        $memKey = $tag . '|' . $key;
        if (isset($this->tagMemory[$memKey])) {
            return;
        }
        $this->tagMemory[$memKey] = true;

        $registryKey = '_tag_registry.' . $tag;

        $doRegister = function () use ($registryKey, $key): void {
            $item = $this->cache->getItem($registryKey);
            $keys = $item->isHit() ? (array) $item->get() : [];

            if (!in_array($key, $keys, true)) {
                $keys[] = $key;
                $item->set($keys);
                $item->expiresAfter(604800);
                $this->cache->save($item);
            }
        };

        $lockDir = function_exists('path') ? path('storage/app/cache/locks') : 'storage/app/cache/locks';
        $lockFile = $lockDir . '/tag_' . md5($tag) . '.lock';

        try {
            FileLockService::withLockOrFallback($lockFile, $doRegister, $doRegister, 2.0);
        } catch (Throwable $e) {
            $this->logger->warning("Failed to register cache key '{$key}' under tag '{$tag}': " . $e->getMessage());
        }
    }

    /**
     * Delete all cache keys registered under the given tag and the registry itself.
     * Uses deleteImmediately() so stale cache is also cleared.
     */
    public function deleteByTag(string $tag): void
    {
        $registryKey = '_tag_registry.' . $tag;

        try {
            $item = $this->cache->getItem($registryKey);

            if ($item->isHit()) {
                $keys = (array) $item->get();

                foreach ($keys as $cachedKey) {
                    $this->deleteImmediately($cachedKey);
                }
            }

            // Delete registry from both main and stale.
            $this->cache->deleteItem($registryKey);
            if ($this->staleCache) {
                $this->staleCache->deleteItem($registryKey);
            }
        } catch (Throwable $e) {
            $this->logger->warning("Failed to delete cache by tag '{$tag}': " . $e->getMessage());
        }
    }

    /**
     * @return array Array of matching keys
     */
    public function getKeys(string $pattern): array
    {
        try {
            $this->logger->debug("Getting cache keys matching pattern: {$pattern}");

            if ($this->cache instanceof \Symfony\Component\Cache\Adapter\FilesystemAdapter) {
                return $this->getKeysFromFilesystem($pattern);
            }

            if ($this->cache instanceof \Symfony\Component\Cache\Adapter\RedisAdapter) {
                return $this->getKeysFromRedis($pattern);
            }

            return $this->getKeysGeneric($pattern);
        } catch (Throwable $e) {
            $this->logger->error('Error getting cache keys: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Revalidate a cache key in the background (called by SWR queue).
     */
    protected function revalidate(string $key, callable $callback, int $ttl = 0): void
    {
        try {
            $item = $this->cache->getItem($key);
            if ($item->isHit()) {
                return;
            }

            $value = $callback();

            $item->set($value);
            if ($ttl > 0) {
                $item->expiresAfter($ttl);
            }

            $this->cache->save($item);
            $this->saveToStale($key, $value, $ttl);
        } catch (Throwable $e) {
            if (function_exists('logs')) {
                logs()->warning($e);
            }
        }
    }

    /**
     * Save a value to the stale cache (if configured).
     */
    protected function saveToStale(string $key, $value, int $ttl = 0): void
    {
        if (!$this->staleCache) {
            return;
        }

        try {
            $item = $this->staleCache->getItem($key);
            $item->set($value);
            $item->expiresAfter($this->resolveStaleTtl($ttl));
            $this->staleCache->save($item);
        } catch (Throwable) {
        }
    }

    protected function memoryCacheStore(string $key, $value): void
    {
        if (count($this->memoryCache) >= $this->memoryCacheMaxSize) {
            array_shift($this->memoryCache);
        }
        $this->memoryCache[$key] = $value;
    }

    protected function resolveStaleTtl(int $ttl): int
    {
        if ($ttl <= 0) {
            return $this->staleTtl;
        }

        return max($ttl, $this->staleTtl);
    }

    /**
     * @return array Always returns empty array for filesystem adapter
     */
    protected function getKeysFromFilesystem(string $pattern): array
    {
        $this->logger->debug(
            'getKeys() is not supported for FilesystemAdapter (keys are hashed). '
            . "Pattern '{$pattern}' ignored. Use tagKey()/deleteByTag() for grouped cache invalidation.",
        );

        return [];
    }

    /**
     * Get keys from Redis adapter using SCAN (non-blocking).
     *
     * @return array Array of matching keys
     */
    protected function getKeysFromRedis(string $pattern): array
    {
        try {
            $reflectionClass = new ReflectionClass($this->cache);
            $redisProperty = $reflectionClass->getProperty('redis');
            $redisProperty->setAccessible(true);
            $redis = $redisProperty->getValue($this->cache);

            if (!is_object($redis)) {
                $this->logger->warning('Redis instance not found: ' . gettype($redis));

                return [];
            }

            // Prefer SCAN over KEYS to avoid blocking Redis.
            if (method_exists($redis, 'scan')) {
                $keys = [];
                $iterator = null;

                // phpredis: scan(&$iterator, $pattern, $count) returns array|false
                // Loop until iterator becomes 0 (scan complete).
                do {
                    $result = $redis->scan($iterator, $pattern, 100);
                    if (is_array($result)) {
                        $keys = array_merge($keys, $result);
                    }
                } while ($iterator !== 0 && $iterator !== null && $iterator !== false);

                $this->logger->debug('Found ' . count($keys) . " keys matching pattern: {$pattern} (via SCAN)");

                return $keys;
            }

            // Fallback to KEYS only if SCAN is unavailable (e.g., Predis without scan).
            if (method_exists($redis, 'keys')) {
                $keys = $redis->keys($pattern);
                $this->logger->debug('Found ' . count($keys) . " keys matching pattern: {$pattern} (via KEYS)");

                return is_array($keys) ? $keys : [];
            }

            $this->logger->warning('Redis instance does not support scan or keys method: ' . $redis::class);

            return [];
        } catch (Throwable $e) {
            $this->logger->error('Error accessing Redis instance: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @return array Array of matching keys
     */
    protected function getKeysGeneric(string $pattern): array
    {
        $this->logger->warning('Using generic key matching for adapter: ' . get_class($this->cache));

        if (strpos($pattern, 'module.') === 0 && strpos($pattern, '.files.') !== false) {
            $parts = explode('.', $pattern);
            if (count($parts) >= 3) {
                $moduleName = $parts[1];
                $this->logger->debug("Extracted module name from pattern: {$moduleName}");

                $this->logger->warning(
                    'Cannot get keys for adapter: '
                    . get_class($this->cache)
                    . '. Module cache clearing may be incomplete.',
                );

                return [];
            }
        }

        $this->logger->warning('Cannot get keys for adapter: ' . get_class($this->cache) . " with pattern: {$pattern}");

        return [];
    }
}
