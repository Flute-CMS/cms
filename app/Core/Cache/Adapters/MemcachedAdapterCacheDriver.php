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

        $client = MemcachedAdapter::createConnection($config["client"]);

        $this->cache = new MemcachedAdapter(
            $client,
            $config["namespace"] ?? '',
            $config["defaultLifetime"] = 0,
        );
    }
}
