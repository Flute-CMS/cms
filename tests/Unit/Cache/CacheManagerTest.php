<?php

namespace Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Flute\Core\Cache\CacheManager;
use Flute\Core\Cache\Adapters\FileSystemAdapterCacheDriver;
use Flute\Core\Cache\Adapters\ArrayAdapterCacheDriver;

class CacheManagerTest extends TestCase
{
    private LoggerInterface $logger;
    private CacheManager $manager;

    protected function setUp() : void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->manager = new CacheManager($this->logger);
    }

    public function testCreateFileDriver() : void
    {
        $driver = $this->manager->create([
            'driver' => 'file',
            'namespace' => 'my_test_ns',
        ]);
        $this->assertInstanceOf(FileSystemAdapterCacheDriver::class, $driver);
    }

    public function testCreateArrayDriverByDefault() : void
    {
        $driver = $this->manager->create([
            // driver не указан => fallback 'array'
        ]);
        $this->assertInstanceOf(ArrayAdapterCacheDriver::class, $driver);
    }

    public function testUnsupportedDriverLogsWarning() : void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains("Unsupported cache driver: invalid"));

        $driver = $this->manager->create(['driver' => 'invalid']);
        // при невалидном драйвере — fallback => FileSystem
        $this->assertInstanceOf(FileSystemAdapterCacheDriver::class, $driver);
    }
}
