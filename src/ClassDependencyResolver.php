<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionClass;

/**
 * Проверяет и получает class-зависимости из контейнера.
 */
final readonly class ClassDependencyResolver
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function canResolve(string $typeName): bool
    {
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

    public function resolve(string $typeName): mixed
    {
        if ($typeName === ContainerInterface::class || $typeName === PsrContainerInterface::class) {
            return $this->container;
        }

        return $this->container->get($typeName);
    }
}
