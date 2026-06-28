<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\LazyGhostProxyFactory;
use CloudCastle\DI\Tests\Fixtures\Compiled\StubCompiledContainer;
use CloudCastle\DI\Tests\Fixtures\LazyGhost\HeavyContract;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractCompiledContainer::class)]
#[CoversClass(LazyGhostProxyFactory::class)]
final class AbstractCompiledContainerLazyGhostTest extends TestCase
{
    public function testLazyGhostWorksInCompiledContainer(): void
    {
        if (!LazyGhostProxyFactory::isAvailable()) {
            self::markTestSkipped('symfony/var-exporter не установлен.');
        }

        $container = new StubCompiledContainer();
        $proxy = $container->lazyGhost(HeavyContract::class, 'heavy');

        self::assertSame(0, $container->createCount('heavy'));
        self::assertSame('heavy-result', $this->asHeavyContract($proxy)->work());
        self::assertSame(1, $container->createCount('heavy'));
    }

    private function asHeavyContract(object $proxy): HeavyContract
    {
        self::assertInstanceOf(HeavyContract::class, $proxy);

        return $proxy;
    }
}
