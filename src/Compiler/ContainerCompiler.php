<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerCompilerInterface;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerCompileException;

/**
 * Компилирует замороженный {@see Container} в PHP-класс wiring (#24).
 */
final class ContainerCompiler implements ContainerCompilerInterface
{
    public function __construct(
        private readonly ContainerCompileSnapshotBuilder $snapshotBuilder = new ContainerCompileSnapshotBuilder(),
        private readonly CompiledContainerPhpGenerator $generator = new CompiledContainerPhpGenerator(),
    ) {
    }

    public function compile(
        ContainerInterface $container,
        string $outputPath,
        ?string $className = null,
    ): ContainerCompileResult {
        if (!$container instanceof Container) {
            throw new ContainerCompileException(
                'Компиляция поддерживается только для CloudCastle\\DI\\Container.',
            );
        }

        $resolvedClassName = $className ?? $this->classNameFromPath($outputPath);
        $snapshot = $this->snapshotBuilder->build($container);
        $source = $this->generator->generate($resolvedClassName, $snapshot);

        $this->writeFile($outputPath, $source);

        return new ContainerCompileResult($resolvedClassName, $outputPath);
    }

    private function classNameFromPath(string $outputPath): string
    {
        $basename = basename($outputPath);

        if (!str_ends_with($basename, '.php')) {
            throw new ContainerCompileException('Путь compiled-контейнера должен оканчиваться на .php.');
        }

        $shortName = substr($basename, 0, -4);

        if ($shortName === '') {
            throw new ContainerCompileException('Некорректное имя файла compiled-контейнера.');
        }

        return 'CloudCastle\\DI\\Compiled\\' . $shortName;
    }

    private function writeFile(string $outputPath, string $source): void
    {
        $directory = \dirname($outputPath);

        if ($directory !== '' && $directory !== '.' && !is_dir($directory)) {
            if (is_file($directory)) {
                throw new ContainerCompileException(\sprintf(
                    'Не удалось создать каталог "%s" для compiled-контейнера.',
                    $directory,
                ));
            }

            mkdir($directory, 0o775, true);
        }

        if (is_dir($outputPath)) {
            throw new ContainerCompileException(\sprintf(
                'Не удалось записать compiled-контейнер в "%s".',
                $outputPath,
            ));
        }

        file_put_contents($outputPath, $source);
    }
}
