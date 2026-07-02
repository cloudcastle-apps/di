<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\CallableInvoker;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use stdClass;

#[CoversClass(CallableInvoker::class)]
final class CallableInvokerReflectionExceptionTest extends TestCase
{
    public function testReflectCallableValueWrapsReflectionExceptionForNonExistentMethod(): void
    {
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'reflectCallableValue');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Метод callable не найден или недоступен:');

        $method->invoke($invoker, [self::class, 'nonExistentMethod']);
    }

    public function testReflectCallableValueWrapsReflectionExceptionForObjectWithNonExistentMethod(): void
    {
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'reflectCallableValue');

        $target = new class () {
        };

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Метод callable не найден или недоступен:');

        $method->invoke($invoker, [$target, 'missingMethod']);
    }

    public function testReflectCallableValuePreservesReflectionExceptionAsPrevious(): void
    {
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'reflectCallableValue');

        try {
            $method->invoke($invoker, [self::class, 'nonExistentMethod']);
            self::fail('Expected ContainerException');
        } catch (ContainerException $containerException) {
            self::assertInstanceOf(ReflectionException::class, $containerException->getPrevious());
        }
    }

    public function testInvokeWithArgumentsWrapsReflectionExceptionFromInvokeArgs(): void
    {
        $invoker = new CallableInvoker(new Container());
        $method = new ReflectionMethod(CallableInvoker::class, 'invokeWithArguments');

        $handler = new class () {
            public function run(): string
            {
                return 'ok';
            }
        };
        $wrongTarget = new stdClass();
        $reflection = new ReflectionMethod($handler, 'run');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Ошибка вызова callable:');

        $method->invoke($invoker, [$wrongTarget, 'run'], $reflection, []);
    }
}
