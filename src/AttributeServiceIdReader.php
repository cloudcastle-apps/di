<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ServiceIdAttribute;
use ReflectionAttribute;

/**
 * Извлекает id сервиса из PHP attributes при autowiring.
 *
 * Встроенные и зарегистрированные attributes перечислены в {@see AttributeServiceIdRegistry}.
 */
final class AttributeServiceIdReader
{
    /**
     * @param AttributeServiceIdRegistry $registry Реестр известных inject-attributes
     */
    public function __construct(
        private readonly AttributeServiceIdRegistry $registry = new AttributeServiceIdRegistry(),
    ) {
    }

    /**
     * Возвращает явный id сервиса из первого подходящего attribute.
     *
     * @param list<ReflectionAttribute<object>> $attributes Attributes параметра, свойства или метода
     *
     * @return string|null Явный id сервиса или `null`, если attribute не найден
     */
    public function read(array $attributes): ?string
    {
        foreach ($attributes as $attribute) {
            $serviceId = $this->readOne($attribute);

            if ($serviceId !== null) {
                return $serviceId;
            }
        }

        return null;
    }

    /**
     * Проверяет, есть ли среди attributes хотя бы один известный inject-attribute.
     *
     * @param list<ReflectionAttribute<object>> $attributes Attributes параметра, свойства или метода
     *
     * @return bool `true`, если найден attribute из реестра
     */
    public function hasAny(array $attributes): bool
    {
        foreach ($attributes as $attribute) {
            if ($this->registry->isKnown($attribute->getName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Извлекает id сервиса из одного reflection-attribute.
     *
     * @param ReflectionAttribute<object> $attribute Reflection PHP attribute
     *
     * @return string|null Id сервиса или `null`, если attribute неизвестен или не реализует контракт
     */
    private function readOne(ReflectionAttribute $attribute): ?string
    {
        if (!$this->registry->isKnown($attribute->getName())) {
            return null;
        }

        $instance = $attribute->newInstance();

        if ($instance instanceof ServiceIdAttribute) {
            return $instance->serviceId();
        }

        return null;
    }
}
