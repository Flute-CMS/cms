<?php

namespace Flute\Core\Cache\Contracts;

interface CacheInterface
{
    public function get(string $key, $default = null);

    public function set(string $key, $value, int $ttl = 0): bool;

    public function delete(string $key): bool;

    public function clear(): bool;

    /**
     * Register a cache key under a tag for grouped invalidation.
     */
    public function tagKey(string $tag, string $key): void;

    /**
     * Delete all cache keys registered under the given tag.
     */
    public function deleteByTag(string $tag): void;

    /**
     * Delete a cache key immediately, bypassing SWR (stale-while-revalidate).
     * Unlike delete(), this removes the item from stale cache too.
     */
    public function deleteImmediately(string $key): bool;
}
