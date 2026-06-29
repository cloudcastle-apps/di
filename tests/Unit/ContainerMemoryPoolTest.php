<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerMemoryPoolSupport;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\ServiceObjectPool;
use CloudCastle\DI\Tests\Fixtures\MemoryPool\ResetCounter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(ContainerMemoryPoolSupport::class)]
final class ContainerMemoryPoolTest extends TestCase
{
    protected function setUp(): void
    {
        ResetCounter::resetCounters();
    }

    public function testMakeWithoutPoolingAlwaysCreatesNewInstance(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter(value: 1));

        self::assertNotSame($container->make('counter'), $container->make('counter'));
        self::assertSame(2, ResetCounter::createdCount());
    }

    public function testPoolingReusesReleasedInstanceOnMake(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter(value: 5));
        $container->enablePooling('counter', maxSize: 4);

        $first = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $first);
        self::assertSame(5, $first->value);
        $container->releaseToPool('counter', $first);

        $second = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $second);

        self::assertSame($first, $second);
        self::assertSame(0, $second->value);
        self::assertSame(1, ResetCounter::createdCount());
        self::assertSame(1, ResetCounter::resetCount());
    }

    public function testGetIgnoresPoolingAndUsesSingletonCache(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());
        $container->enablePooling('counter');

        self::assertSame($container->get('counter'), $container->get('counter'));
        self::assertSame(1, ResetCounter::createdCount());
    }

    public function testDisablePoolingClearsStats(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());
        $container->enablePooling('counter');

        $instance = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $instance);
        $container->releaseToPool('counter', $instance);
        $container->disablePooling('counter');

        self::assertFalse($container->isPoolingEnabled('counter'));
        self::assertSame(
            ['configured' => false, 'max_size' => 0, 'available' => 0],
            $container->poolStats('counter'),
        );
    }

    public function testReleaseToPoolWithoutEnableThrows(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());

        $this->expectException(ContainerException::class);

        $instance = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $instance);
        $container->releaseToPool('counter', $instance);
    }

    public function testDefaultMaxSizeMatchesServiceObjectPoolConstant(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());
        $container->enablePooling('counter');

        self::assertSame(
            ServiceObjectPool::DEFAULT_MAX_SIZE,
            $container->poolStats('counter')['max_size'],
        );
    }

    public function testClearPoolEmptiesAvailableInstances(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());
        $container->enablePooling('counter');

        $instance = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $instance);
        $container->releaseToPool('counter', $instance);
        $container->clearPool('counter');

        self::assertSame(0, $container->poolStats('counter')['available']);
        self::assertTrue($container->isPoolingEnabled('counter'));
    }

    public function testClearAllPoolsEmptiesEveryConfiguredPool(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());
        $container->set('other', static fn (): ResetCounter => new ResetCounter());
        $container->enablePooling('counter');
        $container->enablePooling('other');

        self::assertInstanceOf(ResetCounter::class, $counter = $container->make('counter'));
        self::assertInstanceOf(ResetCounter::class, $other = $container->make('other'));
        $container->releaseToPool('counter', $counter);
        $container->releaseToPool('other', $other);
        $container->clearAllPools();

        self::assertTrue($container->isPoolingEnabled('counter'));
        self::assertTrue($container->isPoolingEnabled('other'));
        self::assertSame(0, $container->poolStats('counter')['available']);
        self::assertSame(0, $container->poolStats('other')['available']);
    }
}
