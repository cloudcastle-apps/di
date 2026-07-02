<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Exception\ContainerException;

/**
 * Хранит и разрешает цепочки alias → id сервиса.
 *
 * @see Container::alias() Регистрация alias в контейнере
 */
final class ServiceAliasResolver
{
    /**
     * Карта alias → целевой id сервиса.
     *
     * @var array<string, string>
     */
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
     *
     * @return string Конечный id без дальнейших alias
     */
    public function resolve(string $id): string
    {
        $visited = [];

        while (isset($this->aliases[$id])) {
            if (isset($visited[$id])) {
                /** @infection-ignore-all Throw_ removal → бесконечный цикл (timeout на CI) */
                throw new ContainerException(\sprintf(
                    'Обнаружена циклическая цепочка alias для "%s".',
                    $id,
                ));
            }

            /** @infection-ignore-all TrueValue: isset($visited[$id]) не зависит от значения */
            $visited[$id] = true;
            $id = $this->aliases[$id];
        }

        return $id;
    }

    /**
     * Проверяет, зарегистрирован ли идентификатор как alias.
     *
     * @param string $id Проверяемый идентификатор
     *
     * @return bool `true`, если `$id` — alias на другой сервис
     */
    public function isAlias(string $id): bool
    {
        return isset($this->aliases[$id]);
    }

    /**
     * Возвращает карту alias → targetId (копия внутреннего состояния).
     *
     * @return array<string, string> Все зарегистрированные alias
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Проверяет цепочку alias на цикл, начиная с `$startAlias`.
     *
     * @param string $startAlias Начальный alias для обхода цепочки
     *
     * @return bool `true`, если обнаружен цикл
     */
    private function hasCycle(string $startAlias): bool
    {
        $visited = [];
        $id = $startAlias;

        while (isset($this->aliases[$id])) {
            if (isset($visited[$id])) {
                return true;
            }

            /** @infection-ignore-all TrueValue: isset($visited[$id]) не зависит от значения */
            $visited[$id] = true;
            $id = $this->aliases[$id];
        }

        return false;
    }
}
