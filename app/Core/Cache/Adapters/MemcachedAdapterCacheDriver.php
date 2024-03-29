<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class MemcachedAdapterCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config)
    {
        parent::__construct($config);

        $client = MemcachedAdapter::createConnection($config["client"]);

        $this->cache = new MemcachedAdapter(
            $client,
            $config["namespace"] ?? '',
            $config["defaultLifetime"] = 0,
        );
    }
}