<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use ArrayIterator;
use CloudCastle\DI\ClassDependencyResolver;
use CloudCastle\DI\Container;
use CloudCastle\DI\IntersectionTypeResolver;
use Countable;
use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

/**
 * Граничные ветки {@see IntersectionTypeResolver}, недостижимые через обычный autowiring.
 */
#[CoversClass(IntersectionTypeResolver::class)]
#[CoversClass(ClassDependencyResolver::class)]
final class IntersectionTypeResolverTest extends TestCase
{
    public function testResolveReturnsNullWhenNullableIntersectionCannotBeResolved(): void
    {
        $container = new Container();
        $classResolver = new ClassDependencyResolver($container);
        $resolver = new IntersectionTypeResolver($classResolver);

        $parameter = $this->createMock(ReflectionParameter::class);
        $parameter->method('allowsNull')->willReturn(true);
        $parameter->method('getName')->willReturn('storage');

        $iteratorType = $this->createMock(ReflectionNamedType::class);
        $iteratorType->method('isBuiltin')->willReturn(false);
        $iteratorType->method('getName')->willReturn(Iterator::class);

        $countableType = $this->createMock(ReflectionNamedType::class);
        $countableType->method('isBuiltin')->willReturn(false);
        $countableType->method('getName')->willReturn(Countable::class);

        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([$iteratorType, $countableType]);

        self::assertNull($resolver->resolve($parameter, $intersectionType));
    }

    public function testResolveSkipsCandidateThatIsNotObject(): void
    {
        $container = new Container();
        $container->set(Iterator::class, 'not-an-object');

        $classResolver = new ClassDependencyResolver($container);
        $resolver = new IntersectionTypeResolver($classResolver);

        $parameter = $this->createMock(ReflectionParameter::class);
        $parameter->method('allowsNull')->willReturn(true);
        $parameter->method('getName')->willReturn('storage');

        $iteratorType = $this->createMock(ReflectionNamedType::class);
        $iteratorType->method('isBuiltin')->willReturn(false);
        $iteratorType->method('getName')->willReturn(Iterator::class);

        $countableType = $this->createMock(ReflectionNamedType::class);
        $countableType->method('isBuiltin')->willReturn(false);
        $countableType->method('getName')->willReturn(Countable::class);

        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([$iteratorType, $countableType]);

        self::assertNull($resolver->resolve($parameter, $intersectionType));
    }

    public function testResolveIgnoresBuiltinTypesInIntersectionReflection(): void
    {
        $container = new Container();
        $classResolver = new ClassDependencyResolver($container);
        $resolver = new IntersectionTypeResolver($classResolver);

        $parameter = $this->createMock(ReflectionParameter::class);
        $parameter->method('allowsNull')->willReturn(true);
        $parameter->method('getName')->willReturn('value');

        $builtinType = $this->createMock(ReflectionNamedType::class);
        $builtinType->method('isBuiltin')->willReturn(true);

        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([$builtinType]);

        self::assertNull($resolver->resolve($parameter, $intersectionType));
    }

    public function testResolveUsesObjectTypeAfterBuiltinInIntersectionReflection(): void
    {
        $storage = new ArrayIterator(['value']);
        $container = new Container();
        $container->set(Iterator::class, $storage);

        $classResolver = new ClassDependencyResolver($container);
        $resolver = new IntersectionTypeResolver($classResolver);

        $parameter = $this->createMock(ReflectionParameter::class);
        $parameter->method('allowsNull')->willReturn(false);
        $parameter->method('getName')->willReturn('storage');

        $builtinType = $this->createMock(ReflectionNamedType::class);
        $builtinType->method('isBuiltin')->willReturn(true);

        $iteratorType = $this->createMock(ReflectionNamedType::class);
        $iteratorType->method('isBuiltin')->willReturn(false);
        $iteratorType->method('getName')->willReturn(Iterator::class);

        $countableType = $this->createMock(ReflectionNamedType::class);
        $countableType->method('isBuiltin')->willReturn(false);
        $countableType->method('getName')->willReturn(Countable::class);

        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([$builtinType, $iteratorType, $countableType]);

        self::assertSame($storage, $resolver->resolve($parameter, $intersectionType));
    }

    public function testResolveUsesPropertyAllowsNullWhenIntersectionMissing(): void
    {
        $container = new Container();
        $classResolver = new ClassDependencyResolver($container);
        $resolver = new IntersectionTypeResolver($classResolver);

        $property = $this->createMock(ReflectionProperty::class);
        $propertyType = $this->createMock(ReflectionType::class);
        $propertyType->method('allowsNull')->willReturn(true);
        $property->method('getType')->willReturn($propertyType);
        $property->method('getName')->willReturn('storage');

        $iteratorType = $this->createMock(ReflectionNamedType::class);
        $iteratorType->method('isBuiltin')->willReturn(false);
        $iteratorType->method('getName')->willReturn(Iterator::class);

        $countableType = $this->createMock(ReflectionNamedType::class);
        $countableType->method('isBuiltin')->willReturn(false);
        $countableType->method('getName')->willReturn(Countable::class);

        $intersectionType = $this->createMock(ReflectionIntersectionType::class);
        $intersectionType->method('getTypes')->willReturn([$iteratorType, $countableType]);

        self::assertNull($resolver->resolve($property, $intersectionType));
    }
}
