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
 *
 * Поддерживает named, union и intersection типы; builtin-типы — через значение по умолчанию.
 */
final class ParameterTypeResolver
{
    /**
     * Проверяет и получает class-зависимости из контейнера.
     */
    private readonly ClassDependencyResolver $classResolver;

    /**
     * Разрешает intersection-типы (A&B).
     */
    private readonly IntersectionTypeResolver $intersection;

    /**
     * @param ContainerInterface $container Контейнер для разрешения class-зависимостей
     */
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        $this->classResolver = new ClassDependencyResolver($container);
        $this->intersection = new IntersectionTypeResolver($this->classResolver);
    }

    /**
     * Разрешает значение параметра конструктора или метода по его reflection-типу.
     *
     * @param ReflectionParameter $parameter Reflection параметра
     *
     * @throws ContainerException Если обязательный параметр не разрешается
     *
     * @return mixed Значение для передачи в вызов
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
     * Разрешает значение свойства по его reflection-типу.
     *
     * @param ReflectionProperty $property Reflection свойства
     *
     * @throws ContainerException Если обязательное свойство не разрешается
     *
     * @return mixed Значение для присвоения свойству
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

    /**
     * Разрешает union-тип параметра: первый доступный object-тип из контейнера.
     *
     * @param ReflectionParameter $parameter Reflection параметра
     * @param ReflectionUnionType $type Union reflection-тип
     * @param string|null $consumerClass Класс-потребитель для contextual binding
     *
     * @throws ContainerException Если обязательный параметр не разрешается
     *
     * @return mixed Разрешённое значение или значение по умолчанию
     */
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

    /**
     * Разрешает union-тип свойства: первый доступный object-тип или `null`.
     *
     * @param ReflectionProperty $property Reflection свойства
     * @param ReflectionUnionType $type Union reflection-тип
     * @param string $consumerClass Класс-потребитель для contextual binding
     *
     * @throws ContainerException Если свойство обязательно и ни один тип не разрешается
     *
     * @return mixed Разрешённое значение или `null`
     */
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

    /**
     * Разрешает named-тип параметра (один class/interface или builtin).
     *
     * @param ReflectionParameter $parameter Reflection параметра
     * @param ReflectionNamedType $type Named reflection-тип
     * @param string|null $consumerClass Класс-потребитель для contextual binding
     *
     * @throws ContainerException Если обязательный параметр не разрешается
     *
     * @return mixed Разрешённое значение, `null` или значение по умолчанию
     */
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

    /**
     * Разрешает named-тип свойства (один class/interface).
     *
     * @param ReflectionProperty $property Reflection свойства
     * @param ReflectionNamedType $type Named reflection-тип
     * @param string $consumerClass Класс-потребитель для contextual binding
     *
     * @throws ContainerException Если свойство обязательно и тип не разрешается
     *
     * @return mixed Разрешённое значение или `null`
     */
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
     * Возвращает значение по умолчанию параметра или выбрасывает исключение.
     *
     * @param ReflectionParameter $parameter Reflection параметра
     *
     * @throws ContainerException Если значение по умолчанию недоступно
     *
     * @return mixed Значение по умолчанию
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
     * Оставляет только object named-типы, исключая builtin и вложенные union/intersection.
     *
     * @param array<ReflectionType> $types Список reflection-типов из union или intersection
     *
     * @return list<ReflectionNamedType> Named-типы классов и интерфейсов
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
