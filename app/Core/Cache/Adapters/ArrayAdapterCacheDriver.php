<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ArrayAdapterCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->cache = new ArrayAdapter(
            $config["defaultLifetime"] ?? 0,
            $config["storeSerialized"] ?? true,
            $config["maxLifetime"] ?? 0,
            $config["maxItems"] ?? 0
        );
    }
}