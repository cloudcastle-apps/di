<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

use CloudCastle\DI\ContextualBinding;

/**
 * Хранилище contextual-привязок для autowiring (#25).
 *
 * Реализация появится в runtime-слое v2.0; контракт фиксирует API до интеграции в {@see ContainerInterface}.
 *
 * @psalm-api Публичный контракт v2.0 (#25); реализация — часть 2 декомпозиции.
 */
interface ContextualBindingRegistryInterface
{
    /**
     * Регистрирует правило when/needs/give.
     */
    public function register(ContextualBinding $binding): void;

    /**
     * Все правила для класса-потребителя (в порядке регистрации).
     *
     * @return list<ContextualBinding>
     */
    public function bindingsFor(string $consumerClass): array;

    /**
     * id сервиса для пары (consumer, need) или `null`, если правило не задано.
     */
    public function resolve(string $consumerClass, string $need): ?string;
}
