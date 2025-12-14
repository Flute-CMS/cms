<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Flute\Core\Cache\SWRQueue;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Throwable;

class FileSystemAdapterCacheDriver extends AbstractCacheDriver
{
    protected ?FilesystemAdapter $staleCache = null;

    protected int $staleTtl = 86400;

    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger);

        $namespace = $config['namespace'] ?? '';
        $defaultLifetime = (int) ($config['defaultLifetime'] ?? 0);
        $directory = (string) ($config['directory'] ?? '');
        $staleDirectory = (string) ($config['stale_directory'] ?? '');

        $this->cache = new FilesystemAdapter($namespace, $defaultLifetime, $directory);

        if ($staleDirectory !== '') {
            if (!is_dir($staleDirectory)) {
                @mkdir($staleDirectory, 0o755, true);
            }

            $this->staleCache = new FilesystemAdapter($namespace, $defaultLifetime, $staleDirectory);
        }

        $staleTtl = (int) ($config['stale_ttl'] ?? 0);
        if ($staleTtl > 0) {
            $this->staleTtl = $staleTtl;
        }
    }

    public function get(string $key, $default = null)
    {
        $item = $this->cache->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        if ($this->staleCache) {
            $staleItem = $this->staleCache->getItem($key);
            if ($staleItem->isHit()) {
                return $staleItem->get();
            }
        }

        return $default;
    }

    public function set(string $key, $value, int $ttl = 0): bool
    {
        $result = parent::set($key, $value, $ttl);

        $this->saveToStale($key, $value, $ttl);

        return $result;
    }

    public function delete(string $key): bool
    {
        if ($this->staleCache) {
            try {
                $item = $this->cache->getItem($key);
                if ($item->isHit()) {
                    $this->saveToStale($key, $item->get(), 0);
                }
            } catch (Throwable) {
            }
        }

        return parent::delete($key);
    }

    public function callback(string $key, callable $callback, int $ttl = 0)
    {
        $item = $this->cache->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        $hasStale = false;
        $staleValue = null;

        if ($this->staleCache) {
            $staleItem = $this->staleCache->getItem($key);
            if ($staleItem->isHit()) {
                $hasStale = true;
                $staleValue = $staleItem->get();
            }
        }

        if ($hasStale && function_exists('is_debug') && !is_debug()) {
            SWRQueue::queue('cache.revalidate.' . md5($key), function () use ($key, $callback, $ttl): void {
                $this->revalidate($key, $callback, $ttl);
            });

            return $staleValue;
        }

        $value = parent::callback($key, $callback, $ttl);

        $this->saveToStale($key, $value, $ttl);

        return $value;
    }

    protected function revalidate(string $key, callable $callback, int $ttl = 0): void
    {
        try {
            $item = $this->cache->getItem($key);
            if ($item->isHit()) {
                return;
            }

            $value = parent::callback($key, $callback, $ttl);
            $this->saveToStale($key, $value, $ttl);
        } catch (Throwable $e) {
            if (function_exists('logs')) {
                logs()->warning($e);
            }
        }
    }

    protected function saveToStale(string $key, $value, int $ttl = 0): void
    {
        if (!$this->staleCache) {
            return;
        }

        $item = $this->staleCache->getItem($key);
        $item->set($value);
        $item->expiresAfter($this->resolveStaleTtl($ttl));

        if (!$item instanceof CacheItemInterface) {
            return;
        }

        try {
            $this->staleCache->save($item);
        } catch (Throwable) {
        }
    }

    protected function resolveStaleTtl(int $ttl): int
    {
        if ($ttl <= 0) {
            return $this->staleTtl;
        }

        return max($ttl, $this->staleTtl);
    }
}
