<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use ReflectionAttribute;
use ReflectionParameter;
use ReflectionProperty;

/**
 * Разрешает зависимости для параметров, свойств и методов при autowiring.
 */
final readonly class MemberResolver
{
    private AttributeServiceIdReader $attributeReader;

    private ParameterTypeResolver $typeResolver;

    public function __construct(
        private ContainerInterface $container,
        ?AttributeServiceIdReader $attributeReader = null,
    ) {
        $this->attributeReader = $attributeReader ?? new AttributeServiceIdReader();
        $this->typeResolver = new ParameterTypeResolver($container);
    }

    /**
     * @throws ContainerException Если обязательный параметр не разрешается
     */
    public function resolveParameter(ReflectionParameter $parameter): mixed
    {
        return $this->resolveMember(
            $parameter->getAttributes(),
            $parameter->getName(),
            fn (): mixed => $this->typeResolver->resolve($parameter),
        );
    }

    /**
     * @throws ContainerException Если обязательное свойство не разрешается
     */
    public function resolveProperty(ReflectionProperty $property): mixed
    {
        return $this->resolveMember(
            $property->getAttributes(),
            $property->getName(),
            fn (): mixed => $this->typeResolver->resolveProperty($property),
        );
    }

    /**
     * @param list<ReflectionAttribute<object>> $attributes
     * @param callable(): mixed $resolveByType
     *
     * @throws ContainerException
     */
    private function resolveMember(array $attributes, string $name, callable $resolveByType): mixed
    {
        $attributeServiceId = $this->attributeReader->read($attributes);

        if ($attributeServiceId !== null) {
            return $this->container->get($attributeServiceId);
        }

        if ($this->container->isParameterNameAutowiringEnabled() && $this->container->has($name)) {
            return $this->container->get($name);
        }

        return $resolveByType();
    }
}
