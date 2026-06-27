<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use ArrayIterator;
use CloudCastle\DI\Attribute\Autowire;
use CloudCastle\DI\Attribute\Inject;
use CloudCastle\DI\Autowirer;
use CloudCastle\DI\ClassDependencyResolver;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\IntersectionTypeResolver;
use CloudCastle\DI\MemberResolver;
use CloudCastle\DI\ParameterTypeResolver;
use CloudCastle\DI\Tests\Fixtures\Autowire\AutowireNullAttributeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\ContainerIteratorIntersectionService;
use CloudCastle\DI\Tests\Fixtures\Autowire\DeprecatedAttributeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\InjectNullAttributeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\IntClockOnlyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\IntersectionParameterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\IteratorOnly;
use CloudCastle\DI\Tests\Fixtures\Autowire\NullableIntersectionService;
use CloudCastle\DI\Tests\Fixtures\Autowire\PsrContainerConsumer;
use Countable;
use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Дополнительные сценарии autowiring: attributes, intersection, PSR-11.
 */
#[CoversClass(Autowirer::class)]
#[CoversClass(ClassDependencyResolver::class)]
#[CoversClass(IntersectionTypeResolver::class)]
#[CoversClass(MemberResolver::class)]
#[CoversClass(ParameterTypeResolver::class)]
#[CoversClass(Inject::class)]
#[CoversClass(Autowire::class)]
final class AutowirerExtendedTest extends TestCase
{
    public function testInstantiateFallsBackToTypeWhenInjectAttributeHasNullId(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();
        $container->autowire(InjectNullAttributeService::class);

        $service = $container->get(InjectNullAttributeService::class);

        self::assertInstanceOf(InjectNullAttributeService::class, $service);
        self::assertSame($clock, $service->clock);
    }

    public function testInstantiateFallsBackToTypeWhenAutowireAttributeHasNullService(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();
        $container->autowire(AutowireNullAttributeService::class);

        $service = $container->get(AutowireNullAttributeService::class);

        self::assertInstanceOf(AutowireNullAttributeService::class, $service);
        self::assertSame($clock, $service->clock);
    }

    public function testInstantiateIgnoresUnrelatedParameterAttributes(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();
        $container->autowire(DeprecatedAttributeService::class);

        $service = $container->get(DeprecatedAttributeService::class);

        self::assertInstanceOf(DeprecatedAttributeService::class, $service);
        self::assertSame($clock, $service->clock);
    }

    public function testInstantiateResolvesIntersectionUsingSecondMatchingCandidate(): void
    {
        $iteratorOnly = new IteratorOnly(['x']);
        $storage = new ArrayIterator(['a']);
        $container = new Container();
        $container->set(Iterator::class, $iteratorOnly);
        $container->set(Countable::class, $storage);
        $container->autowire(IntersectionParameterService::class);

        $service = $container->get(IntersectionParameterService::class);

        self::assertInstanceOf(IntersectionParameterService::class, $service);
        self::assertSame($storage, $service->storage);
    }

    public function testInstantiateResolvesIntersectionWhenFirstMemberIsUnavailable(): void
    {
        $storage = new ArrayIterator(['item']);
        $container = new Container();
        $container->set(Countable::class, $storage);
        $container->autowire(IntersectionParameterService::class);

        $service = $container->get(IntersectionParameterService::class);

        self::assertInstanceOf(IntersectionParameterService::class, $service);
        self::assertSame($storage, $service->storage);
    }

    public function testInstantiateThrowsWhenIntersectionCandidateIsNotContainer(): void
    {
        $storage = new ArrayIterator([]);
        $container = new Container();
        $container->set(\CloudCastle\DI\Contract\ContainerInterface::class, $storage);
        $container->autowire(ContainerIteratorIntersectionService::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('intersection-тип');

        $container->get(ContainerIteratorIntersectionService::class);
    }

    public function testInstantiateSkipsIteratorCandidateThatFailsContainerInterfaceMember(): void
    {
        $container = new Container();
        $container->set(Iterator::class, new IteratorOnly([]));
        $container->autowire(ContainerIteratorIntersectionService::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('intersection-тип');

        $container->get(ContainerIteratorIntersectionService::class);
    }

    public function testInstantiateInjectsPsrContainerInterface(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $consumer = $container->get(PsrContainerConsumer::class);

        self::assertInstanceOf(PsrContainerConsumer::class, $consumer);
        self::assertSame($container, $consumer->container);
    }

    public function testInstantiateUsesDefaultForNullableOptionalDependency(): void
    {
        $container = new Container();
        $container->autowire(NullableIntersectionService::class);

        $service = $container->get(NullableIntersectionService::class);

        self::assertInstanceOf(NullableIntersectionService::class, $service);
        self::assertNull($service->storage);
    }

    public function testInstantiateResolvesUnionAfterSkippingLeadingBuiltinWithoutDefault(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();

        $service = $container->get(IntClockOnlyService::class);

        self::assertInstanceOf(IntClockOnlyService::class, $service);
        self::assertSame($clock, $service->dependency);
    }
}
