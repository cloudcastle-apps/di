<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

/**
 * Загружает конфигурацию контейнера из файла.
 */
interface ConfigurationLoaderInterface
{
    /**
     * Проверяет, может ли загрузчик обработать файл по расширению или содержимому.
     */
    public function supports(string $path): bool;

    /**
     * Читает файл и возвращает нормализованный массив конфигурации.
     *
     *
     * @throws \CloudCastle\DI\Exception\ContainerException При ошибке чтения или разбора
     *
     * @return array<string, mixed>
     */
    public function load(string $path): array;
}
