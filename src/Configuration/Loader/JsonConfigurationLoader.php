<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration\Loader;

use CloudCastle\DI\Contract\ConfigurationLoaderInterface;
use CloudCastle\DI\Exception\ContainerException;
use JsonException;
use Override;

/**
 * Загрузчик JSON-конфигурации.
 */
final class JsonConfigurationLoader implements ConfigurationLoaderInterface
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function supports(string $path): bool
    {
        return str_ends_with(strtolower($path), '.json');
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function load(string $path): array
    {
        $contents = $this->readFile($path);

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new ContainerException(\sprintf(
                'Ошибка разбора JSON-конфигурации "%s": %s',
                $path,
                $jsonException->getMessage(),
            ), $jsonException->getCode(), previous: $jsonException);
        }

        if (!\is_array($decoded)) {
            throw new ContainerException(\sprintf('JSON-конфигурация "%s" должна быть объектом.', $path));
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @throws ContainerException
     */
    private function readFile(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ContainerException(\sprintf('Файл конфигурации "%s" не найден или недоступен.', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new ContainerException(\sprintf('Не удалось прочитать файл конфигурации "%s".', $path));
        }

        return $contents;
    }
}
