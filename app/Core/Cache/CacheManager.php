<?php

namespace Flute\Core\Cache;

use Flute\Core\Cache\Adapters\ApcuAdapterCacheDriver;
use Flute\Core\Cache\Adapters\ArrayAdapterCacheDriver;
use Flute\Core\Cache\Adapters\FileSystemAdapterCacheDriver;
use Flute\Core\Cache\Adapters\MemcachedAdapterCacheDriver;
use Flute\Core\Cache\Adapters\RedisAdapterCacheDriver;
use Psr\Log\LoggerInterface;

/**
 * Class for creating cache driver instances based on configuration
 */
class CacheManager
{
    protected ?AbstractCacheDriver $adapter = null;
    protected LoggerInterface $logger;

    protected array $driverMap = [
        'file' => FileSystemAdapterCacheDriver::class,
        'memcached' => MemcachedAdapterCacheDriver::class,
        'redis' => RedisAdapterCacheDriver::class,
        'apcu' => ApcuAdapterCacheDriver::class,
        'array' => ArrayAdapterCacheDriver::class,
    ];

    /**
     * Class constructor, initializes logger
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Creates a cache driver instance based on the driver specified in the configuration
     *
     * @param array $config
     * @return AbstractCacheDriver
     */
    public function create(array $config): AbstractCacheDriver
    {
        $driver = $config['driver'] ?? 'array';

        if (!isset($this->driverMap[$driver])) {
            $this->logger->warning("Unsupported cache driver: {$driver}. Falling back to 'file' driver.");
            $driver = 'file';
        }

        $adapterClass = $this->driverMap[$driver];
        $this->adapter = new $adapterClass($config, $this->logger);

        return $this->adapter;
    }

    /**
     * Get current cache adapter
     *
     * @return AbstractCacheDriver
     * @throws \RuntimeException
     */
    public function getAdapter(): AbstractCacheDriver
    {
        if ($this->adapter === null) {
            throw new \RuntimeException("Cache adapter has not been created yet.");
        }

        return $this->adapter;
    }
}
