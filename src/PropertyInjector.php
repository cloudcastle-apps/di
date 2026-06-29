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
 *
 * Обрабатывает свойства с inject-attributes или при включённом property autowiring.
 */
final class PropertyInjector
{
    /**
     * Проверяет наличие inject-attributes на свойствах.
     */
    private readonly AttributeServiceIdReader $attributeReader;

    /**
     * Разрешает значения для inject-свойств.
     */
    private readonly MemberResolver $memberResolver;

    /**
     * @param ContainerInterface $container Контейнер для разрешения зависимостей
     * @param AttributeServiceIdReader|null $attributeReader Читатель id из attributes (по умолчанию новый экземпляр)
     */
    public function __construct(
        private readonly ContainerInterface $container,
        ?AttributeServiceIdReader $attributeReader = null,
    ) {
        $reader = $attributeReader ?? new AttributeServiceIdReader();
        $this->attributeReader = $reader;
        $this->memberResolver = new MemberResolver($container, $reader);
    }

    /**
     * Внедряет зависимости во все подходящие свойства экземпляра.
     *
     * @param object $instance Целевой экземпляр
     * @param ReflectionClass<object> $reflection Reflection класса экземпляра
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

    /**
     * Определяет, нужно ли внедрять значение в свойство.
     *
     * @param ReflectionProperty $property Reflection свойства
     * @param object $instance Целевой экземпляр
     *
     * @return bool `true`, если свойство подлежит injection
     */
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

    /**
     * Проверяет наличие inject-attributes на свойстве.
     *
     * @param ReflectionProperty $property Reflection свойства
     *
     * @return bool `true`, если найден известный inject-attribute
     */
    private function hasInjectAttributes(ReflectionProperty $property): bool
    {
        return $this->attributeReader->hasAny($property->getAttributes());
    }
}
