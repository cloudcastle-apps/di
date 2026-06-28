<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Одно contextual-правило: при создании {@see $consumerClass} зависимость {@see $need} → {@see $give}.
 *
 * Часть v2.0 (#25). Регистрация через {@see \CloudCastle\DI\Contract\ContextualBindingConfiguratorInterface}.
 */
final class ContextualBinding
{
    /**
     * @param string $consumerClass FQCN класса-потребителя (when)
     * @param string $need FQCN типа или id сервиса (needs)
     * @param string $give id сервиса или FQCN реализации (give)
     */
    public function __construct(
        public readonly string $consumerClass,
        public readonly string $need,
        public readonly string $give,
    ) {
    }
}
