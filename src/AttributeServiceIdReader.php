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
final readonly class AttributeServiceIdReader
{
    public function __construct(
        private AttributeServiceIdRegistry $registry = new AttributeServiceIdRegistry(),
    ) {
    }

    /**
     * @param list<ReflectionAttribute<object>> $attributes
     *
     * @return string|null Явный id сервиса или `null`
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
     * @param list<ReflectionAttribute<object>> $attributes
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
     * @param ReflectionAttribute<object> $attribute
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
