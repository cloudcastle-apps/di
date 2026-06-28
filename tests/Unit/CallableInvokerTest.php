<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\CallableInvoker;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

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

    public function testReflectCallableBuildsReflectionForClosure(): void
    {
        $closure = static fn (): int => 1;
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'reflectCallable');
        $reflection = $method->invoke($invoker, $closure);

        self::assertInstanceOf(ReflectionFunction::class, $reflection);
    }

    public function testReflectCallableBuildsReflectionForInvokableObject(): void
    {
        $invokable = new class () {
            public function __invoke(): void
            {
            }
        };
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'reflectCallable');
        $reflection = $method->invoke($invoker, $invokable);

        self::assertInstanceOf(ReflectionMethod::class, $reflection);
        self::assertSame('__invoke', $reflection->getName());
    }

    public function testInvokeCallsFunctionByName(): void
    {
        $result = (new CallableInvoker(new Container()))->invoke('strlen', ['string' => 'abc']);

        self::assertSame(3, $result);
    }

    public function testReflectCallableValueThrowsForUnsupportedType(): void
    {
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'reflectCallableValue');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Неподдерживаемый тип callable.');

        $method->invoke($invoker, 123);
    }

    public function testReflectCallableValueThrowsWhenArrayCallableHasNonStringMethod(): void
    {
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'reflectCallableValue');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Неподдерживаемый тип callable.');

        $method->invoke($invoker, ['target', 456]);
    }

    public function testReflectCallableValueThrowsWhenArrayCallableHasInvalidTarget(): void
    {
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'reflectCallableValue');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Неподдерживаемый тип callable.');

        $method->invoke($invoker, [123, 'handle']);
    }

    public function testInvokeWithArgumentsThrowsWhenInstanceMethodTargetIsNotObject(): void
    {
        $handler = new class () {
            public function handle(): void
            {
            }
        };
        $callable = [$handler::class, 'handle'];
        $reflection = new ReflectionMethod($callable[0], $callable[1]);
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'invokeWithArguments');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Callable метода требует объект.');

        $method->invoke($invoker, $callable, $reflection, []);
    }

    public function testInvokeWithArgumentsThrowsWhenCallableIsNeitherArrayNorObject(): void
    {
        $handler = new class () {
            public function run(): void
            {
            }
        };
        $reflection = new ReflectionMethod($handler, 'run');
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'invokeWithArguments');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Callable метода требует объект.');

        $method->invoke($invoker, 'strlen', $reflection, []);
    }

    public function testInvokeWithArgumentsThrowsForUnsupportedReflectionType(): void
    {
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'invokeWithArguments');
        $reflection = $this->createMock(ReflectionFunctionAbstract::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Неподдерживаемый тип reflection callable');

        $method->invoke($invoker, 'strlen', $reflection, []);
    }

    public static function namedHandler(string $label): string
    {
        return $label;
    }
}
