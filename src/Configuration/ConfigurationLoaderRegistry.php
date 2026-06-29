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
final class ConfigurationLoaderRegistry
{
    /** @var list<ConfigurationLoaderInterface> */
    private readonly array $loaders;

    /**
     * @param list<ConfigurationLoaderInterface>|null $loaders Порядок определяет приоритет при совпадении
     */
    public function __construct(?array $loaders = null)
    {
        $this->loaders = $loaders ?? self::createDefaultLoaders();
    }

    /**
     * Создаёт реестр с набором загрузчиков по умолчанию (PHP, JSON, YAML, XML).
     *
     * @return list<ConfigurationLoaderInterface> Загрузчики в порядке приоритета при совпадении формата
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
     *
     * @param string $path Путь к файлу конфигурации
     *
     * @return bool `true`, если хотя бы один загрузчик вернёт `true` из {@see ConfigurationLoaderInterface::supports()}
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
     * Загружает конфигурацию из файла первым подходящим загрузчиком.
     *
     * @param string $path Путь к файлу конфигурации
     *
     * @throws ContainerException Если формат не поддерживается ни одним загрузчиком
     *
     * @return array<string, mixed> Распарсенная конфигурация
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
