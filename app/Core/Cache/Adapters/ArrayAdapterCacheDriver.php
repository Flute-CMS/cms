<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ArrayAdapterCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger);

        $this->cache = new ArrayAdapter(
            $config["defaultLifetime"] ?? 0,
            $config["storeSerialized"] ?? true,
            $config["maxLifetime"] ?? 0,
            $config["maxItems"] ?? 0
        );
    }
}
