<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\Autowirer;
use CloudCastle\DI\Container;
use CloudCastle\DI\MemberResolver;
use CloudCastle\DI\MethodInjector;
use CloudCastle\DI\Tests\Fixtures\Autowire\ChildSetterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\MagicMethodInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\MethodInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\MethodParameterInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\ParentSetterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SetterInjectService;
use CloudCastle\DI\Tests\Fixtures\Autowire\StaticMethodInjectService;
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
}
