<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class FileSystemAdapterCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger);
        $this->cache = new FilesystemAdapter(
            $config["namespace"] ?? '',
            $config["defaultLifetime"] = 0,
            $config["directory"] ?? '',
        );
    }
}
