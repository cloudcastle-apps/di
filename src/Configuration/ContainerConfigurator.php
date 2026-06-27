<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

use CloudCastle\DI\Contract\ContainerInterface;

/**
 * Загружает и применяет конфигурацию DI-контейнера из нескольких источников.
 *
 * Конфигурирование **необязательно** — контейнер можно собирать вручную через {@see ContainerInterface}.
 * Формат по умолчанию — PHP (`return [...]`).
 *
 * При конфликте параметров побеждает последний источник, если у параметра нет явного `priority`.
 */
final readonly class ContainerConfigurator
{
    public function __construct(
        private ConfigurationLoaderRegistry $loaderRegistry = new ConfigurationLoaderRegistry(),
        private ConfigurationMerger $merger = new ConfigurationMerger(),
        private ConfigurationApplicator $applicator = new ConfigurationApplicator(),
    ) {
    }

    /**
     * Загружает и применяет конфигурацию из списка источников.
     *
     * Элемент `$sources` может быть:
     * - путь к файлу или каталогу (строка);
     * - {@see ConfigurationSource} — один файл с приоритетом;
     * - {@see ConfigurationFilesSource} — явный список файлов;
     * - {@see ConfigurationDirectorySource} — все поддерживаемые файлы каталога.
     *
     * @param list<string|ConfigurationSource|ConfigurationDirectorySource|ConfigurationFilesSource> $sources
     */
    public function configure(ContainerInterface $container, array $sources): void
    {
        $this->apply($container, $this->loadMany($sources));
    }

    /**
     * Загружает и объединяет несколько источников без применения к контейнеру.
     *
     * @param list<string|ConfigurationSource|ConfigurationDirectorySource|ConfigurationFilesSource> $sources
     *
     * @return array<string, mixed>
     */
    public function loadMany(array $sources): array
    {
        $resolver = new ConfigurationSourceResolver($this->loaderRegistry);

        return $this->merger->merge($resolver->resolve($sources));
    }

    /**
     * Загружает один файл конфигурации.
     *
     * @return array<string, mixed>
     */
    public function load(string $path): array
    {
        return $this->loaderRegistry->load($path);
    }

    /**
     * Применяет уже объединённый массив конфигурации к контейнеру.
     *
     * @param array<string, mixed> $config
     */
    public function apply(ContainerInterface $container, array $config): void
    {
        $this->applicator->apply($container, $config);
    }

}
