<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

use CloudCastle\DI\Exception\ContainerException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Разворачивает смешанный список источников в отдельные слои по файлам.
 */
final class ConfigurationSourceResolver
{
    public function __construct(
        private readonly ConfigurationLoaderRegistry $loaderRegistry,
    ) {
    }

    /**
     * @param list<string|ConfigurationSource|ConfigurationDirectorySource|ConfigurationFilesSource> $sources
     *
     * @return list<ConfigurationLayer>
     */
    public function resolve(array $sources): array
    {
        /** @var list<ConfigurationLayer> $layers */
        $layers = [];
        $order = 0;

        foreach ($sources as $source) {
            foreach ($this->expand($source) as $file) {
                $layers[] = new ConfigurationLayer(
                    $this->loaderRegistry->load($file['path']),
                    $order,
                    $file['filePriority'],
                );
                ++$order;
            }
        }

        return $layers;
    }

    /**
     * @return list<array{path: string, filePriority: int|null}>
     */
    private function expand(
        string|ConfigurationSource|ConfigurationDirectorySource|ConfigurationFilesSource $source,
    ): array {
        if (\is_string($source)) {
            if (is_dir($source)) {
                return $this->expandDirectory(new ConfigurationDirectorySource($source));
            }

            $this->assertConfigurationFile($source);

            return [['path' => $source, 'filePriority' => null]];
        }

        if ($source instanceof ConfigurationSource) {
            $this->assertConfigurationFile($source->path);

            return [['path' => $source->path, 'filePriority' => $source->priority]];
        }

        if ($source instanceof ConfigurationFilesSource) {
            return $this->expandFilePaths($source->paths, $source->priority);
        }

        return $this->expandDirectory($source);
    }

    /**
     * @param list<string> $paths
     *
     * @return list<array{path: string, filePriority: int|null}>
     */
    private function expandFilePaths(array $paths, ?int $priority): array
    {
        if ($paths === []) {
            throw new ContainerException('Список файлов конфигурации не может быть пустым.');
        }

        /** @var list<array{path: string, filePriority: int|null}> $resolved */
        $resolved = [];

        foreach ($paths as $path) {
            $this->assertConfigurationFile($path);
            $resolved[] = ['path' => $path, 'filePriority' => $priority];
        }

        return $resolved;
    }

    /**
     * @return list<array{path: string, filePriority: int|null}>
     */
    private function expandDirectory(ConfigurationDirectorySource $source): array
    {
        if (!is_dir($source->directory)) {
            throw new ContainerException(\sprintf('Каталог конфигурации "%s" не найден.', $source->directory));
        }

        /** @var list<array{path: string, filePriority: int|null}> $resolved */
        $resolved = [];

        foreach ($this->collectFiles($source->directory, $source->recursive) as $path) {
            $resolved[] = ['path' => $path, 'filePriority' => $source->priority];
        }

        return $resolved;
    }

    /**
     * @return list<string>
     */
    private function collectFiles(string $directory, bool $recursive): array
    {
        /** @var list<string> $paths */
        $paths = [];

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS,
                ),
            );
        } else {
            $iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
        }

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            if ($this->loaderRegistry->supports($path)) {
                $paths[] = $path;
            }
        }

        sort($paths, SORT_STRING);

        return $paths;
    }

    private function assertConfigurationFile(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ContainerException(\sprintf('Файл конфигурации "%s" не найден или недоступен.', $path));
        }

        if (!$this->loaderRegistry->supports($path)) {
            throw new ContainerException(\sprintf(
                'Формат конфигурации "%s" не поддерживается. Доступны: .php, .json, .yaml, .yml, .xml.',
                $path,
            ));
        }
    }
}
