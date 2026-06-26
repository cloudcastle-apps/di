<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\ParameterTypeResolver;
use CloudCastle\DI\Tests\Fixtures\Autowire\BuiltinPropertyHolder;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\NullablePropertyHolder;
use CloudCastle\DI\Tests\Fixtures\Autowire\NullableUnionPropertyHolder;
use CloudCastle\DI\Tests\Fixtures\Autowire\TypedPropertyHolder;
use CloudCastle\DI\Tests\Fixtures\Autowire\UnionPropertyHolder;
use CloudCastle\DI\Tests\Fixtures\Autowire\UntypedPropertyHolder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Разрешение typed properties через {@see ParameterTypeResolver}.
 */
#[CoversClass(ParameterTypeResolver::class)]
final class ParameterTypeResolverPropertyTest extends TestCase
{
    public function testResolvePropertyThrowsWhenPropertyHasNoType(): void
    {
        $resolver = new ParameterTypeResolver(new Container());
        $property = new ReflectionProperty(UntypedPropertyHolder::class, 'value');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Не удалось разрешить свойство $value.');

        $resolver->resolveProperty($property);
    }

    public function testResolvePropertyReturnsNullForNullablePropertyWithoutDefinition(): void
    {
        $resolver = new ParameterTypeResolver(new Container());
        $property = new ReflectionProperty(NullablePropertyHolder::class, 'clock');

        self::assertNull($resolver->resolveProperty($property));
    }

    public function testResolvePropertyResolvesNamedObjectType(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);

        $resolver = new ParameterTypeResolver($container);
        $property = new ReflectionProperty(TypedPropertyHolder::class, 'clock');

        self::assertSame($clock, $resolver->resolveProperty($property));
    }

    public function testResolvePropertyThrowsForBuiltinPropertyType(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::never())->method('hasDefinition');

        $resolver = new ParameterTypeResolver($container);
        $property = new ReflectionProperty(BuiltinPropertyHolder::class, 'count');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Не удалось разрешить свойство $count.');

        $resolver->resolveProperty($property);
    }

    public function testResolvePropertyUsesFirstResolvableUnionMember(): void
    {
        $clock = new Clock();
        $container = new Container();
        $container->set(Clock::class, $clock);

        $resolver = new ParameterTypeResolver($container);
        $property = new ReflectionProperty(UnionPropertyHolder::class, 'dependency');
        $type = $property->getType();

        self::assertInstanceOf(ReflectionUnionType::class, $type);
        self::assertSame($clock, $resolver->resolveProperty($property));
    }

    public function testResolvePropertyThrowsForNonNullableUnionWithoutDefinition(): void
    {
        $resolver = new ParameterTypeResolver(new Container());
        $property = new ReflectionProperty(UnionPropertyHolder::class, 'dependency');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Не удалось разрешить свойство $dependency.');

        $resolver->resolveProperty($property);
    }

    public function testResolvePropertyReturnsNullForNullableUnionWithoutDefinition(): void
    {
        $resolver = new ParameterTypeResolver(new Container());
        $property = new ReflectionProperty(NullableUnionPropertyHolder::class, 'dependency');

        self::assertNull($resolver->resolveProperty($property));
    }

    public function testResolvePropertyThrowsForMissingNamedObjectType(): void
    {
        $resolver = new ParameterTypeResolver(new Container());
        $property = new ReflectionProperty(TypedPropertyHolder::class, 'clock');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Не удалось разрешить свойство $clock.');

        $resolver->resolveProperty($property);
    }
}
