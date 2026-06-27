<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

/**
 * Источник конфигурации: явный список файлов с общим приоритетом слоя.
 */
final class ConfigurationFilesSource
{
    /**
     * @param list<string> $paths Пути к файлам конфигурации в порядке слияния
     * @param int|null $priority Приоритет слоя для каждого файла; `null` — порядок в списке источников
     */
    public function __construct(
        public readonly array $paths,
        public readonly ?int $priority = null,
    ) {
    }
}
