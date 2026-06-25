<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Autowirer;
use CloudCastle\DI\ClassDependencyResolver;
use CloudCastle\DI\Container;
use CloudCastle\DI\IntersectionTypeResolver;
use CloudCastle\DI\MemberResolver;
use CloudCastle\DI\ParameterTypeResolver;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\ContainerConsumer;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerUser;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Autowiring контейнера.
 */
#[CoversClass(Container::class)]
#[CoversClass(Autowirer::class)]
#[CoversClass(ClassDependencyResolver::class)]
#[CoversClass(IntersectionTypeResolver::class)]
#[CoversClass(MemberResolver::class)]
#[CoversClass(ParameterTypeResolver::class)]
final class ContainerAutowireTest extends TestCase
{
    public function testAutowireRegistersClassByName(): void
    {
        $container = new Container();
        $container->autowire(SimpleService::class);

        self::assertTrue($container->hasDefinition(SimpleService::class));
        self::assertInstanceOf(SimpleService::class, $container->get(SimpleService::class));
    }

    public function testEnableAutowiringResolvesUnregisteredClasses(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        self::assertTrue($container->isAutowiringEnabled());
        self::assertTrue($container->has(SimpleService::class));
        self::assertInstanceOf(SimpleService::class, $container->get(SimpleService::class));
    }

    public function testAutowireResolvesConstructorDependencies(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(LoggerUser::class);

        self::assertInstanceOf(LoggerUser::class, $service);
        self::assertTrue($container->has(Clock::class));
    }

    public function testAutowireInjectsContainerInterface(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $consumer = $container->get(ContainerConsumer::class);

        self::assertInstanceOf(ContainerConsumer::class, $consumer);
        self::assertSame($container, $consumer->container);
    }

    public function testAutowireCachesSingleton(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        self::assertSame($container->get(Clock::class), $container->get(Clock::class));
    }

    public function testDisableAutowiringRestoresExplicitOnlyMode(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->disableAutowiring();

        self::assertFalse($container->isAutowiringEnabled());
        self::assertFalse($container->has(SimpleService::class));
    }

    public function testHasReturnsFalseForNonClassIdentifierWhenAutowiringEnabled(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        self::assertFalse($container->has('not.a.real.class'));
    }
}
