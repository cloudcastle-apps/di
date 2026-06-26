<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerAfterResolvingTest extends TestCase
{
    public function testAfterResolvingFiresOnceForSingleton(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('object', new stdClass());
        $container->afterResolving('object', static function () use (&$calls): void {
            ++$calls;
        });

        $container->get('object');
        $container->get('object');

        self::assertSame(1, $calls);
    }

    public function testAfterResolvingFiresOnEachMake(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('object', static fn (): stdClass => new stdClass());
        $container->afterResolving('object', static function () use (&$calls): void {
            ++$calls;
        });

        $container->make('object');
        $container->make('object');

        self::assertSame(2, $calls);
    }

    public function testAfterResolvingReceivesInstanceAndContainer(): void
    {
        $container = new Container();
        $clock = new Clock();
        $container->set('app.clock', $clock);
        $seen = null;

        $container->afterResolving(
            'app.clock',
            static function (
                string $id,
                mixed $instance,
                ContainerInterface $resolvedContainer,
            ) use (&$seen): void {
                $seen = [$id, $instance, $resolvedContainer];
            },
        );

        $container->get('app.clock');

        self::assertNotNull($seen);
        self::assertSame('app.clock', $seen[0]);
        self::assertSame($clock, $seen[1]);
        self::assertInstanceOf(Container::class, $seen[2]);
    }
}
