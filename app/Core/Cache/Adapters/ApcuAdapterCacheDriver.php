<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;

class ApcuAdapterCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger);

        $namespace = $config['namespace'] ?? '';
        $defaultLifetime = (int) ( $config['defaultLifetime'] ?? 0 );
        $version = $config['version'] ?? null;

        $this->cache = new ApcuAdapter($namespace, $defaultLifetime, $version);

        // SWR: stale cache uses a separate APCu namespace.
        $staleNamespace = $namespace !== '' ? $namespace . '_stale' : '_stale';
        $this->staleCache = new ApcuAdapter($staleNamespace, $defaultLifetime, $version);

        $staleTtl = (int) ( $config['stale_ttl'] ?? 0 );
        if ($staleTtl > 0) {
            $this->staleTtl = $staleTtl;
        }
    }
}
