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

        $this->cache = new ApcuAdapter(
            $config["namespace"] ?? '',
            $config["defaultLifetime"] ?? 0,
            $config["version"] ?? null,
        );
    }
}
