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
     * @param array<string, mixed> $config
     *
     * @return array<mixed>
     */
    private function sectionList(array $config, string $key): array
    {
        $section = $config[$key] ?? [];

        return \is_array($section) ? $section : [];
    }

    /**
     * @param array<string, mixed> $config
     */
    public function apply(ContainerInterface $container, array $config): void
    {
        $this->applyRegisterAttributes($container, $config);
        $this->applyAutowiring($container, $config);
        $this->applyScan($container, $config);
        $this->applyServices($container, $config);
        $this->applyAutowire($container, $config);
        $this->applyBind($container, $config);
        $this->applyAliases($container, $config);
        $this->applyTags($container, $config);
    }

    /**
     * @param array<string, mixed> $config
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
     * @param array<string, mixed> $config
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
     * @param array<string, mixed> $config
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
     * @param array<string, mixed> $config
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
     * @param array{class: string, lazy?: bool} $definition
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
     * @param array<string, mixed> $config
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
     * @param array<string, mixed> $config
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
     * @param array<string, mixed> $config
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
     * @param array<string, mixed> $config
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
