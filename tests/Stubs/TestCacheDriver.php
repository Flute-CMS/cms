<?php

namespace Tests\Stubs;

use Flute\Core\Cache\AbstractCacheDriver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class TestCacheDriver extends AbstractCacheDriver
{
    public function __construct(array $config, LoggerInterface $logger)
    {
        parent::__construct($config, $logger);
        $this->cache = new ArrayAdapter();
    }

    public function setCacheAdapter(AdapterInterface $adapter)
    {
        $this->cache = $adapter;
    }
}
