<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\Tests\Fixtures\Compiled\StubCompiledContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use CloudCastle\DI\Tests\Support\ContainerInternalAccess;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractCompiledContainer::class)]
final class AbstractCompiledContainerSmartCacheTest extends TestCase
{
    public function testForgetForcesRecreationInCompiledGet(): void
    {
        $container = new StubCompiledContainer();

        $container->get('value');
        ContainerInternalAccess::forget($container, 'value');
        $container->get('value');

        self::assertSame(2, $container->createCount('value'));
    }

    public function testCacheForExpiresSingletonInCompiledGet(): void
    {
        $clock = new class () {
            public float $now = 1_000.0;
        };
        $container = new StubCompiledContainer(smartCacheClock: fn (): float => $clock->now);

        ContainerInternalAccess::cacheFor($container, 'value', ttlSeconds: 5);
        $container->get('value');
        $clock->now += 5.0;
        $container->get('value');

        self::assertSame(2, $container->createCount('value'));
    }
}
