<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration\Loader;

use CloudCastle\DI\Contract\ConfigurationLoaderInterface;
use CloudCastle\DI\Exception\ContainerException;

/**
 * Загрузчик YAML-конфигурации через расширение {@see yaml_parse_file()}.
 */
final class YamlConfigurationLoader implements ConfigurationLoaderInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(string $path): bool
    {
        $lower = strtolower($path);

        return str_ends_with($lower, '.yaml') || str_ends_with($lower, '.yml');
    }

    /**
     * {@inheritDoc}
     */
    public function load(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ContainerException(\sprintf('Файл конфигурации "%s" не найден или недоступен.', $path));
        }

        $parsed = $this->parseYamlFile($path);

        if ($parsed === false || $parsed === null) {
            throw new ContainerException(\sprintf('Ошибка разбора YAML-конфигурации "%s".', $path));
        }

        if (!\is_array($parsed)) {
            throw new ContainerException(\sprintf('YAML-конфигурация "%s" должна быть mapping.', $path));
        }

        /** @var array<string, mixed> $parsed */
        return $parsed;
    }

    /**
     * Разбирает YAML-файл через {@see yaml_parse_file()} с преобразованием ошибок в {@see ContainerException}.
     *
     * @param string $path Путь к YAML-файлу
     *
     * @return mixed|false Результат {@see yaml_parse_file()} или `false` при ошибке парсера
     */
    private function parseYamlFile(string $path): mixed
    {
        set_error_handler(
            static function (int $severity, string $message) use ($path): bool {
                throw new ContainerException(
                    \sprintf('Ошибка разбора YAML-конфигурации "%s" (severity %d): %s', $path, $severity, $message),
                );
            },
        );

        try {
            return yaml_parse_file($path);
        } finally {
            restore_error_handler();
        }
    }
}
