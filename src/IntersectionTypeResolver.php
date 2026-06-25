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
 */
final readonly class IntersectionTypeResolver
{
    public function __construct(
        private ClassDependencyResolver $classResolver,
    ) {
    }

    public function resolve(ReflectionParameter|ReflectionProperty $member, ReflectionIntersectionType $type): mixed
    {
        $typeNames = $this->collectTypeNames($this->filterObjectNamedTypes($type->getTypes()));

        foreach ($typeNames as $candidateId) {
            if (!$this->classResolver->canResolve($candidateId)) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $instance = $this->classResolver->resolve($candidateId);

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

    private function allowsNull(ReflectionParameter|ReflectionProperty $member): bool
    {
        if ($member instanceof ReflectionParameter) {
            return $member->allowsNull();
        }

        return $member->getType()?->allowsNull() ?? true;
    }

    /**
     * @param list<string> $typeNames
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

    /**
     * @param list<ReflectionNamedType> $namedTypes
     *
     * @return list<string>
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
