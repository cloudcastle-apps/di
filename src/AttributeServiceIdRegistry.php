<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use Attribute;
use CloudCastle\DI\Attribute\Autowire;
use CloudCastle\DI\Attribute\Inject;
use CloudCastle\DI\Contract\ServiceIdAttribute;
use CloudCastle\DI\Exception\ContainerException;
use ReflectionClass;

/**
 * Реестр PHP attributes, распознаваемых при autowiring.
 *
 * Встроенные {@see Inject} и {@see Autowire} всегда известны; пользовательские — через {@see register()}.
 */
final class AttributeServiceIdRegistry
{
    /** @var array<string, true> */
    private const BUILTIN_ATTRIBUTE_CLASSES = [
        Inject::class => true,
        Autowire::class => true,
    ];

    /**
     * Пользовательские attribute-классы, зарегистрированные через {@see register()}.
     *
     * @var list<string>
     */
    private array $customClasses = [];

    /**
     * Регистрирует пользовательский attribute для autowiring.
     *
     * @param string $attributeClass Полное имя класса с `#[\Attribute]` и {@see ServiceIdAttribute}
     *
     * @throws ContainerException Если класс не найден, не attribute или не реализует контракт
     */
    public function register(string $attributeClass): void
    {
        if (!class_exists($attributeClass)) {
            throw new ContainerException(\sprintf('Класс attribute "%s" не найден.', $attributeClass));
        }

        $reflection = new ReflectionClass($attributeClass);

        if (!$reflection->implementsInterface(ServiceIdAttribute::class)) {
            throw new ContainerException(\sprintf(
                'Attribute "%s" должен реализовывать %s.',
                $attributeClass,
                ServiceIdAttribute::class,
            ));
        }

        if ($reflection->getAttributes(Attribute::class) === []) {
            throw new ContainerException(\sprintf(
                'Класс "%s" должен быть помечен PHP attribute #[\\Attribute].',
                $attributeClass,
            ));
        }

        if (!\in_array($attributeClass, $this->customClasses, true)) {
            $this->customClasses[] = $attributeClass;
        }
    }

    /**
     * Проверяет, распознаётся ли attribute при autowiring.
     *
     * @param string $attributeClass Полное имя класса attribute
     */
    public function isKnown(string $attributeClass): bool
    {
        return isset(self::BUILTIN_ATTRIBUTE_CLASSES[$attributeClass])
            || \in_array($attributeClass, $this->customClasses, true);
    }
}
