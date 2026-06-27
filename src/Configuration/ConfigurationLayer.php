<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

/**
 * Один загруженный слой конфигурации перед слиянием.
 */
final class ConfigurationLayer
{
    /**
     * @param array<string, mixed> $config Распарсенная конфигурация
     * @param int $order Порядковый индекс источника (0 — первый)
     * @param int|null $filePriority Явный приоритет файла из {@see ConfigurationSource},
     *                               {@see ConfigurationDirectorySource} или {@see ConfigurationFilesSource}
     */
    public function __construct(
        public readonly array $config,
        public readonly int $order,
        public readonly ?int $filePriority,
    ) {
    }
}
