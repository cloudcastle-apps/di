<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

/**
 * Завершение цепочки contextual binding: {@see give()}.
 *
 * @see ContextualBindingConfiguratorInterface
 * @see ContextualBindingNeedsInterface
 *
 * @psalm-api Публичный контракт v2.0 (#25); реализация — часть 2 декомпозиции.
 */
interface ContextualBindingGiveInterface
{
    /**
     * Задаёт id сервиса или FQCN реализации для текущей пары when/needs.
     *
     * @param string $serviceId id сервиса в контейнере или FQCN класса
     */
    public function give(string $serviceId): void;
}
