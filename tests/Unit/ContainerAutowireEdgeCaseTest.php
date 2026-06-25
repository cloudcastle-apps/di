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
use CloudCastle\DI\Tests\Fixtures\Autowire\AbstractWorker;
use CloudCastle\DI\Tests\Fixtures\Autowire\CircularA;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\IntersectionParameterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\OptionalDependency;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use CloudCastle\DI\Tests\Fixtures\Autowire\UnionParameterService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Граничные случаи autowiring.
 */
#[CoversClass(Container::class)]
#[CoversClass(Autowirer::class)]
#[CoversClass(ClassDependencyResolver::class)]
#[CoversClass(IntersectionTypeResolver::class)]
#[CoversClass(MemberResolver::class)]
#[CoversClass(ParameterTypeResolver::class)]
final class ContainerAutowireEdgeCaseTest extends TestCase
{
    public function testAutowireDetectsCircularDependency(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('циклическая зависимость');

        $container->get(CircularA::class);
    }

    public function testAutowireThrowsForMissingClass(): void
    {
        $container = new Container();

        /** @var string $missingClass */
        $missingClass = SimpleService::class . 'Missing';

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $container->autowire($missingClass);
    }

    public function testAutowireThrowsForAbstractClass(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('нельзя создать');

        $container->autowire(AbstractWorker::class);
    }

    public function testAutowireResolvesNullableOptionalDependency(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(OptionalDependency::class);

        self::assertInstanceOf(OptionalDependency::class, $service);
        self::assertNull($service->clock);
    }

    public function testAutowireInjectsOptionalDependencyWhenExplicitlyRegistered(): void
    {
        $container = new Container();
        $clock = new Clock();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();

        $service = $container->get(OptionalDependency::class);

        self::assertInstanceOf(OptionalDependency::class, $service);
        self::assertSame($clock, $service->clock);
    }

    public function testAutowireResolvesUnionTypeWithRegisteredDependency(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(UnionParameterService::class);

        self::assertInstanceOf(UnionParameterService::class, $service);
        self::assertNotNull($service->clock);
    }

    public function testAutowireThrowsWhenIntersectionCannotBeResolved(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('intersection-тип');

        $container->get(IntersectionParameterService::class);
    }

    public function testEnableParameterNameAutowiringToggle(): void
    {
        $container = new Container();

        self::assertFalse($container->isParameterNameAutowiringEnabled());

        $container->enableParameterNameAutowiring();
        self::assertTrue($container->isParameterNameAutowiringEnabled());

        $container->disableParameterNameAutowiring();
        self::assertFalse($container->isParameterNameAutowiringEnabled());
    }
}
