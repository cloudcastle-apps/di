<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionClass;

/**
 * Проверяет и получает class-зависимости из контейнера.
 *
 * Учитывает contextual binding, self-injection контейнера и глобальный autowiring.
 */
final class ClassDependencyResolver
{
    /**
     * @param ContainerInterface $container Контейнер для проверки и получения зависимостей
     */
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * Проверяет, можно ли разрешить зависимость по имени класса или интерфейса.
     *
     * @param string $typeName FQCN или id сервиса
     * @param string|null $consumerClass Класс-потребитель для contextual binding
     *
     * @return bool `true`, если зависимость доступна через контейнер или autowiring
     */
    public function canResolve(string $typeName, ?string $consumerClass = null): bool
    {
        if ($consumerClass !== null) {
            $contextualGive = $this->container->contextualGive($consumerClass, $typeName);

            if ($contextualGive !== null) {
                return $this->container->has($contextualGive);
            }
        }

        if ($typeName === ContainerInterface::class || $typeName === PsrContainerInterface::class) {
            return true;
        }

        if ($this->container->hasDefinition($typeName)) {
            return true;
        }

        if (!$this->container->isAutowiringEnabled() || !class_exists($typeName)) {
            return false;
        }

        return (new ReflectionClass($typeName))->isInstantiable();
    }

    /**
     * Получает экземпляр зависимости по имени класса или интерфейса.
     *
     * @param string $typeName FQCN или id сервиса
     * @param string|null $consumerClass Класс-потребитель для contextual binding
     *
     * @return mixed Экземпляр сервиса или контейнер при self-injection
     */
    public function resolve(string $typeName, ?string $consumerClass = null): mixed
    {
        if ($consumerClass !== null) {
            $contextualGive = $this->container->contextualGive($consumerClass, $typeName);

            if ($contextualGive !== null) {
                return $this->container->get($contextualGive);
            }
        }

        if ($typeName === ContainerInterface::class || $typeName === PsrContainerInterface::class) {
            return $this->container;
        }

        return $this->container->get($typeName);
    }
}
