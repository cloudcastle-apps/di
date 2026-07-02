<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Exception\NotFoundException;

/**
 * Создаёт экземпляры сервисов с опциональным singleton-кэшированием.
 *
 * Обрабатывает явные definitions, autowiring и цепочку декораторов.
 */
final class ServiceInstanceResolver
{
    /**
     * @param ContainerInterface $container Контейнер для фабрик и декораторов
     */
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * Разрешает экземпляр сервиса по id с учётом singleton, definition и autowiring.
     *
     * @param string $id Идентификатор сервиса
     * @param bool $singleton Кэшировать ли экземпляр после создания
     * @param array<string, mixed> $definitions Карта id → concrete (фабрика или экземпляр)
     * @param array<string, mixed> $resolved Кэш уже созданных singleton-экземпляров
     * @param array<string, true> $resolving Стек id в процессе разрешения (обнаружение циклов)
     * @param array<string, list<callable(mixed, ContainerInterface): mixed>> $decorators Декораторы по id
     * @param callable(string): bool $canAutowire Проверка возможности autowiring по id
     * @param callable(string): object $instantiate Создание экземпляра через autowiring
     *
     * @throws NotFoundException Если сервис не зарегистрирован и autowiring недоступен
     * @throws ContainerException При циклической зависимости autowiring
     *
     * @return mixed Экземпляр сервиса после декораторов
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
     * Создаёт экземпляр из явного definition (фабрика или готовое значение).
     *
     * @param string $id Идентификатор сервиса
     * @param bool $singleton Кэшировать ли экземпляр после создания
     * @param array<string, mixed> $definitions Карта id → concrete
     * @param array<string, mixed> $resolved Кэш singleton-экземпляров
     * @param array<string, list<callable(mixed, ContainerInterface): mixed>> $decorators Декораторы по id
     *
     * @return mixed Экземпляр после декораторов
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
     * Создаёт экземпляр через autowiring с защитой от циклических зависимостей.
     *
     * @param string $id Идентификатор сервиса (обычно FQCN)
     * @param bool $singleton Кэшировать ли экземпляр после создания
     * @param array<string, mixed> $resolved Кэш singleton-экземпляров
     * @param array<string, true> $resolving Стек id в процессе разрешения
     * @param array<string, list<callable(mixed, ContainerInterface): mixed>> $decorators Декораторы по id
     * @param callable(string): object $instantiate Создание экземпляра через autowiring
     *
     * @throws ContainerException При циклической зависимости autowiring
     *
     * @return mixed Экземпляр после декораторов
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
     * Применяет декораторы и сохраняет экземпляр в singleton-кэш при необходимости.
     *
     * @param string $id Идентификатор сервиса
     * @param mixed $instance Исходный экземпляр до декораторов
     * @param bool $singleton Кэшировать ли результат
     * @param array<string, mixed> $resolved Кэш singleton-экземпляров
     * @param array<string, list<callable(mixed, ContainerInterface): mixed>> $decorators Декораторы по id
     *
     * @return mixed Финальный экземпляр после декораторов
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
