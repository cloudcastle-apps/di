<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;

/**
 * Создаёт экземпляры сервисов с опциональным singleton-кэшированием.
 */
final class ServiceInstanceResolver
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @param array<string, mixed> $definitions
     * @param array<string, mixed> $resolved
     * @param array<string, true> $resolving
     * @param array<string, list<callable(mixed, ContainerInterface): mixed>> $decorators
     * @param callable(string): bool $canAutowire
     * @param callable(string): object $instantiate
     *
     * @throws NotFoundException
     * @throws ContainerException
     */
    public function resolve(
        string $id,
        bool $singleton,
        array &$definitions,
        array &$resolved,
        array &$resolving,
        array $decorators,
        callable $canAutowire,
        callable $instantiate,
    ): mixed {
        if ($singleton && isset($resolved[$id])) {
            return $resolved[$id];
        }

        if (isset($definitions[$id])) {
            return $this->resolveDefinition($id, $singleton, $definitions, $resolved, $decorators);
        }

        if ($canAutowire($id)) {
            return $this->resolveAutowired($id, $singleton, $resolved, $resolving, $decorators, $instantiate);
        }

        throw new NotFoundException(\sprintf('Сервис "%s" не зарегистрирован.', $id));
    }

    /**
     * @param array<string, mixed> $definitions
     * @param array<string, mixed> $resolved
     * @param array<string, list<callable(mixed, ContainerInterface): mixed>> $decorators
     *
     * @psalm-suppress MixedAssignment
     */
    private function resolveDefinition(
        string $id,
        bool $singleton,
        array &$definitions,
        array &$resolved,
        array $decorators,
    ): mixed {
        $concrete = $definitions[$id];

        /** @var mixed $instance */
        $instance = \is_callable($concrete) ? $concrete($this->container) : $concrete;

        return $this->finalizeInstance($id, $instance, $singleton, $resolved, $decorators);
    }

    /**
     * @param array<string, mixed> $resolved
     * @param array<string, true> $resolving
     * @param array<string, list<callable(mixed, ContainerInterface): mixed>> $decorators
     * @param callable(string): object $instantiate
     */
    private function resolveAutowired(
        string $id,
        bool $singleton,
        array &$resolved,
        array &$resolving,
        array $decorators,
        callable $instantiate,
    ): mixed {
        if (($resolving[$id] ?? false) === true) {
            throw new ContainerException(\sprintf(
                'Обнаружена циклическая зависимость при autowiring сервиса "%s".',
                $id,
            ));
        }

        $resolving[$id] = true;

        try {
            $instance = $instantiate($id);

            return $this->finalizeInstance($id, $instance, $singleton, $resolved, $decorators);
        } finally {
            unset($resolving[$id]);
        }
    }

    /**
     * @param array<string, mixed> $resolved
     * @param array<string, list<callable(mixed, ContainerInterface): mixed>> $decorators
     *
     * @psalm-suppress MixedAssignment
     */
    private function finalizeInstance(
        string $id,
        mixed $instance,
        bool $singleton,
        array &$resolved,
        array $decorators,
    ): mixed {
        foreach ($decorators[$id] ?? [] as $decorator) {
            $instance = $decorator($instance, $this->container);
        }

        if ($singleton && $instance !== null) {
            $resolved[$id] = $instance;
        }

        return $instance;
    }
}
