<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\PropertyInjector;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\ManualInitPropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\PromotedAndPlainPropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\StaticAndInstancePropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\UntypedPropertyHolder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Mutation-тесты для {@see PropertyInjector}.
 */
#[CoversClass(PropertyInjector::class)]
final class PropertyInjectorMutationTest extends TestCase
{
    public function testPropertyAutowiringSkipsStaticPropertyButInjectsInstance(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enablePropertyAutowiring();
        $container->autowire(StaticAndInstancePropertyService::class);

        $service = $container->get(StaticAndInstancePropertyService::class);

        self::assertInstanceOf(StaticAndInstancePropertyService::class, $service);
        self::assertNull(StaticAndInstancePropertyService::getStaticClock());
        self::assertSame($clock, $service->getInstanceClock());
    }

    public function testPropertyAutowiringSkipsManuallyInitializedProperty(): void
    {
        $containerClock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $containerClock);
        $container->enablePropertyAutowiring();
        $container->autowire(ManualInitPropertyService::class);

        $service = $container->get(ManualInitPropertyService::class);

        self::assertInstanceOf(ManualInitPropertyService::class, $service);
        self::assertNotSame($containerClock, $service->getPlain());
    }

    public function testPropertyAutowiringSkipsPromotedButInjectsPlainTypedProperty(): void
    {
        $containerClock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $containerClock);
        $container->enablePropertyAutowiring();
        $container->autowire(PromotedAndPlainPropertyService::class);

        $service = $container->get(PromotedAndPlainPropertyService::class);

        self::assertInstanceOf(PromotedAndPlainPropertyService::class, $service);
        self::assertSame($containerClock, $service->getPlain());
        self::assertSame($containerClock, $service->getPromoted());
    }

    public function testPropertyAutowiringSkipsUntypedPropertyWithoutAttributes(): void
    {
        $container = new Container();
        $container->enablePropertyAutowiring();
        $container->autowire(UntypedPropertyHolder::class);

        $holder = $container->get(UntypedPropertyHolder::class);

        self::assertInstanceOf(UntypedPropertyHolder::class, $holder);
        self::assertNull($holder->getValue());
    }
}
