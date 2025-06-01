<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Psr\Log\LoggerInterface;

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