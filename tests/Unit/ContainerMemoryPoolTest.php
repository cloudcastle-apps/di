<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerMemoryPoolSupport;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\ServiceObjectPool;
use CloudCastle\DI\Tests\Fixtures\MemoryPool\ResetCounter;
use CloudCastle\DI\Tests\Support\ContainerInternalAccess;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
        ContainerInternalAccess::enablePooling($container, 'counter', maxSize: 4);

        $first = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $first);
        self::assertSame(5, $first->value);
        ContainerInternalAccess::releaseToPool($container, 'counter', $first);

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
        ContainerInternalAccess::enablePooling($container, 'counter');

        self::assertSame($container->get('counter'), $container->get('counter'));
        self::assertSame(1, ResetCounter::createdCount());
    }

    public function testDisablePoolingClearsStats(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());
        ContainerInternalAccess::enablePooling($container, 'counter');

        $instance = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $instance);
        ContainerInternalAccess::releaseToPool($container, 'counter', $instance);
        ContainerInternalAccess::disablePooling($container, 'counter');

        self::assertFalse(ContainerInternalAccess::isPoolingEnabled($container, 'counter'));
        self::assertSame(
            ['configured' => false, 'max_size' => 0, 'available' => 0],
            ContainerInternalAccess::poolStats($container, 'counter'),
        );
    }

    public function testReleaseToPoolWithoutEnableThrows(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());

        $this->expectException(ContainerException::class);

        $instance = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $instance);
        ContainerInternalAccess::releaseToPool($container, 'counter', $instance);
    }

    public function testDefaultMaxSizeMatchesServiceObjectPoolConstant(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());
        ContainerInternalAccess::enablePooling($container, 'counter');

        self::assertSame(
            ServiceObjectPool::DEFAULT_MAX_SIZE,
            ContainerInternalAccess::poolStats($container, 'counter')['max_size'],
        );
    }

    public function testClearPoolEmptiesAvailableInstances(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());
        ContainerInternalAccess::enablePooling($container, 'counter');

        $instance = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $instance);
        ContainerInternalAccess::releaseToPool($container, 'counter', $instance);
        ContainerInternalAccess::clearPool($container, 'counter');

        self::assertSame(0, ContainerInternalAccess::poolStats($container, 'counter')['available']);
        self::assertTrue(ContainerInternalAccess::isPoolingEnabled($container, 'counter'));
    }

    public function testClearAllPoolsEmptiesEveryConfiguredPool(): void
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());
        $container->set('other', static fn (): ResetCounter => new ResetCounter());
        ContainerInternalAccess::enablePooling($container, 'counter');
        ContainerInternalAccess::enablePooling($container, 'other');

        self::assertInstanceOf(ResetCounter::class, $counter = $container->make('counter'));
        self::assertInstanceOf(ResetCounter::class, $other = $container->make('other'));
        ContainerInternalAccess::releaseToPool($container, 'counter', $counter);
        ContainerInternalAccess::releaseToPool($container, 'other', $other);
        ContainerInternalAccess::clearAllPools($container);

        self::assertTrue(ContainerInternalAccess::isPoolingEnabled($container, 'counter'));
        self::assertTrue(ContainerInternalAccess::isPoolingEnabled($container, 'other'));
        self::assertSame(0, ContainerInternalAccess::poolStats($container, 'counter')['available']);
        self::assertSame(0, ContainerInternalAccess::poolStats($container, 'other')['available']);
    }
}
