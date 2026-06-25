<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerTest extends TestCase
{
    public function testGetReturnsRegisteredInstance(): void
    {
        $container = new Container();
        $service = new stdClass();
        $container->set('service', $service);

        self::assertSame($service, $container->get('service'));
    }

    public function testGetReturnsCachedInstanceWithoutReinvokingFactory(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('counter', static function () use (&$calls): int {
            ++$calls;

            return $calls;
        });

        self::assertSame(1, $container->get('counter'));
        self::assertSame(1, $container->get('counter'));
        self::assertSame(1, $calls);
    }

    public function testGetPassesContainerToFactory(): void
    {
        $container = new Container();
        $container->set('self', static fn (ContainerInterface $container): ContainerInterface => $container);

        self::assertSame($container, $container->get('self'));
    }

    public function testGetThrowsWhenServiceMissing(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Сервис "missing" не зарегистрирован.');

        $container->get('missing');
    }

    public function testSetClearsPreviouslyResolvedInstance(): void
    {
        $container = new Container();
        $first = new stdClass();
        $second = new stdClass();
        $container->set('service', $first);
        self::assertSame($first, $container->get('service'));

        $container->set('service', $second);

        self::assertSame($second, $container->get('service'));
    }

    public function testHasDefinitionDetectsRegistration(): void
    {
        $container = new Container();
        $container->set('service', new stdClass());

        self::assertTrue($container->hasDefinition('service'));
        self::assertFalse($container->hasDefinition('missing'));
    }

    public function testHasDetectsRegisteredService(): void
    {
        $container = new Container();
        $container->set('service', new stdClass());

        self::assertTrue($container->has('service'));
        self::assertFalse($container->has('missing'));
    }

    public function testHasDetectsResolvedServiceAfterGet(): void
    {
        $container = new Container();
        $container->set('service', static fn (): stdClass => new stdClass());
        $container->get('service');

        self::assertTrue($container->has('service'));
        self::assertTrue($container->hasDefinition('service'));
    }

    public function testContainerIsFinal(): void
    {
        self::assertTrue((new ReflectionClass(Container::class))->isFinal());
    }
}
