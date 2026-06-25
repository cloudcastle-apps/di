<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\NotFoundException;
use Override;

/**
 * Базовая реализация DI-контейнера с поддержкой singleton-фабрик.
 *
 * Каждый идентификатор может быть связан с готовым экземпляром или фабрикой.
 * Фабрика вызывается не более одного раза; результат кэшируется до следующего {@see set()}.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, mixed> Зарегистрированные определения сервисов */
    private array $definitions = [];

    /** @var array<string, mixed> Уже созданные singleton-экземпляры */
    private array $resolved = [];

    /**
     * Возвращает сервис по идентификатору.
     *
     * @param string $id Идентификатор сервиса
     *
     * @throws NotFoundException Если сервис не зарегистрирован
     *
     * @return mixed Экземпляр сервиса
     */
    #[Override]
    public function get(string $id): mixed
    {
        if (isset($this->resolved[$id])) {
            return $this->resolved[$id];
        }

        if (!isset($this->definitions[$id])) {
            throw new NotFoundException(\sprintf('Сервис "%s" не зарегистрирован.', $id));
        }

        /** @var mixed $concrete */
        $concrete = $this->definitions[$id];
        /** @var mixed $instance */
        $instance = \is_callable($concrete) ? $concrete($this) : $concrete;
        $this->resolved[$id] = $instance;

        return $instance;
    }

    /**
     * Проверяет, доступен ли сервис для получения через {@see get()}.
     *
     * @param string $id Идентификатор сервиса
     */
    #[Override]
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || isset($this->resolved[$id]);
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function set(string $id, mixed $concrete): void
    {
        unset($this->resolved[$id]);
        $this->definitions[$id] = $concrete;
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id]);
    }
}
