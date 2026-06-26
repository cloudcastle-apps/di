<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\LazyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(LazyService::class)]
final class ContainerLazyTest extends TestCase
{
    public function testLazyDefersServiceCreationUntilGetValue(): void
    {
        $container = new Container();
        $counter = new class () {
            public int $calls = 0;
        };
        $container->set('app.clock', static function () use ($counter): Clock {
            ++$counter->calls;

            return new Clock();
        });

        $lazy = $container->lazy('app.clock');

        self::assertSame(0, $counter->calls);
        self::assertInstanceOf(Clock::class, $lazy->getValue());
        self::assertGreaterThan(0, $counter->calls);
    }

    public function testLazyGetValueReturnsCachedInstance(): void
    {
        $container = new Container();
        $counter = new class () {
            public int $calls = 0;
        };
        $container->set('app.clock', static function () use ($counter): Clock {
            ++$counter->calls;

            return new Clock();
        });

        $lazy = $container->lazy('app.clock');

        self::assertSame($lazy->getValue(), $lazy->getValue());
        self::assertGreaterThan(0, $counter->calls);
    }
}
