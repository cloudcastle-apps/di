<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

/**
 * Завершение цепочки contextual binding: {@see give()}.
 *
 * @see ContextualBindingConfiguratorInterface
 * @see ContextualBindingNeedsInterface
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
