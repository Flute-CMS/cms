<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class MemcachedAdapterCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger);

        $client = MemcachedAdapter::createConnection($config['client']);
        $namespace = $config['namespace'] ?? '';
        $defaultLifetime = (int) ( $config['defaultLifetime'] ?? 0 );

        $this->cache = new MemcachedAdapter($client, $namespace, $defaultLifetime);

        // SWR: stale cache uses the same Memcached connection with a _stale namespace suffix.
        $staleNamespace = $namespace !== '' ? $namespace . '_stale' : '_stale';
        $this->staleCache = new MemcachedAdapter($client, $staleNamespace, $defaultLifetime);

        $staleTtl = (int) ( $config['stale_ttl'] ?? 0 );
        if ($staleTtl > 0) {
            $this->staleTtl = $staleTtl;
        }
    }
}
