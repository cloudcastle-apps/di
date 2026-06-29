<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

use CloudCastle\DI\ContextualBinding;

/**
 * Хранилище contextual-привязок для autowiring (#25).
 *
 * Реализация — {@see \CloudCastle\DI\ContextualBindingRegistry}; регистрация через {@see ContainerInterface::when()}.
 *
 * @psalm-api Публичный контракт v2.0 (#25); реализация — часть 2 декомпозиции.
 */
interface ContextualBindingRegistryInterface
{
    /**
     * Регистрирует правило when/needs/give.
     *
     * @param ContextualBinding $binding Правило contextual-привязки
     */
    public function register(ContextualBinding $binding): void;

    /**
     * Все правила для класса-потребителя (в порядке регистрации).
     *
     * @param string $consumerClass FQCN класса-потребителя (when)
     *
     * @return list<ContextualBinding> Список правил для autowiring в контексте класса
     */
    public function bindingsFor(string $consumerClass): array;

    /**
     * Возвращает id сервиса для пары (consumer, need) или `null`, если правило не задано.
     *
     * @param string $consumerClass FQCN класса-потребителя (when)
     * @param string $need FQCN типа или id зависимости (needs)
     *
     * @return string|null Id сервиса (give) или `null`, если привязка не найдена
     */
    public function resolve(string $consumerClass, string $need): ?string;
}
