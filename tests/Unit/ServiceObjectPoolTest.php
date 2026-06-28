<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\ServiceObjectPool;
use CloudCastle\DI\Tests\Fixtures\MemoryPool\ResetCounter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ServiceObjectPool::class)]
final class ServiceObjectPoolTest extends TestCase
{
    private ServiceObjectPool $pool;

    protected function setUp(): void
    {
        $this->pool = new ServiceObjectPool();
    }

    public function testAcquireCreatesWhenPoolIsEmpty(): void
    {
        $this->pool->configure('svc');
        $created = 0;

        self::assertInstanceOf(
            stdClass::class,
            $this->pool->acquire('svc', static function () use (&$created): stdClass {
                ++$created;

                return new stdClass();
            }),
        );

        self::assertSame(1, $created);
    }

    public function testReleaseAndAcquireReuseSameInstance(): void
    {
        $this->pool->configure('svc', maxSize: 2);
        $first = $this->pool->acquire('svc', static fn (): stdClass => new stdClass());
        self::assertInstanceOf(stdClass::class, $first);
        $this->pool->release('svc', $first);

        $created = 0;
        $second = $this->pool->acquire('svc', static function () use (&$created): stdClass {
            ++$created;

            return new stdClass();
        });

        self::assertSame($first, $second);
        self::assertSame(0, $created);
    }

    public function testAcquireWithoutConfigureCallsFactory(): void
    {
        $created = 0;

        self::assertInstanceOf(
            stdClass::class,
            $this->pool->acquire('svc', static function () use (&$created): stdClass {
                ++$created;

                return new stdClass();
            }),
        );
        self::assertSame(1, $created);
    }

    public function testClearAllRemovesAvailableInstancesFromEveryPool(): void
    {
        $this->pool->configure('first');
        $this->pool->configure('second');
        self::assertInstanceOf(
            stdClass::class,
            $first = $this->pool->acquire('first', static fn (): stdClass => new stdClass()),
        );
        self::assertInstanceOf(
            stdClass::class,
            $second = $this->pool->acquire('second', static fn (): stdClass => new stdClass()),
        );
        $this->pool->release('first', $first);
        $this->pool->release('second', $second);
        $this->pool->clearAll();

        self::assertSame(0, $this->pool->stats('first')['available']);
        self::assertSame(0, $this->pool->stats('second')['available']);
    }

    public function testReleaseWithoutConfigureThrows(): void
    {
        $this->expectException(ContainerException::class);

        $this->pool->release('svc', new stdClass());
    }

    public function testConfigureRejectsZeroMaxSize(): void
    {
        $this->expectException(ContainerException::class);

        $this->pool->configure('svc', maxSize: 0);
    }

    public function testRemoveClearsConfigurationAndAvailableInstances(): void
    {
        $this->pool->configure('svc');
        $instance = $this->pool->acquire('svc', static fn (): stdClass => new stdClass());
        self::assertInstanceOf(stdClass::class, $instance);
        $this->pool->release('svc', $instance);
        $this->pool->remove('svc');

        self::assertSame(
            ['configured' => false, 'max_size' => 0, 'available' => 0],
            $this->pool->stats('svc'),
        );
    }

    public function testReleaseRespectsMaxSize(): void
    {
        $this->pool->configure('svc', maxSize: 1);
        $first = new stdClass();
        $second = new stdClass();
        $this->pool->release('svc', $first);
        $this->pool->release('svc', $second);

        self::assertSame(1, $this->pool->stats('svc')['available']);
    }

    public function testClearRemovesAvailableButKeepsConfiguration(): void
    {
        $this->pool->configure('svc');
        $instance = $this->pool->acquire('svc', static fn (): stdClass => new stdClass());
        self::assertInstanceOf(stdClass::class, $instance);
        $this->pool->release('svc', $instance);
        $this->pool->clear('svc');

        self::assertSame(
            ['configured' => true, 'max_size' => 16, 'available' => 0],
            $this->pool->stats('svc'),
        );
    }

    public function testReleaseCallsResetOnPoolableInstance(): void
    {
        ResetCounter::resetCounters();
        $this->pool->configure('svc');
        $counter = new ResetCounter(value: 9);
        $this->pool->release('svc', $counter);

        self::assertSame(0, $counter->value);
        self::assertSame(1, ResetCounter::resetCount());
    }
}
