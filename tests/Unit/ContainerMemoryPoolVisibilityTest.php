<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerMemoryPoolApi;
use CloudCastle\DI\ContainerMemoryPoolSupport;
use CloudCastle\DI\Tests\Fixtures\MemoryPool\ResetCounter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(Container::class)]
#[CoversClass(ContainerMemoryPoolApi::class)]
#[CoversClass(ContainerMemoryPoolSupport::class)]
final class ContainerMemoryPoolVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        ResetCounter::resetCounters();
    }

    /**
     * @covers \CloudCastle\DI\ContainerMemoryPoolApi::enablePooling
     */
    public function testEnablePoolingMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'enablePooling');

        self::assertTrue($method->isPublic());

        $container = $this->createPoolingContainer();
        $container->enablePooling('counter', maxSize: 2);
        self::assertTrue($container->isPoolingEnabled('counter'));
    }

    /**
     * @covers \CloudCastle\DI\ContainerMemoryPoolApi::disablePooling
     */
    public function testDisablePoolingMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'disablePooling');

        self::assertTrue($method->isPublic());

        $container = $this->createPoolingContainer();
        $container->enablePooling('counter');
        $container->disablePooling('counter');
        self::assertFalse($container->isPoolingEnabled('counter'));
    }

    /**
     * @covers \CloudCastle\DI\ContainerMemoryPoolApi::isPoolingEnabled
     */
    public function testIsPoolingEnabledMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'isPoolingEnabled');

        self::assertTrue($method->isPublic());

        $container = $this->createPoolingContainer();
        self::assertFalse($container->isPoolingEnabled('counter'));
        $container->enablePooling('counter');
        self::assertTrue($container->isPoolingEnabled('counter'));
    }

    /**
     * @covers \CloudCastle\DI\ContainerMemoryPoolApi::releaseToPool
     */
    public function testReleaseToPoolMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'releaseToPool');

        self::assertTrue($method->isPublic());

        $container = $this->createPoolingContainer();
        $container->enablePooling('counter');

        $instance = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $instance);
        $container->releaseToPool('counter', $instance);
        self::assertSame(1, $container->poolStats('counter')['available']);
    }

    /**
     * @covers \CloudCastle\DI\ContainerMemoryPoolApi::clearPool
     */
    public function testClearPoolMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'clearPool');

        self::assertTrue($method->isPublic());

        $container = $this->createPoolingContainer();
        $container->enablePooling('counter');

        $instance = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $instance);
        $container->releaseToPool('counter', $instance);
        $container->clearPool('counter');
        self::assertSame(0, $container->poolStats('counter')['available']);
    }

    /**
     * @covers \CloudCastle\DI\ContainerMemoryPoolApi::clearAllPools
     */
    public function testClearAllPoolsMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'clearAllPools');

        self::assertTrue($method->isPublic());

        $container = $this->createPoolingContainer();
        $container->enablePooling('counter');

        $instance = $container->make('counter');
        self::assertInstanceOf(ResetCounter::class, $instance);
        $container->releaseToPool('counter', $instance);
        $container->clearAllPools();
        self::assertSame(0, $container->poolStats('counter')['available']);
    }

    /**
     * @covers \CloudCastle\DI\ContainerMemoryPoolApi::poolStats
     */
    public function testPoolStatsMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'poolStats');

        self::assertTrue($method->isPublic());

        $container = $this->createPoolingContainer();
        $container->enablePooling('counter', maxSize: 3);
        self::assertSame(
            ['configured' => true, 'max_size' => 3, 'available' => 0],
            $container->poolStats('counter'),
        );
    }

    private function createPoolingContainer(): Container
    {
        $container = new Container();
        $container->set('counter', static fn (): ResetCounter => new ResetCounter());

        return $container;
    }
}
