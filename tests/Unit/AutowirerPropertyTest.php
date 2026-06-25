<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use ArrayIterator;
use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\Autowirer;
use CloudCastle\DI\Container;
use CloudCastle\DI\MemberResolver;
use CloudCastle\DI\ParameterTypeResolver;
use CloudCastle\DI\PropertyInjector;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\NullablePropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\PromotedPropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\PropertyAutowireAttributeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\PropertyInjectAttributeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\PropertyIntersectionService;
use CloudCastle\DI\Tests\Fixtures\Autowire\PropertyUnionService;
use CloudCastle\DI\Tests\Fixtures\Autowire\TypedPropertyService;
use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Autowiring свойств.
 */
#[CoversClass(Container::class)]
#[CoversClass(Autowirer::class)]
#[CoversClass(AttributeServiceIdReader::class)]
#[CoversClass(MemberResolver::class)]
#[CoversClass(ParameterTypeResolver::class)]
#[CoversClass(PropertyInjector::class)]
final class AutowirerPropertyTest extends TestCase
{
    public function testInstantiateInjectsPropertyWithInjectAttribute(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set('app.clock', $clock);
        $container->autowire(PropertyInjectAttributeService::class);

        $service = $container->get(PropertyInjectAttributeService::class);

        self::assertInstanceOf(PropertyInjectAttributeService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testInstantiateInjectsTypedPropertyWhenPropertyAutowiringEnabled(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();
        $container->enablePropertyAutowiring();

        $service = $container->get(TypedPropertyService::class);

        self::assertInstanceOf(TypedPropertyService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testPropertyAutowiringToggle(): void
    {
        $container = new Container();

        self::assertFalse($container->isPropertyAutowiringEnabled());

        $container->enablePropertyAutowiring();

        self::assertTrue($container->isPropertyAutowiringEnabled());

        $container->disablePropertyAutowiring();

        self::assertFalse($container->isPropertyAutowiringEnabled());
    }

    public function testInstantiateInjectsPropertyWithAutowireAttribute(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set('app.clock', $clock);
        $container->autowire(PropertyAutowireAttributeService::class);

        $service = $container->get(PropertyAutowireAttributeService::class);

        self::assertInstanceOf(PropertyAutowireAttributeService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testInstantiateInjectsNullablePropertyAsNull(): void
    {
        $container = new Container();
        $container->enablePropertyAutowiring();
        $container->autowire(NullablePropertyService::class);

        $service = $container->get(NullablePropertyService::class);

        self::assertInstanceOf(NullablePropertyService::class, $service);
        self::assertNull($service->getClock());
    }

    public function testInstantiateInjectsUnionTypedProperty(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enablePropertyAutowiring();
        $container->autowire(PropertyUnionService::class);

        $service = $container->get(PropertyUnionService::class);

        self::assertInstanceOf(PropertyUnionService::class, $service);
        self::assertSame($clock, $service->getDependency());
    }

    public function testInstantiateUsesPromotedPropertyWithoutDuplicateInjection(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enablePropertyAutowiring();
        $container->autowire(PromotedPropertyService::class);

        $service = $container->get(PromotedPropertyService::class);

        self::assertInstanceOf(PromotedPropertyService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testInstantiateInjectsIntersectionTypedProperty(): void
    {
        $storage = new ArrayIterator(['value']);
        $container = new Container();
        $container->set(Iterator::class, $storage);
        $container->enablePropertyAutowiring();
        $container->autowire(PropertyIntersectionService::class);

        $service = $container->get(PropertyIntersectionService::class);

        self::assertInstanceOf(PropertyIntersectionService::class, $service);
        self::assertSame($storage, $service->getStorage());
    }
}
