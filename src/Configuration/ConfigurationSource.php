<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

/**
 * Источник конфигурации: путь к одному файлу и необязательный приоритет слоя.
 *
 * Для каталога или списка файлов см. {@see ConfigurationDirectorySource} и {@see ConfigurationFilesSource}.
 * Строковый путь к каталогу в {@see ContainerConfigurator::configure()} также поддерживается.
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
