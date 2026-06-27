<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

use CloudCastle\DI\Configuration\Loader\JsonConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\PhpConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\XmlConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\YamlConfigurationLoader;
use CloudCastle\DI\Contract\ConfigurationLoaderInterface;
use CloudCastle\DI\Exception\ContainerException;

/**
 * Реестр загрузчиков конфигурации по расширению файла.
 *
 * По умолчанию: PHP (первый), JSON, YAML, XML.
 */
final readonly class ConfigurationLoaderRegistry
{
    /** @var list<ConfigurationLoaderInterface> */
    private array $loaders;

    /**
     * @param list<ConfigurationLoaderInterface>|null $loaders Порядок определяет приоритет при совпадении
     */
    public function __construct(?array $loaders = null)
    {
        $this->loaders = $loaders ?? self::createDefaultLoaders();
    }

    /**
     * @return list<ConfigurationLoaderInterface>
     */
    public static function createDefaultLoaders(): array
    {
        return [
            new PhpConfigurationLoader(),
            new JsonConfigurationLoader(),
            new YamlConfigurationLoader(),
            new XmlConfigurationLoader(),
        ];
    }

    /**
     * Проверяет, поддерживается ли файл одним из зарегистрированных загрузчиков.
     */
    public function supports(string $path): bool
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * @throws ContainerException Если формат не поддерживается
     *
     * @return array<string, mixed>
     */
    public function load(string $path): array
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($path)) {
                return $loader->load($path);
            }
        }

        throw new ContainerException(\sprintf(
            'Формат конфигурации "%s" не поддерживается. Доступны: .php, .json, .yaml, .yml, .xml.',
            $path,
        ));
    }
}
