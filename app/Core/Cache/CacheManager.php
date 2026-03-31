<?php

namespace Flute\Core\Cache;

use Flute\Core\Cache\Adapters\ApcuAdapterCacheDriver;
use Flute\Core\Cache\Adapters\ArrayAdapterCacheDriver;
use Flute\Core\Cache\Adapters\FileSystemAdapterCacheDriver;
use Flute\Core\Cache\Adapters\MemcachedAdapterCacheDriver;
use Flute\Core\Cache\Adapters\RedisAdapterCacheDriver;
use Psr\Log\LoggerInterface;
use RuntimeException;

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
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Creates a cache driver instance based on the driver specified in the configuration
     */
    public function create(array $config): AbstractCacheDriver
    {
        $config = $this->applyEpochNamespace($config);

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
     * @throws RuntimeException
     */
    public function getAdapter(): AbstractCacheDriver
    {
        if ($this->adapter === null) {
            throw new RuntimeException('Cache adapter has not been created yet.');
        }

        return $this->adapter;
    }

    private function applyEpochNamespace(array $config): array
    {
        $epoch = $this->readEpoch();

        $base = (string) ( $config['namespace'] ?? '' );
        $base = preg_replace('/\\.e\\d+$/', '', $base) ?: '';
        $base = trim((string) $base, '.');

        $config['namespace'] = $base === '' ? 'e' . $epoch : $base . '.e' . $epoch;

        return $config;
    }

    private static ?int $epochCache = null;

    private function readEpoch(): int
    {
        if (isset($GLOBALS['flute_cache_epoch']) && is_int($GLOBALS['flute_cache_epoch'])) {
            self::$epochCache = $GLOBALS['flute_cache_epoch'];

            return self::$epochCache;
        }

        if (self::$epochCache !== null) {
            return self::$epochCache;
        }

        $epochFile = defined('BASE_PATH') ? BASE_PATH . 'storage/app/cache_epoch' : 'cache_epoch';

        $content = @file_get_contents($epochFile);
        if (!is_string($content) || $content === '') {
            self::$epochCache = 0;

            return 0;
        }

        self::$epochCache = (int) trim($content);

        return self::$epochCache;
    }
}
