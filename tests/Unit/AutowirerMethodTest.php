<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\AttributeServiceIdRegistry;
use CloudCastle\DI\Autowirer;
use CloudCastle\DI\Container;
use CloudCastle\DI\MemberResolver;
use CloudCastle\DI\MethodInjector;
use CloudCastle\DI\Tests\Fixtures\Autowire\ChildSetterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\ConstructCountService;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\MagicMethodInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\MethodInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\MethodParameterInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\NoopMethodService;
use CloudCastle\DI\Tests\Fixtures\Autowire\ParentSetterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SetterInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\StaticMethodInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\StaticThrowMethodService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Autowiring методов.
 */
#[CoversClass(Container::class)]
#[CoversClass(Autowirer::class)]
#[CoversClass(AttributeServiceIdReader::class)]
#[CoversClass(MemberResolver::class)]
#[CoversClass(MethodInjector::class)]
final class AutowirerMethodTest extends TestCase
{
    protected function tearDown(): void
    {
        ConstructCountService::$constructCount = 0;
    }

    public function testInstantiateCallsInjectMethodWithAttribute(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();

        $service = $container->get(MethodInjectService::class);

        self::assertInstanceOf(MethodInjectService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testInstantiateCallsSetterWhenMethodAutowiringEnabled(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableMethodAutowiring();
        $container->autowire(SetterInjectService::class);

        $service = $container->get(SetterInjectService::class);

        self::assertInstanceOf(SetterInjectService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testMethodAutowiringToggle(): void
    {
        $container = new Container();

        self::assertFalse($container->isMethodAutowiringEnabled());

        $container->enableMethodAutowiring();

        self::assertTrue($container->isMethodAutowiringEnabled());

        $container->disableMethodAutowiring();

        self::assertFalse($container->isMethodAutowiringEnabled());
    }

    public function testInstantiateCallsMethodWithParameterInjectAttribute(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();
        $container->autowire(MethodParameterInjectService::class);

        $service = $container->get(MethodParameterInjectService::class);

        self::assertInstanceOf(MethodParameterInjectService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testInstantiateSkipsParentSetterWhenMethodDeclaredInParent(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableMethodAutowiring();
        $container->autowire(ChildSetterService::class);

        $service = $container->get(ChildSetterService::class);

        self::assertInstanceOf(ChildSetterService::class, $service);
        self::assertFalse((new ReflectionProperty(ParentSetterService::class, 'clock'))->isInitialized($service));
    }

    public function testInstantiateSkipsStaticAndMagicMethods(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableMethodAutowiring();
        $container->autowire(StaticMethodInjectService::class);

        $staticService = $container->get(StaticMethodInjectService::class);

        self::assertInstanceOf(StaticMethodInjectService::class, $staticService);
        self::assertSame($clock, $staticService->getClock());

        $container->autowire(MagicMethodInjectService::class);

        $magicService = $container->get(MagicMethodInjectService::class);

        self::assertInstanceOf(MagicMethodInjectService::class, $magicService);
        self::assertSame($clock, $magicService->getClock());
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

    public function testMethodInjectorUsesProvidedAttributeReader(): void
    {
        $registry = new AttributeServiceIdRegistry();
        $registry->register(CustomServiceIdAttribute::class);
        $reader = new AttributeServiceIdReader($registry);
        $container = new Container();
        $injector = new MethodInjector($container, $reader);
        $property = new ReflectionProperty(MethodInjector::class, 'attributeReader');

        self::assertSame($reader, $property->getValue($injector));
    }
}
