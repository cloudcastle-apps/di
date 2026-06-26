<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Exception\ContainerException;

/**
 * Хранит и разрешает цепочки alias → id сервиса.
 */
final class ServiceAliasResolver
{
    /** @var array<string, string> */
    private array $aliases = [];

    /**
     * Регистрирует alias: {@see Container::get()} по `$alias` разрешит `$targetId`.
     *
     * @param string $alias Альтернативный идентификатор
     * @param string $targetId Целевой id сервиса
     *
     * @throws ContainerException При циклической цепочке alias
     */
    public function alias(string $alias, string $targetId): void
    {
        $this->aliases[$alias] = $targetId;

        if ($this->hasCycle($alias)) {
            unset($this->aliases[$alias]);

            throw new ContainerException(\sprintf(
                'Обнаружена циклическая цепочка alias для "%s".',
                $alias,
            ));
        }
    }

    /**
     * Возвращает конечный id после прохождения цепочки alias.
     *
     * @param string $id Исходный идентификатор
     *
     * @throws ContainerException При циклической цепочке alias
     */
    public function resolve(string $id): string
    {
        $visited = [];

        while (isset($this->aliases[$id])) {
            if (isset($visited[$id])) {
                throw new ContainerException(\sprintf(
                    'Обнаружена циклическая цепочка alias для "%s".',
                    $id,
                ));
            }

            $visited[$id] = true;
            $id = $this->aliases[$id];
        }

        return $id;
    }

    public function isAlias(string $id): bool
    {
        return isset($this->aliases[$id]);
    }

    private function hasCycle(string $startAlias): bool
    {
        $visited = [];
        $id = $startAlias;

        while (isset($this->aliases[$id])) {
            if (isset($visited[$id])) {
                return true;
            }

            $visited[$id] = true;
            $id = $this->aliases[$id];
        }

        return false;
    }
}
