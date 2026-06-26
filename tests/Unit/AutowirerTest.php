<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\AttributeServiceIdRegistry;
use CloudCastle\DI\Autowirer;
use CloudCastle\DI\ClassDependencyResolver;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\IntersectionTypeResolver;
use CloudCastle\DI\MemberResolver;
use CloudCastle\DI\ParameterTypeResolver;
use CloudCastle\DI\Tests\Fixtures\Autowire\AbstractWorker;
use CloudCastle\DI\Tests\Fixtures\Autowire\BuiltinParameterService;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomAttributePropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\DualTypeService;
use CloudCastle\DI\Tests\Fixtures\Autowire\NullableWithoutDefinitionService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use CloudCastle\DI\Tests\Fixtures\Autowire\UntypedParameterService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Базовые сценарии {@see Autowirer}.
 */
#[CoversClass(Autowirer::class)]
#[CoversClass(ClassDependencyResolver::class)]
#[CoversClass(IntersectionTypeResolver::class)]
#[CoversClass(MemberResolver::class)]
#[CoversClass(ParameterTypeResolver::class)]
final class AutowirerTest extends TestCase
{
    public function testInstantiateThrowsWhenClassIsMissing(): void
    {
        $autowirer = new Autowirer(new Container());

        /** @var string $missingClass */
        $missingClass = SimpleService::class . 'Missing';

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $autowirer->instantiate($missingClass);
    }

    public function testInstantiateThrowsWhenClassIsNotInstantiable(): void
    {
        $autowirer = new Autowirer(new Container());

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('нельзя создать');

        $autowirer->instantiate(AbstractWorker::class);
    }

    public function testInstantiateThrowsForUntypedRequiredParameter(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Не удалось разрешить параметр');

        $container->get(UntypedParameterService::class);
    }

    public function testInstantiateUsesBuiltinDefaultValue(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(BuiltinParameterService::class);

        self::assertInstanceOf(BuiltinParameterService::class, $service);
        self::assertSame('default', $service->label);
    }

    public function testInstantiateResolvesUnionType(): void
    {
        $container = new Container();
        $container->enableAutowiring();

        $service = $container->get(DualTypeService::class);

        self::assertInstanceOf(DualTypeService::class, $service);
        self::assertInstanceOf(Clock::class, $service->value);
    }

    public function testInstantiateReturnsNullForNullableDependencyWithoutDefinition(): void
    {
        $container = new Container();
        $container->autowire(NullableWithoutDefinitionService::class);

        $service = $container->get(NullableWithoutDefinitionService::class);

        self::assertInstanceOf(NullableWithoutDefinitionService::class, $service);
        self::assertNull($service->clock);
    }

    public function testInstantiateUsesInjectedAttributeReader(): void
    {
        $clock = new Clock();
        $registry = new AttributeServiceIdRegistry();
        $registry->register(CustomServiceIdAttribute::class);

        $reader = new AttributeServiceIdReader($registry);
        $container = new Container();
        $container->set('app.clock', $clock);

        $autowirer = new Autowirer($container, $reader);

        $service = $autowirer->instantiate(CustomAttributePropertyService::class);

        $clockProperty = new ReflectionProperty(CustomAttributePropertyService::class, 'clock');

        self::assertTrue($clockProperty->isInitialized($service));
        self::assertSame($clock, $clockProperty->getValue($service));
    }
}
