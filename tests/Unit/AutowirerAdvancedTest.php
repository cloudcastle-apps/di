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
use CloudCastle\DI\Tests\Fixtures\Autowire\AutowireAttributeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\InjectAttributeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\IntersectionParameterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\NamedLoggerConsumer;
use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Autowiring через attributes, intersection-типы и имя параметра.
 */
#[CoversClass(Autowirer::class)]
#[CoversClass(ClassDependencyResolver::class)]
#[CoversClass(IntersectionTypeResolver::class)]
#[CoversClass(MemberResolver::class)]
#[CoversClass(ParameterTypeResolver::class)]
#[CoversClass(Inject::class)]
#[CoversClass(Autowire::class)]
final class AutowirerAdvancedTest extends TestCase
{
    public function testInstantiateResolvesInjectAttributeByServiceId(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set('app.clock', $clock);
        $container->autowire(InjectAttributeService::class);

        $service = $container->get(InjectAttributeService::class);

        self::assertInstanceOf(InjectAttributeService::class, $service);
        self::assertSame($clock, $service->clock);
    }

    public function testInstantiateResolvesAutowireAttributeByServiceId(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set('app.clock', $clock);
        $container->autowire(AutowireAttributeService::class);

        $service = $container->get(AutowireAttributeService::class);

        self::assertInstanceOf(AutowireAttributeService::class, $service);
        self::assertSame($clock, $service->clock);
    }

    public function testInstantiateResolvesDependencyByParameterName(): void
    {
        $logger = new FileLogger();
        $container = new Container();
        $container->set('logger', $logger);
        $container->enableParameterNameAutowiring();
        $container->autowire(NamedLoggerConsumer::class);

        $service = $container->get(NamedLoggerConsumer::class);

        self::assertInstanceOf(NamedLoggerConsumer::class, $service);
        self::assertSame($logger, $service->logger);
    }

    public function testParameterNameAutowiringIsDisabledByDefault(): void
    {
        $logger = new FileLogger();
        $container = new Container();
        $container->set('logger', $logger);
        $container->autowire(NamedLoggerConsumer::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Не удалось разрешить параметр');

        $container->get(NamedLoggerConsumer::class);
    }

    public function testInstantiateResolvesIntersectionTypeWhenServiceImplementsAllTypes(): void
    {
        $storage = new ArrayIterator(['a', 'b']);
        $container = new Container();
        $container->set(Iterator::class, $storage);
        $container->autowire(IntersectionParameterService::class);

        $service = $container->get(IntersectionParameterService::class);

        self::assertInstanceOf(IntersectionParameterService::class, $service);
        self::assertSame($storage, $service->storage);
        self::assertCount(2, $service->storage);
    }

    public function testInstantiateResolvesIntersectionByParameterName(): void
    {
        $storage = new ArrayIterator([]);
        $container = new Container();
        $container->set('storage', $storage);
        $container->enableParameterNameAutowiring();
        $container->autowire(IntersectionParameterService::class);

        $service = $container->get(IntersectionParameterService::class);

        self::assertInstanceOf(IntersectionParameterService::class, $service);
        self::assertSame($storage, $service->storage);
    }

    public function testInstantiateThrowsWhenIntersectionCannotBeResolved(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->autowire(IntersectionParameterService::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('intersection-тип');

        $container->get(IntersectionParameterService::class);
    }

    public function testInjectAttributeHasPriorityOverParameterName(): void
    {
        $clock = new Clock();
        $decoy = new Clock();
        $container = new Container();
        $container->set('app.clock', $clock);
        $container->set('clock', $decoy);
        $container->enableParameterNameAutowiring();
        $container->autowire(InjectAttributeService::class);

        $service = $container->get(InjectAttributeService::class);

        self::assertInstanceOf(InjectAttributeService::class, $service);
        self::assertSame($clock, $service->clock);
    }
}
