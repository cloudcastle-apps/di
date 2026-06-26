<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

/**
 * Один загруженный слой конфигурации перед слиянием.
 */
final readonly class ConfigurationLayer
{
    /**
     * @param array<string, mixed> $config Распарсенная конфигурация
     * @param int $order Порядковый индекс источника (0 — первый)
     * @param int|null $filePriority Явный приоритет файла из {@see ConfigurationSource}
     */
    public function __construct(
        public array $config,
        public int $order,
        public ?int $filePriority,
    ) {
    }
}
