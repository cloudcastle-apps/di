<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerAliasTest extends TestCase
{
    public function testGetResolvesAliasToTarget(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set('app.clock', $clock);
        $container->alias(Clock::class, 'app.clock');

        self::assertSame($clock, $container->get(Clock::class));
    }

    public function testHasReturnsTrueForAlias(): void
    {
        $container = new Container();
        $container->set('service', new stdClass());
        $container->alias('alias', 'service');

        self::assertTrue($container->has('alias'));
        self::assertTrue($container->hasDefinition('alias'));
    }

    public function testHasReturnsTrueForAliasWhenTargetIsMissing(): void
    {
        $container = new Container();
        $container->alias('alias', 'missing.service');

        self::assertTrue($container->has('alias'));
        self::assertFalse($container->has('missing.service'));
    }

    public function testAliasChainResolvesToFinalTarget(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set('app.clock', $clock);
        $container->alias('clock.alias', 'app.clock');
        $container->alias(Clock::class, 'clock.alias');

        self::assertSame($clock, $container->get(Clock::class));
    }

    public function testAliasThrowsOnCircularChain(): void
    {
        $container = new Container();
        $container->alias('a', 'b');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая цепочка alias');

        $container->alias('b', 'a');
    }
}
