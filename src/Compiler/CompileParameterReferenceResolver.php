<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerCompileException;
use CloudCastle\DI\ParameterTypeResolver;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;

/**
 * Преобразует параметр конструктора в PHP-выражение для compiled-контейнера.
 */
final class CompileParameterReferenceResolver
{
    public function __construct(
        private readonly AttributeServiceIdReader $attributeReader = new AttributeServiceIdReader(),
    ) {
    }

    public function resolveExpression(Container $container, ReflectionParameter $parameter): string
    {
        $attributeServiceId = $this->attributeReader->read($parameter->getAttributes());

        if ($attributeServiceId !== null) {
            return $this->serviceGetExpression($attributeServiceId);
        }

        if ($container->isParameterNameAutowiringEnabled() && $container->has($parameter->getName())) {
            return $this->serviceGetExpression($parameter->getName());
        }

        $type = $parameter->getType();

        if ($type === null) {
            return $this->defaultValueExpression($parameter);
        }

        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnionExpression($container, $parameter, $type);
        }

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return $this->defaultValueExpression($parameter);
        }

        return $this->resolveNamedTypeExpression($container, $parameter, $type);
    }

    private function resolveNamedTypeExpression(
        Container $container,
        ReflectionParameter $parameter,
        ReflectionNamedType $type,
    ): string {
        $typeName = $type->getName();

        if ($typeName === ContainerInterface::class || $typeName === PsrContainerInterface::class) {
            return '$this';
        }

        $consumerClass = $parameter->getDeclaringClass()?->getName();

        if ($consumerClass !== null) {
            $contextualGive = $container->contextualGive($consumerClass, $typeName);

            if ($contextualGive !== null) {
                if (!$container->has($contextualGive)) {
                    throw new ContainerCompileException(\sprintf(
                        'Contextual give "%s" для %s::%s не зарегистрирован в контейнере.',
                        $contextualGive,
                        $consumerClass,
                        $parameter->getName(),
                    ));
                }

                return $this->serviceGetExpression($contextualGive);
            }
        }

        if (!$container->has($typeName)) {
            if ($parameter->isDefaultValueAvailable()) {
                return var_export($parameter->getDefaultValue(), true);
            }

            throw new ContainerCompileException(\sprintf(
                'Не удалось разрешить параметр $%s типа %s для компиляции.',
                $parameter->getName(),
                $typeName,
            ));
        }

        return $this->serviceGetExpression($typeName);
    }

    private function resolveUnionExpression(
        Container $container,
        ReflectionParameter $parameter,
        ReflectionUnionType $type,
    ): string {
        $resolver = new ParameterTypeResolver($container);

        try {
            $resolver->resolve($parameter);
        } catch (Throwable $throwable) {
            throw new ContainerCompileException(\sprintf(
                'Не удалось разрешить union-параметр $%s: %s',
                $parameter->getName(),
                $throwable->getMessage(),
            ), 0, $throwable);
        }

        /** @psalm-suppress DocblockTypeContradiction */
        foreach ($type->getTypes() as $unionMember) {
            if (!$unionMember instanceof ReflectionNamedType || $unionMember->isBuiltin()) {
                continue;
            }

            if ($container->has($unionMember->getName())) {
                return $this->serviceGetExpression($unionMember->getName());
            }
        }

        return $this->defaultValueExpression($parameter);
    }

    private function defaultValueExpression(ReflectionParameter $parameter): string
    {
        if ($parameter->isDefaultValueAvailable()) {
            return var_export($parameter->getDefaultValue(), true);
        }

        throw new ContainerCompileException(\sprintf(
            'Не удалось разрешить параметр $%s для компиляции.',
            $parameter->getName(),
        ));
    }

    private function serviceGetExpression(string $serviceId): string
    {
        return '$this->get(' . var_export($serviceId, true) . ')';
    }
}
