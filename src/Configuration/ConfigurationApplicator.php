<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

use CloudCastle\DI\Contract\ContainerInterface;

/**
 * Применяет объединённую конфигурацию к контейнеру.
 *
 * @psalm-suppress MixedAssignment
 */
final class ConfigurationApplicator
{
    /**
     * Возвращает нормализованную секцию конфигурации как массив.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     * @param string $key Ключ секции (например `services`, `bind`)
     *
     * @return array<mixed> Секция как массив или пустой массив, если ключ отсутствует или не массив
     */
    private function sectionList(array $config, string $key): array
    {
        $section = $config[$key] ?? [];

        return \is_array($section) ? $section : [];
    }

    /**
     * Применяет объединённую конфигурацию к контейнеру: autowiring, scan, services, bind и т.д.
     *
     * @param ContainerInterface $container Целевой контейнер
     * @param array<string, mixed> $config Объединённая конфигурация после {@see ConfigurationMerger}
     */
    public function apply(ContainerInterface $container, array $config): void
    {
        $this->applyRegisterAttributes($container, $config);
        $this->applyAutowiring($container, $config);
        $this->applyScan($container, $config);
        $this->applyServices($container, $config);
        $this->applyAutowire($container, $config);
        $this->applyBind($container, $config);
        $this->applyContextual($container, $config);
        $this->applyAliases($container, $config);
        $this->applyTags($container, $config);
    }

    /**
     * Регистрирует пользовательские PHP attributes из секции `register_attributes`.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     */
    private function applyRegisterAttributes(ContainerInterface $container, array $config): void
    {
        foreach ($this->sectionList($config, 'register_attributes') as $attributeClass) {
            if (!\is_string($attributeClass)) {
                continue;
            }

            $container->registerAttribute($attributeClass);
        }
    }

    /**
     * Включает режимы autowiring из секции `autowiring` конфигурации.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     */
    private function applyAutowiring(ContainerInterface $container, array $config): void
    {
        $flags = $config['autowiring'] ?? null;

        if (!\is_array($flags)) {
            return;
        }

        if (($flags['enabled'] ?? false) === true) {
            $container->enableAutowiring();
        }

        if (($flags['parameter_name'] ?? false) === true) {
            $container->enableParameterNameAutowiring();
        }

        if (($flags['property'] ?? false) === true) {
            $container->enablePropertyAutowiring();
        }

        if (($flags['method'] ?? false) === true) {
            $container->enableMethodAutowiring();
        }
    }

    /**
     * Выполняет {@see ContainerInterface::scan()} для записей секции `scan`.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     */
    private function applyScan(ContainerInterface $container, array $config): void
    {
        foreach ($this->sectionList($config, 'scan') as $scan) {
            if (!\is_array($scan)) {
                continue;
            }

            $directory = $scan['directory'] ?? null;

            if (!\is_string($directory)) {
                continue;
            }

            $namespace = $scan['namespace'] ?? null;
            $container->scan($directory, \is_string($namespace) ? $namespace : null);
        }
    }

    /**
     * Регистрирует сервисы из секции `services` через {@see set()}, {@see autowire()} или {@see bind()}.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     */
    private function applyServices(ContainerInterface $container, array $config): void
    {
        foreach ($this->sectionList($config, 'services') as $id => $value) {
            if (!\is_string($id)) {
                continue;
            }

            if (\is_array($value) && isset($value['class']) && \is_string($value['class'])) {
                /** @var array{class: string, lazy?: bool} $definition */
                $definition = $value;
                $this->registerClassService($container, $id, $definition);

                continue;
            }

            $container->set($id, $value);
        }
    }

    /**
     * Регистрирует сервис с ключом `class` (опционально `lazy`) из YAML/JSON/PHP-конфигурации.
     *
     * @param string $id Идентификатор сервиса в конфигурации
     * @param array{class: string, lazy?: bool} $definition Описание класса и флага lazy
     */
    private function registerClassService(ContainerInterface $container, string $id, array $definition): void
    {
        $class = $definition['class'];

        if (($definition['lazy'] ?? false) === true) {
            $container->set($id, $container->lazy($class));

            return;
        }

        if ($id === $class) {
            $container->autowire($class);

            return;
        }

        $container->bind($id, $class);
    }

    /**
     * Регистрирует классы из секции `autowire` через {@see ContainerInterface::autowire()}.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     */
    private function applyAutowire(ContainerInterface $container, array $config): void
    {
        foreach ($this->sectionList($config, 'autowire') as $className) {
            if (\is_string($className)) {
                $container->autowire($className);
            }
        }
    }

    /**
     * Применяет привязки абстракций из секции `bind`.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     */
    private function applyBind(ContainerInterface $container, array $config): void
    {
        foreach ($this->sectionList($config, 'bind') as $abstract => $concrete) {
            if (\is_string($abstract) && \is_string($concrete)) {
                $container->bind($abstract, $concrete);
            }
        }
    }

    /**
     * Регистрирует contextual-привязки when/needs/give из секции `contextual`.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     */
    private function applyContextual(ContainerInterface $container, array $config): void
    {
        foreach ($this->sectionList($config, 'contextual') as $consumerClass => $needsMap) {
            if (!\is_string($consumerClass) || !\is_array($needsMap)) {
                continue;
            }

            foreach ($needsMap as $need => $give) {
                if (!\is_string($need) || !\is_string($give)) {
                    continue;
                }

                $container->when($consumerClass)->needs($need)->give($give);
            }
        }
    }

    /**
     * Регистрирует alias из секции `aliases`.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     */
    private function applyAliases(ContainerInterface $container, array $config): void
    {
        foreach ($this->sectionList($config, 'aliases') as $alias => $target) {
            if (\is_string($alias) && \is_string($target)) {
                $container->alias($alias, $target);
            }
        }
    }

    /**
     * Привязывает id сервисов к тегам из секции `tags`.
     *
     * @param array<string, mixed> $config Объединённая конфигурация
     */
    private function applyTags(ContainerInterface $container, array $config): void
    {
        foreach ($this->sectionList($config, 'tags') as $tag => $ids) {
            if (!\is_string($tag) || !\is_array($ids)) {
                continue;
            }

            foreach ($ids as $id) {
                if (\is_string($id)) {
                    $container->tag($id, $tag);
                }
            }
        }
    }
}
