<?php

namespace Flute\Core\Cache;

use Flute\Core\Cache\Adapters\ApcuAdapterCacheDriver;
use Flute\Core\Cache\Adapters\ArrayAdapterCacheDriver;
use Flute\Core\Cache\Adapters\FileSystemAdapterCacheDriver;
use Flute\Core\Cache\Adapters\MemcachedAdapterCacheDriver;
use Flute\Core\Cache\Adapters\RedisAdapterCacheDriver;

/**
 * Класс для создания экземпляров классов драйверов кэша на основе конфигурации
 */
class CacheManager
{
    protected AbstractCacheDriver $adapter;
    
    /**
     * Создает экземпляр класса драйвера кэша на основе указанного в конфигурации драйвера
     *
     * @param array $config
     * @return AbstractCacheDriver
     */
    public function create(array $config) : AbstractCacheDriver
    {
        $driver = $config['driver'] ?? 'array';

        // Выбираем драйвер кэша в зависимости от настроек
        switch ($driver) {
            case 'file':
                $adapter = new FileSystemAdapterCacheDriver($config);
            break;
            case 'memcached':
                $adapter = new MemcachedAdapterCacheDriver($config);
            break;
            case 'redis':
                $adapter = new RedisAdapterCacheDriver($config);
            break;
            case 'apcu':
                $adapter = new ApcuAdapterCacheDriver($config);
            break;
            case 'array':
            default:
                $adapter = new ArrayAdapterCacheDriver($config);
        }

        $this->adapter = $adapter;
        
        return $adapter;
    }

    public function getAdapter() : AbstractCacheDriver
    {
        return $this->adapter;
    }
}
