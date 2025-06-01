<?php

namespace Tests\Integration\Cache;

use PHPUnit\Framework\TestCase;
use Flute\Core\Cache\Adapters\FileSystemAdapterCacheDriver;
use Psr\Log\LoggerInterface;

class FileSystemAdapterCacheDriverTest extends TestCase
{
    private FileSystemAdapterCacheDriver $driver;
    private string $tmpDir;
    private LoggerInterface $logger;

    protected function setUp() : void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->tmpDir = sys_get_temp_dir() . '/fs_cache_test_' . uniqid();
        mkdir($this->tmpDir);

        $config = [
            'namespace' => 'test_ns',
            'directory' => $this->tmpDir,
        ];

        $this->driver = new FileSystemAdapterCacheDriver($config, $this->logger);
    }

    protected function tearDown() : void
    {
        if (is_dir($this->tmpDir)) {
            exec('rm -rf ' . escapeshellarg($this->tmpDir));
        }
        parent::tearDown();
    }

    public function testSetAndGetSuccess() : void
    {
        $this->assertTrue($this->driver->set('key', 'value', 60));
        $this->assertSame('value', $this->driver->get('key'));
    }

    public function testSetFailWhenNoWritePermission() : void
    {
        chmod($this->tmpDir, 0555);

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('ERROR SAVE CACHE'));

        $this->assertFalse($this->driver->set('readonly_key', 'some_val'));
    }
}
