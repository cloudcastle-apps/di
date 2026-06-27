<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use ReflectionClass;
use ReflectionProperty;
use ReflectionType;

/**
 * Внедряет зависимости в свойства экземпляра после создания.
 */
final class PropertyInjector
{
    private readonly AttributeServiceIdReader $attributeReader;

    private readonly MemberResolver $memberResolver;

    public function __construct(
        private readonly ContainerInterface $container,
        ?AttributeServiceIdReader $attributeReader = null,
    ) {
        $reader = $attributeReader ?? new AttributeServiceIdReader();
        $this->attributeReader = $reader;
        $this->memberResolver = new MemberResolver($container, $reader);
    }

    /**
     *
     * @param ReflectionClass<object> $reflection
     *
     * @throws ContainerException Если свойство не удаётся разрешить
     */
    public function inject(object $instance, ReflectionClass $reflection): void
    {
        foreach ($reflection->getProperties() as $property) {
            if (!$this->shouldInjectProperty($property, $instance)) {
                continue;
            }

            $property->setValue($instance, $this->memberResolver->resolveProperty($property));
        }
    }

    private function shouldInjectProperty(ReflectionProperty $property, object $instance): bool
    {
        if ($property->isStatic() || $property->isPromoted() || $property->isInitialized($instance)) {
            return false;
        }

        if ($this->hasInjectAttributes($property)) {
            return true;
        }

        return $this->container->isPropertyAutowiringEnabled() && $property->getType() instanceof ReflectionType;
    }

    private function hasInjectAttributes(ReflectionProperty $property): bool
    {
        return $this->attributeReader->hasAny($property->getAttributes());
    }
}
