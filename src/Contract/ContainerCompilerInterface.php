<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

use CloudCastle\DI\Compiler\ContainerCompileResult;

/**
 * Компилирует замороженный {@see ContainerInterface} в PHP-класс wiring.
 *
 * Целевой сценарий v2.0 (#24): deploy/build генерирует класс без reflection при каждом `get()`.
 * Минимальный набор определений на первом этапе: `set`, `autowire`, `alias`, tags.
 *
 * @psalm-suppress PossiblyUnusedMethod Реализация появится в ContainerCompiler (#24)
 *
 * @see CompiledContainerInterface
 */
interface ContainerCompilerInterface
{
    /**
     * Генерирует PHP-файл compiled-контейнера по текущим определениям.
     *
     * Контейнер должен быть в состоянии, пригодном для компиляции (рекомендуется {@see ContainerInterface::freeze()}).
     *
     * @param ContainerInterface $container Источник определений (runtime-контейнер)
     * @param string $outputPath Абсолютный или относительный путь к `.php` файлу результата
     * @param string|null $className FQCN класса; при `null` выводится из имени файла
     *
     * @return ContainerCompileResult Метаданные сгенерированного класса и пути к файлу
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function compile(
        ContainerInterface $container,
        string $outputPath,
        ?string $className = null,
    ): ContainerCompileResult;
}
