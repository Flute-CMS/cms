<?php

namespace Flute\Core\Cache;

use Exception;
use Flute\Core\Cache\Contracts\CacheInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Throwable;

/**
 * Abstract class that describes the basic functionality for cache drivers
 */
abstract class AbstractCacheDriver implements CacheInterface
{
    /**
     * Cache configuration array
     */
    protected array $config;

    /**
     * Cache adapter instance
     */
    protected AdapterInterface $cache;

    /**
     * Logger instance
     */
    protected LoggerInterface $logger;

    /**
     * Class constructor, initializes configuration array and logger
     */
    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Get cache adapter instance
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->cache;
    }

    /**
     * Get value from cache by key
     *
     * @param mixed $default
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $item = $this->cache->getItem($key);

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * Set value in cache by key
     *
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function set(string $key, $value, int $ttl = 0): bool
    {
        $item = $this->cache->getItem($key);

        $item->set($value);
        if ($ttl > 0) {
            $item->expiresAfter($ttl);
        }

        if (!$item instanceof CacheItemInterface) {
            return false;
        }

        $result = $this->cache->save($item);

        if (!$result) {
            $adapter = is_object($this->cache) ? get_class($this->cache) : gettype($this->cache);
            $type = is_object($value) ? $value::class : gettype($value);
            $sizeHint = null;

            try {
                $sizeHint = is_string($value) ? strlen($value) : (is_array($value) ? count($value) : null);
            } catch (Throwable) {
            }
            $this->logger->error("Failed to save cache for key: {$key}", [
                'adapter' => $adapter,
                'ttl' => $ttl,
                'value_type' => $type,
                'value_size_hint' => $sizeHint,
            ]);
        }

        return $result;
    }

    /**
     * Delete value from cache by key
     *
     * @throws InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        return $this->cache->deleteItem($key);
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Commit changes in cache
     */
    public function commit(): bool
    {
        return $this->cache->commit();
    }

    /**
     * Check if item exists in cache by key
     *
     * @throws InvalidArgumentException
     */
    public function has(string $key): bool
    {
        $item = $this->cache->getItem($key);

        return $item->isHit();
    }

    /**
     * Execute callback and save result in cache if key doesn't exist.
     * Uses file locking to prevent cache stampede (thundering herd).
     *
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function callback(string $key, callable $callback, int $ttl = 0)
    {
        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        $lockDir = path('storage/app/cache/locks');
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0o755, true);
        }

        $lockFile = $lockDir . '/' . md5($key) . '.lock';
        $lockHandle = @fopen($lockFile, 'w+');

        if ($lockHandle && flock($lockHandle, LOCK_EX | LOCK_NB)) {
            try {
                $item = $this->cache->getItem($key);
                if ($item->isHit()) {
                    return $item->get();
                }

                $value = $callback();

                $item->set($value);
                if ($ttl > 0) {
                    $item->expiresAfter($ttl);
                }

                $saveResult = $this->cache->save($item);

                if (!$saveResult) {
                    $adapter = is_object($this->cache) ? get_class($this->cache) : gettype($this->cache);
                    $type = is_object($value) ? $value::class : gettype($value);
                    $sizeHint = null;

                    try {
                        $sizeHint = is_string($value) ? strlen($value) : (is_array($value) ? count($value) : null);
                    } catch (Throwable) {
                    }
                    $this->logger->error("Failed to save cache for key: {$key}", [
                        'adapter' => $adapter,
                        'ttl' => $ttl,
                        'value_type' => $type,
                        'value_size_hint' => $sizeHint,
                    ]);
                }

                return $value;
            } finally {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                @unlink($lockFile);
            }
        } elseif ($lockHandle) {
            flock($lockHandle, LOCK_SH);
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);

            $item = $this->cache->getItem($key);
            if ($item->isHit()) {
                return $item->get();
            }

            return $callback();
        }

        return $callback();
    }

    /**
     * Get all cache keys matching a pattern
     *
     * @param string $pattern Pattern to match keys against
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
        } catch (Exception $e) {
            $this->logger->error("Error getting cache keys: " . $e->getMessage());

            return [];
        }
    }

    /**
     * Get keys from filesystem adapter
     *
     * @param string $pattern Pattern to match keys against
     * @return array Array of matching keys
     */
    protected function getKeysFromFilesystem(string $pattern): array
    {
        $keys = [];
        $cacheDir = $this->config['directory'] ?? storage_path('cache/symfony');

        if (!is_dir($cacheDir)) {
            return [];
        }

        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($cacheDir)->name('*.php');

        foreach ($finder as $file) {
            $key = str_replace('.php', '', $file->getFilename());
            if (fnmatch($pattern, $key)) {
                $keys[] = $key;
            }
        }

        $this->logger->debug("Found " . count($keys) . " keys matching pattern: {$pattern}");

        return $keys;
    }

    /**
     * Get keys from Redis adapter
     *
     * @param string $pattern Pattern to match keys against
     * @return array Array of matching keys
     */
    protected function getKeysFromRedis(string $pattern): array
    {
        try {
            $reflectionClass = new ReflectionClass($this->cache);
            $redisProperty = $reflectionClass->getProperty('redis');
            $redisProperty->setAccessible(true);
            $redis = $redisProperty->getValue($this->cache);

            if (is_object($redis) && method_exists($redis, 'keys')) {
                $keys = $redis->keys($pattern);
                $this->logger->debug("Found " . count($keys) . " keys matching pattern: {$pattern}");

                return $keys;
            }

            $this->logger->warning("Redis instance not found or does not support keys method: " . (is_object($redis) ? $redis::class : gettype($redis)));

            return [];
        } catch (Exception $e) {
            $this->logger->error("Error accessing Redis instance: " . $e->getMessage());

            return [];
        }
    }

    /**
     * Generic method to get keys for adapters without specific implementation
     *
     * @param string $pattern Pattern to match keys against
     * @return array Array of matching keys
     */
    protected function getKeysGeneric(string $pattern): array
    {
        $this->logger->warning("Using generic key matching for adapter: " . get_class($this->cache));

        if (strpos($pattern, 'module.') === 0 && strpos($pattern, '.files.') !== false) {
            $parts = explode('.', $pattern);
            if (count($parts) >= 3) {
                $moduleName = $parts[1];
                $this->logger->debug("Extracted module name from pattern: {$moduleName}");

                $this->logger->warning("Cannot get keys for adapter: " . get_class($this->cache) . ". Module cache clearing may be incomplete.");

                return [];
            }
        }

        $this->logger->warning("Cannot get keys for adapter: " . get_class($this->cache) . " with pattern: {$pattern}");

        return [];
    }
}
