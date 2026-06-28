<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContextualBindingConfiguratorInterface;
use CloudCastle\DI\Contract\ContextualBindingNeedsInterface;
use CloudCastle\DI\Contract\ContextualBindingRegistrarInterface;

/**
 * Fluent API {@see when()} для регистрации contextual-привязок (#25).
 */
final class ContextualBindingConfigurator implements ContextualBindingConfiguratorInterface
{
    public function __construct(
        private readonly ContextualBindingRegistrarInterface $registrar,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function when(string $consumerClass): ContextualBindingNeedsInterface
    {
        return new ContextualBindingNeeds($this->registrar, $consumerClass);
    }
}
