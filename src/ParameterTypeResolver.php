<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

/**
 * Разрешает параметры конструктора и свойства по reflection-типам.
 */
final class ParameterTypeResolver
{
    private readonly ClassDependencyResolver $classResolver;

    private readonly IntersectionTypeResolver $intersection;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        $this->classResolver = new ClassDependencyResolver($container);
        $this->intersection = new IntersectionTypeResolver($this->classResolver);
    }

    /**
     * @throws ContainerException Если обязательный параметр не разрешается
     */
    public function resolve(ReflectionParameter $parameter): mixed
    {
        $consumerClass = $parameter->getDeclaringClass()?->getName();
        $type = $parameter->getType();

        if ($type === null) {
            return $this->resolveDefaultValue($parameter);
        }

        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnionParameter($parameter, $type, $consumerClass);
        }

        if ($type instanceof ReflectionIntersectionType) {
            return $this->intersection->resolve($parameter, $type, $consumerClass);
        }

        /** @var ReflectionNamedType $type */
        return $this->resolveNamedTypeParameter($parameter, $type, $consumerClass);
    }

    /**
     * @throws ContainerException Если обязательное свойство не разрешается
     */
    public function resolveProperty(ReflectionProperty $property): mixed
    {
        $consumerClass = $property->getDeclaringClass()->getName();
        $type = $property->getType();

        if ($type === null) {
            throw new ContainerException(\sprintf(
                'Не удалось разрешить свойство $%s.',
                $property->getName(),
            ));
        }

        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnionProperty($property, $type, $consumerClass);
        }

        if ($type instanceof ReflectionIntersectionType) {
            return $this->intersection->resolve($property, $type, $consumerClass);
        }

        /** @var ReflectionNamedType $type */
        return $this->resolveNamedTypeProperty($property, $type, $consumerClass);
    }

    private function resolveUnionParameter(
        ReflectionParameter $parameter,
        ReflectionUnionType $type,
        ?string $consumerClass,
    ): mixed {
        foreach ($this->filterObjectNamedTypes($type->getTypes()) as $namedType) {
            if ($this->classResolver->canResolve($namedType->getName(), $consumerClass)) {
                return $this->classResolver->resolve($namedType->getName(), $consumerClass);
            }
        }

        return $this->resolveDefaultValue($parameter);
    }

    private function resolveUnionProperty(
        ReflectionProperty $property,
        ReflectionUnionType $type,
        string $consumerClass,
    ): mixed {
        foreach ($this->filterObjectNamedTypes($type->getTypes()) as $namedType) {
            if ($this->classResolver->canResolve($namedType->getName(), $consumerClass)) {
                return $this->classResolver->resolve($namedType->getName(), $consumerClass);
            }
        }

        if ($type->allowsNull()) {
            return null;
        }

        throw new ContainerException(\sprintf(
            'Не удалось разрешить свойство $%s.',
            $property->getName(),
        ));
    }

    private function resolveNamedTypeParameter(
        ReflectionParameter $parameter,
        ReflectionNamedType $type,
        ?string $consumerClass,
    ): mixed {
        if ($type->isBuiltin()) {
            return $this->resolveDefaultValue($parameter);
        }

        $typeName = $type->getName();

        if ($parameter->isDefaultValueAvailable() && !$this->container->hasDefinition($typeName)) {
            return $parameter->getDefaultValue();
        }

        if ($this->classResolver->canResolve($typeName, $consumerClass)) {
            return $this->classResolver->resolve($typeName, $consumerClass);
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        return $this->resolveDefaultValue($parameter);
    }

    private function resolveNamedTypeProperty(
        ReflectionProperty $property,
        ReflectionNamedType $type,
        string $consumerClass,
    ): mixed {
        if ($type->isBuiltin()) {
            throw new ContainerException(\sprintf(
                'Не удалось разрешить свойство $%s.',
                $property->getName(),
            ));
        }

        $typeName = $type->getName();

        if ($this->classResolver->canResolve($typeName, $consumerClass)) {
            return $this->classResolver->resolve($typeName, $consumerClass);
        }

        if ($type->allowsNull()) {
            return null;
        }

        throw new ContainerException(\sprintf(
            'Не удалось разрешить свойство $%s.',
            $property->getName(),
        ));
    }

    /**
     * @throws ContainerException Если значение по умолчанию недоступно
     */
    private function resolveDefaultValue(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ContainerException(\sprintf(
            'Не удалось разрешить параметр $%s конструктора.',
            $parameter->getName(),
        ));
    }

    /**
     * @param array<ReflectionType> $types
     *
     * @return list<ReflectionNamedType>
     */
    private function filterObjectNamedTypes(array $types): array
    {
        /** @var list<ReflectionNamedType> $namedTypes */
        $namedTypes = [];

        foreach ($types as $reflectionType) {
            if (!$reflectionType instanceof ReflectionNamedType || $reflectionType->isBuiltin()) {
                continue;
            }

            $namedTypes[] = $reflectionType;
        }

        return $namedTypes;
    }
}
