<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

/**
 * Результат {@see \CloudCastle\DI\Contract\ContainerCompilerInterface::compile()}.
 */
final readonly class ContainerCompileResult
{
    /**
     * @param string $className FQCN сгенерированного compiled-контейнера
     * @param string $outputPath Путь к записанному PHP-файлу
     */
    public function __construct(
        public string $className,
        public string $outputPath,
    ) {
    }
}
