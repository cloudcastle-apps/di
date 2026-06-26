<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\PropertyInjector;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\ManualInitPropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\StaticAndInstancePropertyService;
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
}
