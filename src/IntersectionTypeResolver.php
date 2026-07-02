<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

/**
 * Разрешает intersection-типы параметров конструктора и свойств.
 *
 * Перебирает кандидатов из контейнера и проверяет, что экземпляр удовлетворяет всем типам пересечения.
 */
final class IntersectionTypeResolver
{
    /**
     * @param ClassDependencyResolver $classResolver Разрешитель class-зависимостей
     */
    public function __construct(
        private readonly ClassDependencyResolver $classResolver,
    ) {
    }

    /**
     * Разрешает intersection-тип параметра или свойства.
     *
     * @param ReflectionParameter|ReflectionProperty $member Reflection параметра или свойства
     * @param ReflectionIntersectionType $type Intersection reflection-тип
     * @param string|null $consumerClass Класс-потребитель для contextual binding
     *
     * @throws ContainerException Если обязательный член не разрешается
     *
     * @return mixed Экземпляр, удовлетворяющий всем типам пересечения, или `null`
     */
    public function resolve(
        ReflectionParameter|ReflectionProperty $member,
        ReflectionIntersectionType $type,
        ?string $consumerClass = null,
    ): mixed {
        $typeNames = $this->collectTypeNames($this->filterObjectNamedTypes($type->getTypes()));

        foreach ($typeNames as $candidateId) {
            if (!$this->classResolver->canResolve($candidateId, $consumerClass)) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $instance = $this->classResolver->resolve($candidateId, $consumerClass);

            if ($this->satisfiesIntersection($instance, $typeNames)) {
                return $instance;
            }
        }

        if ($this->allowsNull($member)) {
            return null;
        }

        throw new ContainerException(\sprintf(
            'Не удалось разрешить intersection-тип для %s $%s.',
            $member instanceof ReflectionProperty ? 'свойства' : 'параметра',
            $member->getName(),
        ));
    }

    /**
     * Проверяет, допускает ли параметр или свойство значение `null`.
     *
     * @param ReflectionParameter|ReflectionProperty $member Reflection параметра или свойства
     *
     * @return bool `true`, если `null` допустим
     */
    private function allowsNull(ReflectionParameter|ReflectionProperty $member): bool
    {
        if ($member instanceof ReflectionParameter) {
            return $member->allowsNull();
        }

        return $member->getType()?->allowsNull() ?? true;
    }

    /**
     * Проверяет, что экземпляр реализует все типы intersection.
     *
     * @param mixed $instance Кандидат из контейнера
     * @param list<string> $typeNames FQCN всех типов пересечения
     *
     * @return bool `true`, если экземпляр удовлетворяет каждому типу
     */
    private function satisfiesIntersection(mixed $instance, array $typeNames): bool
    {
        foreach ($typeNames as $typeName) {
            if ($typeName === ContainerInterface::class || $typeName === PsrContainerInterface::class) {
                if ($instance instanceof ContainerInterface || $instance instanceof PsrContainerInterface) {
                    continue;
                }

                return false;
            }

            if (!\is_object($instance)) {
                return false;
            }

            if (!$instance instanceof $typeName) {
                return false;
            }
        }

        return true;
    }

    /**
     * Оставляет только object named-типы из списка reflection-типов.
     *
     * @param array<ReflectionType> $types Список reflection-типов intersection
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

    /**
     * Извлекает FQCN из списка named-типов.
     *
     * @param list<ReflectionNamedType> $namedTypes Named reflection-типы
     *
     * @return list<string> Имена классов и интерфейсов
     */
    private function collectTypeNames(array $namedTypes): array
    {
        /** @var list<string> $typeNames */
        $typeNames = [];

        foreach ($namedTypes as $namedType) {
            $typeNames[] = $namedType->getName();
        }

        return $typeNames;
    }
}
