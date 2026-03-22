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

        $namespace = $config['namespace'] ?? '';
        $defaultLifetime = (int) ( $config['defaultLifetime'] ?? 0 );
        $directory = (string) ( $config['directory'] ?? '' );
        $staleDirectory = (string) ( $config['stale_directory'] ?? '' );

        $this->cache = new FilesystemAdapter($namespace, $defaultLifetime, $directory);

        if ($staleDirectory !== '') {
            if (!is_dir($staleDirectory)) {
                @mkdir($staleDirectory, 0o755, true);
            }

            $this->staleCache = new FilesystemAdapter($namespace, $defaultLifetime, $staleDirectory);
        }

        $staleTtl = (int) ( $config['stale_ttl'] ?? 0 );
        if ($staleTtl > 0) {
            $this->staleTtl = $staleTtl;
        }
    }
}
