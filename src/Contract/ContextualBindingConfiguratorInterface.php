<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

/**
 * Fluent API contextual binding (#25): `when(A)->needs(B)->give(C)`.
 *
 * Аналог Laravel `when()->needs()->give()` и PHP-DI contextual definitions.
 * Метод {@see when()} появится на {@see ContainerInterface} в части 2 декомпозиции.
 *
 * @see ContextualBindingRegistryInterface
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
