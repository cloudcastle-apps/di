<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Граничные случаи регистрации и разрешения сервисов.
 */
#[CoversClass(Container::class)]
final class ContainerEdgeCaseTest extends TestCase
{
    public function testGetReturnsScalarAndArrayInstances(): void
    {
        $container = new Container();
        $container->set('counter', 42);
        $container->set('label', 'cloudcastle');
        $container->set('enabled', false);
        $container->set('tags', ['di', 'psr-11']);

        self::assertSame(42, $container->get('counter'));
        self::assertSame('cloudcastle', $container->get('label'));
        self::assertFalse($container->get('enabled'));
        self::assertSame(['di', 'psr-11'], $container->get('tags'));
    }

    public function testFactoryCanBeInvokableObject(): void
    {
        $container = new Container();
        $factory = new class () {
            public int $calls = 0;

            public function __invoke(): string
            {
                ++$this->calls;

                return 'created';
            }
        };
        $container->set('service', $factory);

        self::assertSame('created', $container->get('service'));
        self::assertSame('created', $container->get('service'));
        self::assertSame(1, $factory->calls);
    }

    public function testFactoryCanBeFirstClassCallable(): void
    {
        $container = new Container();
        $factory = new class () {
            public function create(): stdClass
            {
                return new stdClass();
            }
        };
        $container->set('service', $factory->create(...));

        self::assertInstanceOf(stdClass::class, $container->get('service'));
    }

    public function testEmptyStringIdentifierWorks(): void
    {
        $container = new Container();
        $service = new stdClass();
        $container->set('', $service);

        self::assertSame($service, $container->get(''));
        self::assertTrue($container->has(''));
        self::assertTrue($container->hasDefinition(''));
    }

    public function testGetCachesFalsyValuesExceptNull(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('zero', static function () use (&$calls): int {
            ++$calls;

            return 0;
        });
        $container->set('empty', static function () use (&$calls): string {
            ++$calls;

            return '';
        });
        $container->set('disabled', static function () use (&$calls): bool {
            ++$calls;

            return false;
        });

        self::assertSame(0, $container->get('zero'));
        self::assertSame('', $container->get('empty'));
        self::assertFalse($container->get('disabled'));
        $callsAfterFirstPass = $calls;
        self::assertSame(3, $callsAfterFirstPass);

        $container->get('zero');
        $container->get('empty');
        $container->get('disabled');

        self::assertSame($callsAfterFirstPass, $calls);
    }

    public function testGetReinvokesFactoryWhenItReturnsNull(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('nullable', static function () use (&$calls): null {
            ++$calls;

            return null;
        });

        self::assertNull($container->get('nullable'));
        self::assertNull($container->get('nullable'));
        self::assertSame(2, $calls);
    }

    public function testNullRegistrationIsNotDetectableByHasOrHasDefinition(): void
    {
        $container = new Container();
        $container->set('nullable', null);

        self::assertFalse($container->hasDefinition('nullable'));
        self::assertFalse($container->has('nullable'));

        $this->expectException(NotFoundException::class);
        $container->get('nullable');
    }

    public function testSetReplacingFactoryAfterResolutionUsesNewFactory(): void
    {
        $container = new Container();
        $container->set('token', static fn (): string => 'first');
        self::assertSame('first', $container->get('token'));

        $container->set('token', static fn (): string => 'second');

        self::assertSame('second', $container->get('token'));
    }
}
