<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerRegistry;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Глобальный singleton-реестр контейнера.
 */
#[CoversClass(ContainerRegistry::class)]
final class ContainerRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        ContainerRegistry::reset();
    }

    public function testSetAndGetGlobalContainer(): void
    {
        $container = new Container();
        ContainerRegistry::set($container);

        self::assertTrue(ContainerRegistry::has());
        self::assertSame($container, ContainerRegistry::get());
    }

    public function testGetThrowsWhenContainerIsNotInitialized(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не инициализирован');

        ContainerRegistry::get();
    }

    public function testResetClearsGlobalContainer(): void
    {
        ContainerRegistry::set(new Container());
        ContainerRegistry::reset();

        self::assertFalse(ContainerRegistry::has());
    }

    public function testGlobalContainerResolvesServices(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        ContainerRegistry::set($container);

        self::assertInstanceOf(SimpleService::class, ContainerRegistry::get()->get(SimpleService::class));
    }
}
