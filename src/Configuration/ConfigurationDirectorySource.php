<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

/**
 * Источник конфигурации: все поддерживаемые файлы в каталоге.
 *
 * Файлы загружаются в лексикографическом порядке пути. Неподдерживаемые расширения пропускаются.
 */
final readonly class ConfigurationDirectorySource
{
    /**
     * @param string $directory Абсолютный или относительный путь к каталогу
     * @param int|null $priority Приоритет слоя для каждого файла каталога; `null` — порядок в списке источников
     * @param bool $recursive Обход вложенных каталогов
     */
    public function __construct(
        public string $directory,
        public ?int $priority = null,
        public bool $recursive = false,
    ) {
    }
}
