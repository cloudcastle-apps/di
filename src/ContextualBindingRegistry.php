<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContextualBindingRegistryInterface;

/**
 * In-memory хранилище contextual-привязок when/needs/give (#25).
 */
final class ContextualBindingRegistry implements ContextualBindingRegistryInterface
{
    /** @var array<string, list<ContextualBinding>> */
    private array $bindings = [];

    /**
     * {@inheritDoc}
     */
    public function register(ContextualBinding $binding): void
    {
        $this->bindings[$binding->consumerClass][] = $binding;
    }

    /**
     * {@inheritDoc}
     */
    public function bindingsFor(string $consumerClass): array
    {
        return $this->bindings[$consumerClass] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(string $consumerClass, string $need): ?string
    {
        $give = null;

        foreach ($this->bindingsFor($consumerClass) as $binding) {
            if ($binding->need === $need) {
                $give = $binding->give;
            }
        }

        return $give;
    }

    /**
     * Экспортирует contextual-привязки в карту consumer → need → give для компиляции.
     *
     * При нескольких правилах для одной пары (consumer, need) побеждает последнее зарегистрированное.
     *
     * @return array<string, array<string, string>> FQCN потребителя → карта need → give
     */
    public function exportContextualMap(): array
    {
        /** @var array<string, array<string, string>> $result */
        $result = [];

        foreach ($this->bindings as $consumerClass => $bindings) {
            foreach ($bindings as $binding) {
                $result[$consumerClass][$binding->need] = $binding->give;
            }
        }

        return $result;
    }
}
