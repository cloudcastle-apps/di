<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

/**
 * Источник конфигурации: путь к файлу и необязательный приоритет слоя.
 *
 * Приоритет слоя применяется ко всем параметрам файла без собственного {@see ConfigurationMerger}.
 */
final readonly class ConfigurationSource
{
    /**
     * @param string $path Абсолютный или относительный путь к файлу конфигурации
     * @param int|null $priority Приоритет слоя; `null` — порядок в списке источников
     */
    public function __construct(
        public string $path,
        public ?int $priority = null,
    ) {
    }
}
