<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Psr\Log\LoggerInterface;

class RedisAdapterCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger);

        $client = RedisAdapter::createConnection($config["client"]);

        $this->cache = new RedisAdapter(
            $client,
            $config["namespace"] ?? '',
            $config["defaultLifetime"] ?? 0,
        );
    }
}