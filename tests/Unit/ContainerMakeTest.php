<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerMakeTest extends TestCase
{
    public function testMakeCreatesNewInstanceEachTime(): void
    {
        $container = new Container();
        $container->set('object', static fn (): stdClass => new stdClass());

        $first = $container->make('object');
        $second = $container->make('object');

        self::assertInstanceOf(stdClass::class, $first);
        self::assertInstanceOf(stdClass::class, $second);
        self::assertNotSame($first, $second);
    }

    public function testMakeDoesNotPopulateSingletonCache(): void
    {
        $container = new Container();
        $container->set('object', static fn (): stdClass => new stdClass());

        $prototype = $container->make('object');
        $singleton = $container->get('object');

        self::assertInstanceOf(stdClass::class, $prototype);
        self::assertInstanceOf(stdClass::class, $singleton);
        self::assertNotSame($prototype, $singleton);
    }

    public function testMakeInvokesFactoryOnEveryCall(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('counter', static function () use (&$calls): int {
            ++$calls;

            return $calls;
        });

        self::assertSame(1, $container->make('counter'));
        self::assertSame(2, $container->make('counter'));
        self::assertSame(2, $calls);
    }

    public function testMakeResolvesAutowiredClassWithoutCaching(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $first = $container->make(SimpleService::class);
        $second = $container->make(SimpleService::class);

        self::assertInstanceOf(SimpleService::class, $first);
        self::assertInstanceOf(SimpleService::class, $second);
        self::assertNotSame($first, $second);
    }

    public function testMakeAppliesDecoratorsOnEachCall(): void
    {
        $container = new Container();
        $decorations = 0;
        $container->set('inner', static fn (): stdClass => new stdClass());
        $container->decorate('inner', static function (mixed $inner) use (&$decorations): mixed {
            ++$decorations;

            return $inner;
        });

        $container->make('inner');
        $container->make('inner');

        self::assertSame(2, $decorations);
    }

    public function testMakeResolvesAliasTarget(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set('app.clock', $clock);
        $container->alias(Clock::class, 'app.clock');

        self::assertSame($clock, $container->make(Clock::class));
    }
}
