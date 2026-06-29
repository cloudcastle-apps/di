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
    /**
     * @param ConfigurationLoaderRegistry $loaderRegistry Реестр загрузчиков для проверки и чтения файлов
     */
    public function __construct(
        private readonly ConfigurationLoaderRegistry $loaderRegistry,
    ) {
    }

    /**
     * Разворачивает смешанный список источников в упорядоченные слои конфигурации.
     *
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
     * Преобразует один элемент списка источников в список файлов с приоритетами.
     *
     * @param string|ConfigurationSource|ConfigurationDirectorySource|ConfigurationFilesSource $source
     *                                                                                                 Путь, файл, каталог или явный список файлов
     *
     * @throws ContainerException Если файл не найден или формат не поддерживается
     *
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
     * Разворачивает явный список путей к файлам конфигурации.
     *
     * @param list<string> $paths Пути к файлам конфигурации
     * @param int|null $priority Общий приоритет слоя для всех файлов; `null` — порядок в списке
     *
     * @throws ContainerException Если список пуст или файл недоступен/неподдерживаем
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
     * Собирает поддерживаемые файлы конфигурации из каталога.
     *
     * @param ConfigurationDirectorySource $source Каталог и режим обхода
     *
     * @throws ContainerException Если каталог не найден
     *
     * @return list<array{path: string, filePriority: int|null}>
     */
    private function expandDirectory(ConfigurationDirectorySource $source): array
    {
        if (!is_dir($source->directory)) {
            throw new ContainerException(\sprintf('Каталог конфигурации "%s" не найден.', $source->directory));
        }

        /** @var list<array{path: string, filePriority: int|null}> $resolved */
        $resolved = [];

        foreach ($this->collectFiles($source->directory, $source->scan) as $path) {
            $resolved[] = ['path' => $path, 'filePriority' => $source->priority];
        }

        return $resolved;
    }

    /**
     * Обходит каталог и возвращает пути к поддерживаемым файлам конфигурации.
     *
     * @param string $directory Корневой каталог обхода
     * @param ConfigurationDirectoryScan $scan Режим: только верхний уровень или рекурсивно
     *
     * @return list<string> Отсортированные лексикографически пути к файлам
     */
    private function collectFiles(string $directory, ConfigurationDirectoryScan $scan): array
    {
        /** @var list<string> $paths */
        $paths = [];

        $iterator = $scan === ConfigurationDirectoryScan::Recursive
            ? new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS,
                ),
            )
            : new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

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

    /**
     * Проверяет существование, доступность и поддерживаемый формат файла конфигурации.
     *
     * Дублирует проверки загрузчиков: при удалении вызова {@see ConfigurationLoaderRegistry::load()}
     * поведение совпадает, поэтому метод исключён из mutation-тестов.
     *
     * @param string $path Путь к файлу конфигурации
     *
     * @throws ContainerException Если файл не найден, недоступен или формат не поддерживается
     *
     * @infection-ignore-all
     */
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
