<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration\Loader;

use CloudCastle\DI\Contract\ConfigurationLoaderInterface;
use CloudCastle\DI\Exception\ContainerException;
use Override;

/**
 * Загрузчик YAML-конфигурации через расширение {@see yaml_parse_file()}.
 */
final class YamlConfigurationLoader implements ConfigurationLoaderInterface
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function supports(string $path): bool
    {
        $lower = strtolower($path);

        return str_ends_with($lower, '.yaml') || str_ends_with($lower, '.yml');
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function load(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ContainerException(\sprintf('Файл конфигурации "%s" не найден или недоступен.', $path));
        }

        if (!\function_exists('yaml_parse_file')) {
            throw new ContainerException(
                'Для загрузки YAML-конфигурации требуется расширение ext-yaml (yaml_parse_file).',
            );
        }

        $parsed = yaml_parse_file($path);

        if ($parsed === false) {
            throw new ContainerException(\sprintf('Ошибка разбора YAML-конфигурации "%s".', $path));
        }

        if (!\is_array($parsed)) {
            throw new ContainerException(\sprintf('YAML-конфигурация "%s" должна быть mapping.', $path));
        }

        /** @var array<string, mixed> $parsed */
        return $parsed;
    }
}
