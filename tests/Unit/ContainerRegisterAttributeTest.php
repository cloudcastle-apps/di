<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomAttributeConstructorService;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomAttributePropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomAttributeTypedPropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\UnrelatedAttribute;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(Container::class)]
final class ContainerRegisterAttributeTest extends TestCase
{
    public function testRegisterAttributeEnablesPropertyInjection(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->registerAttribute(CustomServiceIdAttribute::class);
        $container->set('app.clock', $clock);
        $container->autowire(CustomAttributePropertyService::class);

        $service = $container->get(CustomAttributePropertyService::class);

        self::assertInstanceOf(CustomAttributePropertyService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testRegisterAttributeEnablesConstructorInjection(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->registerAttribute(CustomServiceIdAttribute::class);
        $container->set('app.clock', $clock);
        $container->enableAutowiring();

        $service = $container->get(CustomAttributeConstructorService::class);

        self::assertInstanceOf(CustomAttributeConstructorService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testCustomAttributeWithoutIdFallsBackToType(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->registerAttribute(CustomServiceIdAttribute::class);
        $container->set(Clock::class, $clock);
        $container->autowire(CustomAttributeTypedPropertyService::class);

        $service = $container->get(CustomAttributeTypedPropertyService::class);

        self::assertInstanceOf(CustomAttributeTypedPropertyService::class, $service);
        self::assertSame($clock, $service->getClock());
    }

    public function testCallResolvesCustomAttributeAfterRegister(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->registerAttribute(CustomServiceIdAttribute::class);
        $container->set('app.clock', $clock);

        $resolved = $container->call(
            static fn (
                #[CustomServiceIdAttribute(service: 'app.clock')]
                Clock $clock,
            ): Clock => $clock,
        );

        self::assertSame($clock, $resolved);
    }

    public function testUnregisteredCustomAttributeIsIgnored(): void
    {
        $container = new Container();
        $container->autowire(CustomAttributePropertyService::class);

        $service = $container->get(CustomAttributePropertyService::class);

        self::assertInstanceOf(CustomAttributePropertyService::class, $service);

        $clockProperty = new ReflectionProperty(CustomAttributePropertyService::class, 'clock');

        self::assertFalse($clockProperty->isInitialized($service));
    }

    public function testRegisterAttributeRejectsInvalidClass(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);

        $container->registerAttribute(UnrelatedAttribute::class);
    }

    public function testRegisterAttributeRejectsMissingClass(): void
    {
        $container = new Container();

        /** @var string $missingAttribute */
        $missingAttribute = CustomServiceIdAttribute::class . 'Missing';

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $container->registerAttribute($missingAttribute);
    }

    public function testFreezeBlocksRegisterAttribute(): void
    {
        $container = new Container();
        $container->freeze();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('заморожен');

        $container->registerAttribute(CustomServiceIdAttribute::class);
    }
}
