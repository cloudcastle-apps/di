<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\LazyGhostProxyFactory;
use CloudCastle\DI\Tests\Fixtures\LazyGhost\HeavyContract;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(LazyGhostProxyFactory::class)]
final class LazyGhostProxyFactoryTest extends TestCase
{
    public function testIsAvailableWhenVarExporterInstalled(): void
    {
        self::assertTrue(LazyGhostProxyFactory::isAvailable());
    }

    public function testCreateThrowsWhenTypeIsNotInterface(): void
    {
        if (!LazyGhostProxyFactory::isAvailable()) {
            self::markTestSkipped('symfony/var-exporter не установлен.');
        }

        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('lazyGhost() принимает только interface class-string');

        LazyGhostProxyFactory::create($container, stdClass::class, 'missing');
    }

    public function testCreateThrowsWhenResolvedServiceIsNotObject(): void
    {
        if (!LazyGhostProxyFactory::isAvailable()) {
            self::markTestSkipped('symfony/var-exporter не установлен.');
        }

        $container = new Container();
        $container->set('scalar', 'not-an-object');

        $proxy = LazyGhostProxyFactory::create($container, HeavyContract::class, 'scalar');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('lazyGhost(): сервис "scalar" должен быть объектом');

        $proxy->work();
    }

    public function testResolveProxyClassWrapsLogicException(): void
    {
        if (!LazyGhostProxyFactory::isAvailable()) {
            self::markTestSkipped('symfony/var-exporter не установлен.');
        }

        $method = new \ReflectionMethod(LazyGhostProxyFactory::class, 'resolveProxyClass');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Не удалось создать lazy ghost');

        $method->invoke(null, new \ReflectionClass(stdClass::class));
    }

    public function testCreateReusesGeneratedProxyClassForSameInterface(): void
    {
        if (!LazyGhostProxyFactory::isAvailable()) {
            self::markTestSkipped('symfony/var-exporter не установлен.');
        }

        $container = new Container();
        $container->set('heavy', static function (): HeavyContract {
            $class = \CloudCastle\DI\Tests\Fixtures\LazyGhost\HeavyService::class;

            return new $class();
        });

        $first = LazyGhostProxyFactory::create($container, HeavyContract::class, 'heavy');
        $second = LazyGhostProxyFactory::create($container, HeavyContract::class, 'heavy');

        self::assertSame($first::class, $second::class);
    }
}
