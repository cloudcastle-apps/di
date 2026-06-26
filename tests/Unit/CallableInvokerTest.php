<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\CallableInvoker;
use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CallableInvoker::class)]
final class CallableInvokerTest extends TestCase
{
    public function testInvokeAutowiresClosureParameters(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $result = (new CallableInvoker($container))->invoke(
            static fn (SimpleService $service): string => $service::class,
        );

        self::assertSame(SimpleService::class, $result);
    }

    public function testInvokeUsesExplicitParametersByName(): void
    {
        $container = new Container();
        $clock = new Clock();

        $result = (new CallableInvoker($container))->invoke(
            static fn (Clock $clock, string $label): string => $label . ':' . $clock::class,
            ['clock' => $clock, 'label' => 'ok'],
        );

        self::assertSame('ok:' . Clock::class, $result);
    }

    public function testInvokeCallsInstanceMethod(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $target = new class () {
            public function receive(Clock $clock): string
            {
                return $clock::class;
            }
        };

        $result = (new CallableInvoker($container))->invoke($target->receive(...));

        self::assertSame(Clock::class, $result);
    }

    public function testInvokeCallsInstanceArrayCallable(): void
    {
        $target = new class () {
            public function handle(string $label): string
            {
                return $label;
            }
        };

        $result = (new CallableInvoker(new Container()))->invoke([$target, 'handle'], ['label' => 'ok']);

        self::assertSame('ok', $result);
    }

    public function testInvokeCallsArrayCallable(): void
    {
        $result = (new CallableInvoker(new Container()))->invoke(
            [self::class, 'namedHandler'],
            ['label' => 'ok'],
        );

        self::assertSame('ok', $result);
    }

    public function testInvokeCallsInvokableObject(): void
    {
        $invokable = new class () {
            public function __invoke(string $label): string
            {
                return $label;
            }
        };

        $result = (new CallableInvoker(new Container()))->invoke($invokable, ['label' => 'ok']);

        self::assertSame('ok', $result);
    }

    public function testInvokeCallsFunctionByName(): void
    {
        $result = (new CallableInvoker(new Container()))->invoke('strlen', ['string' => 'abc']);

        self::assertSame(3, $result);
    }

    public static function namedHandler(string $label): string
    {
        return $label;
    }
}
