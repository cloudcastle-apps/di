<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionClass;

/**
 * Проверяет и получает class-зависимости из контейнера.
 */
final class ClassDependencyResolver
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

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
