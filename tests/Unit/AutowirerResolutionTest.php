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
use CloudCastle\DI\Tests\Fixtures\Autowire\BuiltinUnionService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\IntClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\IntLoggerOrClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\LegacyUntypedService;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerOrClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\NullOrClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\RequiredClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\StringClockService;
use CloudCastle\DI\Tests\Fixtures\Autowire\StringOrNullService;
use CloudCastle\DI\Tests\Fixtures\Autowire\UntypedDefaultService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Расширенные сценарии разрешения параметров {@see Autowirer}.
 */
#[CoversClass(Autowirer::class)]
#[CoversClass(ClassDependencyResolver::class)]
#[CoversClass(IntersectionTypeResolver::class)]
#[CoversClass(MemberResolver::class)]
#[CoversClass(ParameterTypeResolver::class)]
final class AutowirerResolutionTest extends TestCase
{
    public function testInstantiateUsesLegacyUntypedDefaultValue(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(LegacyUntypedService::class);

        self::assertInstanceOf(LegacyUntypedService::class, $service);
        self::assertSame('legacy', $service->value);
    }

    public function testInstantiateUsesUntypedDefaultValue(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(UntypedDefaultService::class);

        self::assertInstanceOf(UntypedDefaultService::class, $service);
        self::assertSame('default', $service->value);
    }

    public function testInstantiateResolvesNullUnionTypeWhenDependencyMissing(): void
    {
        $container = new Container();
        $container->autowire(NullOrClockService::class);

        $service = $container->get(NullOrClockService::class);

        self::assertInstanceOf(NullOrClockService::class, $service);
        self::assertNull($service->clock);
    }

    public function testInstantiateUsesUnionDefaultValue(): void
    {
        $container = new Container();
        $container->autowire(StringOrNullService::class);

        $service = $container->get(StringOrNullService::class);

        self::assertInstanceOf(StringOrNullService::class, $service);
        self::assertSame('default', $service->label);
    }

    public function testInstantiateResolvesIntClockUnionType(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(IntClockService::class);

        self::assertInstanceOf(IntClockService::class, $service);
        self::assertInstanceOf(Clock::class, $service->value);
    }

    public function testInstantiateResolvesStringClockUnionType(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(StringClockService::class);

        self::assertInstanceOf(StringClockService::class, $service);
        self::assertInstanceOf(Clock::class, $service->value);
    }

    public function testInstantiateUsesBuiltinUnionDefaultValue(): void
    {
        $container = new Container();
        $container->autowire(BuiltinUnionService::class);

        $service = $container->get(BuiltinUnionService::class);

        self::assertInstanceOf(BuiltinUnionService::class, $service);
        self::assertSame('200', $service->code);
    }

    public function testInstantiateResolvesUnionUsingSecondObjectType(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();

        $service = $container->get(LoggerOrClockService::class);

        self::assertInstanceOf(LoggerOrClockService::class, $service);
        self::assertSame($clock, $service->dependency);
    }

    public function testInstantiateResolvesUnionAfterSkippingBuiltinType(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);
        $container->enableAutowiring();

        $service = $container->get(IntLoggerOrClockService::class);

        self::assertInstanceOf(IntLoggerOrClockService::class, $service);
        self::assertSame($clock, $service->dependency);
    }

    public function testInstantiateFailsWhenDependencyCannotBeResolved(): void
    {
        $container = new Container();
        $container->autowire(RequiredClockService::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Не удалось разрешить параметр');

        $container->get(RequiredClockService::class);
    }
}
