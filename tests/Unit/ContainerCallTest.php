<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
final class ContainerCallTest extends TestCase
{
    public function testCallDelegatesToCallableInvoker(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->call(
            static fn (SimpleService $inner): SimpleService => $inner,
        );

        self::assertInstanceOf(SimpleService::class, $service);
    }

    public function testCallPassesExplicitParameters(): void
    {
        $container = new Container();

        $label = $container->call(
            static fn (string $label): string => $label,
            ['label' => 'explicit'],
        );

        self::assertSame('explicit', $label);
    }
}
