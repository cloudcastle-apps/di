<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Support;

use CloudCastle\DI\Contract\CompiledContainerInterface;
use ReflectionClass;
use RuntimeException;

/**
 * Загружает сгенерированный compiled-контейнер из временного PHP-файла в тестах.
 */
final class CompiledContainerLoader
{
    /**
     * @param string $className FQCN сгенерированного класса
     *
     * @psalm-suppress UnresolvableInclude
     */
    public static function load(string $path, string $className): CompiledContainerInterface
    {
        require $path;

        if (!class_exists($className)) {
            throw new RuntimeException(\sprintf('Compiled class "%s" not found after require.', $className));
        }

        $instance = (new ReflectionClass($className))->newInstance();

        if (!$instance instanceof CompiledContainerInterface) {
            throw new RuntimeException(\sprintf('Class "%s" must implement CompiledContainerInterface.', $className));
        }

        return $instance;
    }
}
