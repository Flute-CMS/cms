<?php

namespace Flute\Core\Cache\Contracts;

interface CacheInterface
{
    public function get(string $key, $default = null);

    public function set(string $key, $value, int $ttl = 0): bool;

    public function delete(string $key): bool;

    public function clear(): bool;
}
