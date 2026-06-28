<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContextualBindingGiveInterface;
use CloudCastle\DI\Contract\ContextualBindingNeedsInterface;
use CloudCastle\DI\Contract\ContextualBindingRegistrarInterface;

/**
 * Вторая ступень fluent API contextual binding: {@see needs()}.
 */
final class ContextualBindingNeeds implements ContextualBindingNeedsInterface
{
    public function __construct(
        private readonly ContextualBindingRegistrarInterface $registrar,
        private readonly string $consumerClass,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function needs(string $need): ContextualBindingGiveInterface
    {
        return new ContextualBindingGive($this->registrar, $this->consumerClass, $need);
    }
}
