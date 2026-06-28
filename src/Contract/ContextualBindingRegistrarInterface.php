<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

use CloudCastle\DI\ContextualBinding;

/**
 * Регистрация contextual-привязок с проверкой mutability контейнера (#25).
 *
 * @psalm-api Публичный контракт v1.11 (#25, часть 2).
 */
interface ContextualBindingRegistrarInterface
{
    /**
     * Регистрирует правило when/needs/give.
     *
     * @throws \CloudCastle\DI\Exception\ContainerException Если контейнер заморожен
     */
    public function registerContextualBinding(ContextualBinding $binding): void;
}
