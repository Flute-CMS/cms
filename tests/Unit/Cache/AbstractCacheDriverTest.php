<?php

namespace Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\Stubs\TestCacheDriver;

/**
 * @coversDefaultClass \Flute\Core\Cache\AbstractCacheDriver
 */
class AbstractCacheDriverTest extends TestCase
{
    private TestCacheDriver $driver;
    private LoggerInterface $logger;

    protected function setUp() : void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->driver = new TestCacheDriver([], $this->logger);
    }

    /**
     * @covers ::get
     */
    public function testGetReturnsDefaultIfItemNotFound() : void
    {
        $result = $this->driver->get('non_existing_key', 'default_val');
        $this->assertSame('default_val', $result);
    }

    /**
     * @covers ::set
     */
    public function testSetStoresValueInCache() : void
    {
        $stored = $this->driver->set('some_key', 'some_value', 60);
        $this->assertTrue($stored);

        $this->assertSame('some_value', $this->driver->get('some_key'));
    }

    /**
     * @covers ::set
     */
    public function testSetLogsErrorOnSaveFailure() : void
    {
        $mockAdapter = $this->createMock(ArrayAdapter::class);
        $mockAdapter->method('getItem')->willReturn((new ArrayAdapter())->getItem('tmp'));
        $mockAdapter->method('save')->willReturn(false);
        $this->driver->setCacheAdapter($mockAdapter);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('ERROR SAVE CACHE'));

        $result = $this->driver->set('key', 'val');
        $this->assertFalse($result);
    }

    /**
     * @covers ::callback
     */
    public function testCallbackExecutesCallableWhenCacheMiss() : void
    {
        $callsCounter = 0;

        $value = $this->driver->callback('calc_key', function ($item) use (&$callsCounter) {
            $callsCounter++;
            return 'calculated_value';
        }, 120);

        $this->assertSame('calculated_value', $value);
        $this->assertSame(1, $callsCounter, 'Callback должен был быть вызван один раз');

        $value2 = $this->driver->callback('calc_key', function ($item) use (&$callsCounter) {
            $callsCounter++;
            return 'new_value';
        }, 120);

        $this->assertSame('calculated_value', $value2);
        $this->assertSame(1, $callsCounter, 'Callback не должен повторно вызываться, если значение уже в кэше');
    }
}
