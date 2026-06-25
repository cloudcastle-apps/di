<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\Autowirer;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\IntersectionTypeResolver;
use CloudCastle\DI\MethodInjector;
use CloudCastle\DI\PropertyInjector;
use CloudCastle\DI\Tests\Fixtures\Autowire\AttributeReaderFixtures;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\ConstructCountService;
use CloudCastle\DI\Tests\Fixtures\Autowire\NoopMethodService;
use CloudCastle\DI\Tests\Fixtures\Autowire\PropertyIntersectionService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SetterInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\StaticThrowMethodService;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Тесты для mutation score property/method injection.
 */
#[CoversClass(AttributeServiceIdReader::class)]
#[CoversClass(Autowirer::class)]
#[CoversClass(Container::class)]
#[CoversClass(IntersectionTypeResolver::class)]
#[CoversClass(MethodInjector::class)]
#[CoversClass(PropertyInjector::class)]
final class PropertyMethodInjectionMutationTest extends TestCase
{
    #[Override]
    protected function tearDown(): void
    {
        ConstructCountService::$constructCount = 0;
    }

    public function testMethodAutowiringDisabledSkipsSetterWithoutAttributes(): void
    {
        $container = new Container();
        $container->autowire(SetterInjectService::class);

        $service = $container->get(SetterInjectService::class);

        self::assertInstanceOf(SetterInjectService::class, $service);
        self::assertFalse((new ReflectionProperty(SetterInjectService::class, 'clock'))->isInitialized($service));
    }

    public function testMethodAutowiringDoesNotInvokeStaticInjectMethod(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableMethodAutowiring();
        $container->autowire(StaticThrowMethodService::class);

        $service = $container->get(StaticThrowMethodService::class);

        self::assertInstanceOf(StaticThrowMethodService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testMethodAutowiringDoesNotInvokeNoopMethod(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableMethodAutowiring();
        $container->autowire(NoopMethodService::class);

        $service = $container->get(NoopMethodService::class);

        self::assertInstanceOf(NoopMethodService::class, $service);
        self::assertFalse($service->noopCalled);
        self::assertSame($clock, $service->getClock());
    }

    public function testMethodAutowiringDoesNotReinvokeConstructor(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableMethodAutowiring();
        $container->autowire(ConstructCountService::class);

        $container->get(ConstructCountService::class);

        self::assertSame(1, ConstructCountService::$constructCount);
    }

    public function testPropertyIntersectionWithoutDefinitionThrows(): void
    {
        $container = new Container();
        $container->enablePropertyAutowiring();
        $container->autowire(PropertyIntersectionService::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Не удалось разрешить intersection-тип для свойства $storage.');

        $container->get(PropertyIntersectionService::class);
    }

    public function testAttributeReaderHasAnyReturnsFalseForUnrelatedAttributes(): void
    {
        $reader = new AttributeServiceIdReader();
        $property = new ReflectionProperty(AttributeReaderFixtures::class, 'unrelated');

        self::assertFalse($reader->hasAny($property->getAttributes()));
    }

    public function testAttributeReaderReadReturnsInjectIdFromProperty(): void
    {
        $reader = new AttributeServiceIdReader();
        $property = new ReflectionProperty(AttributeReaderFixtures::class, 'withInjectWithoutId');

        self::assertNull($reader->read($property->getAttributes()));
        self::assertTrue($reader->hasAny($property->getAttributes()));
    }
}
