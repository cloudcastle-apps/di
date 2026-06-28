<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

/**
 * Fluent API contextual binding (#25): `when(A)->needs(B)->give(C)`.
 *
 * Аналог Laravel `when()->needs()->give()` и PHP-DI contextual definitions.
 * Метод {@see when()} доступен на {@see ContainerInterface} с v1.11.0 (#25, часть 2).
 *
 * @see ContextualBindingRegistryInterface
 *
 * @psalm-api Публичный контракт v2.0 (#25); реализация — часть 2 декомпозиции.
 */
interface ContextualBindingConfiguratorInterface
{
    /**
     * Класс-потребитель, для которого действуют следующие needs/give.
     *
     * @param string $consumerClass FQCN класса (when)
     */
    public function when(string $consumerClass): ContextualBindingNeedsInterface;
}
