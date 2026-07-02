<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContextualBindingGiveInterface;
use CloudCastle\DI\Contract\ContextualBindingRegistrarInterface;

/**
 * Завершение fluent API contextual binding: {@see give()}.
 */
final class ContextualBindingGive implements ContextualBindingGiveInterface
{
    /**
     * @param ContextualBindingRegistrarInterface $registrar Регистратор contextual-привязок
     * @param string $consumerClass FQCN класса-потребителя из цепочки when()
     * @param string $need FQCN зависимости или id сервиса из цепочки needs()
     */
    public function __construct(
        private readonly ContextualBindingRegistrarInterface $registrar,
        private readonly string $consumerClass,
        private readonly string $need,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function give(string $serviceId): void
    {
        $this->registrar->registerContextualBinding(new ContextualBinding(
            consumerClass: $this->consumerClass,
            need: $this->need,
            give: $serviceId,
        ));
    }
}
