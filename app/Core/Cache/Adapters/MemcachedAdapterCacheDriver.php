<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Psr\Log\LoggerInterface;

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