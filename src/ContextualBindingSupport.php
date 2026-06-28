<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContextualBindingNeedsInterface;
use CloudCastle\DI\Contract\ContextualBindingRegistrarInterface;

/**
 * Runtime contextual when/needs/give (#25, часть 2).
 */
final class ContextualBindingSupport implements ContextualBindingRegistrarInterface
{
    private readonly ContextualBindingRegistry $registry;

    private readonly ContextualBindingConfigurator $configurator;

    /** @var \Closure(): void|null */
    private readonly ?\Closure $assertMutable;

    /**
     * @param \Closure(): void|null $assertMutable Проверка mutability контейнера перед register
     */
    public function __construct(?\Closure $assertMutable = null)
    {
        $this->registry = new ContextualBindingRegistry();
        $this->configurator = new ContextualBindingConfigurator($this);
        $this->assertMutable = $assertMutable;
    }

    public function when(string $consumerClass): ContextualBindingNeedsInterface
    {
        return $this->configurator->when($consumerClass);
    }

    public function contextualGive(string $consumerClass, string $need): ?string
    {
        return $this->registry->resolve($consumerClass, $need);
    }

    /**
     * {@inheritDoc}
     */
    public function registerContextualBinding(ContextualBinding $binding): void
    {
        if ($this->assertMutable !== null) {
            ($this->assertMutable)();
        }

        $this->registry->register($binding);
    }
}
