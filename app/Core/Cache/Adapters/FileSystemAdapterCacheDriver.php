<?php

namespace Flute\Core\Cache\Adapters;

use Flute\Core\Cache\AbstractCacheDriver;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class FileSystemAdapterCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->cache = new FilesystemAdapter(
            $config["namespace"] ?? '',
            $config["defaultLifetime"] = 0,
            $config["directory"] ?? '',
        );
    }
}