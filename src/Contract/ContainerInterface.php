<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Контракт DI-контейнера, расширяющий PSR-11.
 *
 * Дополняет {@see PsrContainerInterface} методами регистрации сервисов
 * и проверки наличия определения без создания экземпляра.
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Регистрирует фабрику или готовый экземпляр по идентификатору.
     *
     * Повторная регистрация сбрасывает ранее созданный singleton-экземпляр.
     *
     * @param string $id Идентификатор сервиса
     * @param mixed $concrete Фабрика `callable(ContainerInterface): mixed` или готовый экземпляр
     */
    public function set(string $id, mixed $concrete): void;

    /**
     * Проверяет наличие регистрации сервиса без его создания.
     *
     * @param string $id Идентификатор сервиса
     *
     * @return bool `true`, если сервис зарегистрирован через {@see set()}
     */
    public function hasDefinition(string $id): bool;
}
