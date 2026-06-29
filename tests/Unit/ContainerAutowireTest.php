<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Autowirer;
use CloudCastle\DI\ClassDependencyResolver;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\IntersectionTypeResolver;
use CloudCastle\DI\MemberResolver;
use CloudCastle\DI\ParameterTypeResolver;
use CloudCastle\DI\Tests\Fixtures\Autowire\CircularA;
use CloudCastle\DI\Tests\Fixtures\Autowire\CircularB;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\ContainerConsumer;
use CloudCastle\DI\Tests\Fixtures\Autowire\IntClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerUser;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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

    public function testAutowireUsesExplicitDependencyWithoutGlobalAutowiring(): void
    {
        $container = new Container();
        $clock = new Clock();
        $container->set(Clock::class, $clock);
        $container->autowire(LoggerUser::class);

        $service = $container->get(LoggerUser::class);

        self::assertInstanceOf(LoggerUser::class, $service);
        self::assertSame($clock, $service->clock);
    }

    #[Group('circular-slow')]
    public function testCircularDependencyAllowsRetryAfterFailure(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        try {
            $container->get(CircularA::class);
            self::fail('Ожидалось исключение ContainerException.');
        } catch (ContainerException $containerException) {
            self::assertStringContainsString('циклическая зависимость', $containerException->getMessage());
        }

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая зависимость');

        $container->get(CircularB::class);
    }

    #[Group('circular-slow')]
    public function testCircularDependencyDoesNotBlockUnrelatedResolution(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        try {
            $container->get(CircularA::class);
            self::fail('Ожидалось исключение ContainerException.');
        } catch (ContainerException $containerException) {
            self::assertStringContainsString('циклическая зависимость', $containerException->getMessage());
        }

        $reflection = new ReflectionClass(Container::class);
        $resolving = $reflection->getProperty('resolving');

        self::assertSame([], $resolving->getValue($container));
        self::assertInstanceOf(SimpleService::class, $container->get(SimpleService::class));
    }

    public function testAutowirerIsCachedBetweenResolutions(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->get(SimpleService::class);
        $container->get(Clock::class);

        $reflection = new ReflectionClass(Container::class);
        $property = $reflection->getProperty('autowirer');
        /** @var Autowirer|null $first */
        $first = $property->getValue($container);

        $container->get(LoggerUser::class);

        self::assertSame($first, $property->getValue($container));
    }

    public function testResolveAutowiredThrowsWhenServiceAlreadyResolving(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $reflection = new ReflectionClass(Container::class);
        $resolving = $reflection->getProperty('resolving');
        $resolving->setValue($container, [SimpleService::class => true]);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая зависимость');

        $container->make(SimpleService::class);
    }

    public function testInstantiateResolvesIntClockUnionSkippingBuiltinFirst(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(IntClockService::class);

        self::assertInstanceOf(IntClockService::class, $service);
        self::assertInstanceOf(Clock::class, $service->value);
    }

    public function testResolvingStackIsClearedAfterSuccessfulAutowired(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->get(SimpleService::class);

        $reflection = new ReflectionClass(Container::class);
        $resolving = $reflection->getProperty('resolving');

        self::assertSame([], $resolving->getValue($container));
    }
}
