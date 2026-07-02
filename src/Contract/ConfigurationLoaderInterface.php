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
     *
     * @param string $path Путь к файлу конфигурации
     *
     * @return bool `true`, если загрузчик поддерживает формат файла
     */
    public function supports(string $path): bool;

    /**
     * Читает файл и возвращает нормализованный массив конфигурации.
     *
     * @param string $path Путь к файлу конфигурации
     *
     * @throws \CloudCastle\DI\Exception\ContainerException При ошибке чтения или разбора
     *
     * @return array<string, mixed> Распарсенная конфигурация контейнера
     */
    public function load(string $path): array;
}
