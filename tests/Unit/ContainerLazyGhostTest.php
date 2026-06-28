<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\LazyGhostProxyFactory;
use CloudCastle\DI\Tests\Fixtures\LazyGhost\HeavyContract;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
#[CoversClass(LazyGhostProxyFactory::class)]
final class ContainerLazyGhostTest extends TestCase
{
    private const HEAVY_SERVICE = \CloudCastle\DI\Tests\Fixtures\LazyGhost\HeavyService::class;

    public function testLazyGhostDefersImplementationLoadUntilMethodCall(): void
    {
        if (!LazyGhostProxyFactory::isAvailable()) {
            self::markTestSkipped('symfony/var-exporter не установлен.');
        }

        $this->resetHeavyConstructCount();

        $container = new Container();
        $container->set('heavy', static function (): HeavyContract {
            $class = self::HEAVY_SERVICE;

            return new $class();
        });

        $proxy = $container->lazyGhost(HeavyContract::class, 'heavy');

        $this->assertHeavyServiceNotConstructed();
        self::assertSame('heavy-result', $this->asHeavyContract($proxy)->work());
        self::assertSame(1, self::HEAVY_SERVICE::$constructCount);
    }

    public function testLazyGhostResolvesThroughContainerSingletonOnce(): void
    {
        if (!LazyGhostProxyFactory::isAvailable()) {
            self::markTestSkipped('symfony/var-exporter не установлен.');
        }

        $this->resetHeavyConstructCount();

        $container = new Container();
        $container->set('heavy', static function (): HeavyContract {
            $class = self::HEAVY_SERVICE;

            return new $class();
        });
        $proxy = $this->asHeavyContract($container->lazyGhost(HeavyContract::class, 'heavy'));

        $proxy->work();
        $proxy->work();

        self::assertSame(1, self::HEAVY_SERVICE::$constructCount);
    }

    public function testLazyGhostCanBeRegisteredAsDefinition(): void
    {
        if (!LazyGhostProxyFactory::isAvailable()) {
            self::markTestSkipped('symfony/var-exporter не установлен.');
        }

        $this->resetHeavyConstructCount();

        $container = new Container();
        $container->set('heavy', static function (): HeavyContract {
            $class = self::HEAVY_SERVICE;

            return new $class();
        });
        $container->set('contract', $container->lazyGhost(HeavyContract::class, 'heavy'));

        $this->assertHeavyServiceNotConstructed();
        $resolved = $container->get('contract');
        self::assertIsObject($resolved);
        self::assertSame('heavy-result', $this->asHeavyContract($resolved)->work());
        self::assertSame(1, self::HEAVY_SERVICE::$constructCount);
    }

    public function testLazyGhostRejectsNonInterfaceType(): void
    {
        if (!LazyGhostProxyFactory::isAvailable()) {
            self::markTestSkipped('symfony/var-exporter не установлен.');
        }

        $container = new Container();

        $this->expectExceptionMessage('lazyGhost() принимает только interface class-string');

        $container->lazyGhost(stdClass::class, 'missing');
    }

    private function resetHeavyConstructCount(): void
    {
        $class = self::HEAVY_SERVICE;

        if (class_exists($class, false)) {
            $class::$constructCount = 0;
        }
    }

    private function assertHeavyServiceNotConstructed(): void
    {
        if (!class_exists(self::HEAVY_SERVICE, false)) {
            return;
        }

        self::assertSame(0, self::HEAVY_SERVICE::$constructCount);
    }

    private function asHeavyContract(object $proxy): HeavyContract
    {
        self::assertInstanceOf(HeavyContract::class, $proxy);

        return $proxy;
    }
}
