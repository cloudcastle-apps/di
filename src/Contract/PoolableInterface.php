<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

/**
 * Контракт сервиса, возвращаемого в {@see \CloudCastle\DI\ContainerInterface::releaseToPool()}.
 *
 * Перед повторным использованием экземпляра из пула контейнер вызывает {@see reset()}
 * для сброса внутреннего состояния.
 *
 * @see \CloudCastle\DI\ContainerInterface::enablePooling()
 */
interface PoolableInterface
{
    /**
     * Сбрасывает состояние экземпляра перед возвратом в пул или повторным {@see make()}.
     */
    public function reset(): void;
}
