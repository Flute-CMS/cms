<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisAdapterCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config)
    {
        parent::__construct($config);

        $client = RedisAdapter::createConnection($config["client"]);

        $this->cache = new RedisAdapter(
            $client,
            $config["namespace"] ?? '',
            $config["defaultLifetime"] = 0,
        );
    }
}