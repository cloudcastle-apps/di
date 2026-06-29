<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\CallableInvoker;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(Container::class)]
#[CoversClass(CallableInvoker::class)]
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

    public function testCallReusesLazyCallableInvokerInstance(): void
    {
        $container = new Container();
        $invokerProperty = new ReflectionProperty(Container::class, 'callableInvoker');

        $container->call(static fn (): int => 1);
        $firstInvoker = $invokerProperty->getValue($container);

        $container->call(static fn (): int => 2);
        $secondInvoker = $invokerProperty->getValue($container);

        self::assertInstanceOf(CallableInvoker::class, $firstInvoker);
        self::assertSame($firstInvoker, $secondInvoker);
    }

    public function testCallThrowsWhenInstanceMethodArrayUsesClassNameString(): void
    {
        $container = new Container();
        $handler = new class () {
            public function run(): void
            {
            }
        };

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Callable метода требует объект.');

        $container->call([$handler::class, 'run']);
    }
}
