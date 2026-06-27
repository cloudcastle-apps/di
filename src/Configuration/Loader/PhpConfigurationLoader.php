<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration\Loader;

use CloudCastle\DI\Contract\ConfigurationLoaderInterface;
use CloudCastle\DI\Exception\ContainerException;

/**
 * Загрузчик PHP-конфигурации (формат по умолчанию).
 *
 * Файл должен `return array<string, mixed>`.
 *
 * @psalm-suppress UnresolvableInclude
 */
final class PhpConfigurationLoader implements ConfigurationLoaderInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(string $path): bool
    {
        return str_ends_with(strtolower($path), '.php');
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress PossiblyUnusedReturnValue Вызывается через {@see ConfigurationLoaderInterface}
     */
    public function load(string $path): array
    {
        $this->assertReadableFile($path);

        /** @var mixed $config */
        $config = require $path;

        if (!\is_array($config)) {
            throw new ContainerException(\sprintf(
                'PHP-конфигурация "%s" должна возвращать массив.',
                $path,
            ));
        }

        /** @var array<string, mixed> $config */
        return $config;
    }

    /**
     * @throws ContainerException
     */
    private function assertReadableFile(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ContainerException(\sprintf('Файл конфигурации "%s" не найден или недоступен.', $path));
        }
    }
}
