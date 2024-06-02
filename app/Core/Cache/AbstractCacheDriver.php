<?php

namespace Flute\Core\Cache;

use Flute\Core\Contracts\CacheInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Абстрактный класс, описывающий базовый функционал для драйверов кэша
 */
abstract class AbstractCacheDriver implements CacheInterface
{
    /**
     * Массив конфигурации кэша
     *
     * @var array
     */
    protected array $config;

    /**
     * Экземпляр адаптера кэша
     *
     * @var AdapterInterface
     */
    protected AdapterInterface $cache;

    /**
     * Конструктор класса, инициализирует массив конфигурации
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Получить instance у адаптера кэша
     * 
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->cache;
    }

    /**
     * Получает значение из кэша по ключу
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get(string $key, $default = null)
    {
        $item = $this->cache->getItem($key);

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * Устанавливает значение в кэше по ключу
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     * @throws InvalidArgumentException
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        $item = $this->cache->getItem($key);

        $item->set($value);
        $item->expiresAfter($ttl);
        $result = $this->cache->save($item);

        if (!$result) {
            logs()->error("ERROR SAVE CACHE - $key");
        }

        return $result;
    }

    /**
     * Удаляет значение из кэша по ключу
     *
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        return $this->cache->deleteItem($key);
    }

    /**
     * Очищает весь кэш
     *
     * @return bool
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Фиксирует изменения в кэше
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->cache->commit();
    }

    /**
     * Проверяет, есть ли элемент в кеше по ключу
     *
     * @param string $key
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function has(string $key): bool
    {
        $item = $this->cache->getItem($key);
        return $item->isHit();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function callback(string $key, callable $callback, $ttl = null)
    {
        // Проверяем, есть ли уже кэш для этого ключа
        $item = $this->cache->getItem($key)->expiresAfter($ttl);

        if (!$item->isHit()) {
            $value = $callback($item);
            $item->set($value);
            $this->cache->save($item);
        } else {
            $value = $item->get();
        }

        // Возвращаем значение из кэша или из каллбэка
        return $value;
    }

}