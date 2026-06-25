<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Attribute\Autowire;
use CloudCastle\DI\Attribute\Inject;
use ReflectionAttribute;

/**
 * Извлекает id сервиса из PHP attributes {@see Inject} / {@see Autowire}.
 */
final readonly class AttributeServiceIdReader
{
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
            $attributeName = $attribute->getName();

            if ($attributeName === Inject::class || $attributeName === Autowire::class) {
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
        $attributeName = $attribute->getName();

        if ($attributeName !== Inject::class && $attributeName !== Autowire::class) {
            return null;
        }

        $instance = $attribute->newInstance();

        if ($instance instanceof Inject && $instance->id !== null) {
            return $instance->id;
        }

        if ($instance instanceof Autowire && $instance->service !== null) {
            return $instance->service;
        }

        return null;
    }
}
