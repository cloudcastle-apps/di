<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Снимок и список id определений контейнера (без resolve сервисов).
 */
final class ContainerIntrospector
{
    /**
     * Создаёт снимок внутреннего состояния контейнера для отладки без resolve сервисов.
     *
     * @param bool $frozen Заморожен ли контейнер ({@see ContainerInterface::isFrozen()})
     * @param array<string, mixed> $definitions Явные определения id → concrete
     * @param array<string, true> $autowired Id классов, зарегистрированных через autowire
     * @param array<string, string> $aliases Карта alias → target id
     * @param array<string, list<string>> $tags Карта тег → список id сервисов
     * @param array<string, list<callable>> $decorators Декораторы по id сервиса
     * @param array<string, mixed> $resolved Singleton-кэш resolved id → экземпляр
     * @param array{
     *     enabled: bool,
     *     parameterName: bool,
     *     property: bool,
     *     method: bool
     * } $autowiringFlags Флаги режимов autowiring
     */
    public function __construct(
        private readonly bool $frozen,
        private readonly array $definitions,
        private readonly array $autowired,
        private readonly array $aliases,
        private readonly array $tags,
        private readonly array $decorators,
        private readonly array $resolved,
        private readonly array $autowiringFlags,
    ) {
    }

    /**
     * Возвращает все зарегистрированные id без создания сервисов.
     *
     * @return list<string> Отсортированный список definitions, autowire и alias без дубликатов
     */
    public function definitionIds(): array
    {
        $ids = [
            ...array_keys($this->definitions),
            ...array_keys($this->autowired),
            ...array_keys($this->aliases),
        ];
        $ids = array_values(array_unique($ids, SORT_STRING));
        sort($ids, SORT_STRING);

        return $ids;
    }

    /**
     * Возвращает структурированный снимок состояния контейнера для отладки.
     *
     * @return array{
     *     frozen: bool,
     *     definitions: list<string>,
     *     autowired: list<string>,
     *     aliases: array<string, string>,
     *     tags: array<string, list<string>>,
     *     decorators: list<string>,
     *     resolved: list<string>,
     *     autowiring: array{
     *         enabled: bool,
     *         parameterName: bool,
     *         property: bool,
     *         method: bool
     *     }
     * } Карта полей для {@see ContainerInterface::dump()}
     */
    public function dump(): array
    {
        $definitions = array_keys($this->definitions);
        sort($definitions, SORT_STRING);

        $autowired = array_keys($this->autowired);
        sort($autowired, SORT_STRING);

        $decorators = array_keys($this->decorators);
        sort($decorators, SORT_STRING);

        $resolved = array_keys($this->resolved);
        sort($resolved, SORT_STRING);

        return [
            'frozen' => $this->frozen,
            'definitions' => $definitions,
            'autowired' => $autowired,
            'aliases' => $this->aliases,
            'tags' => $this->tags,
            'decorators' => $decorators,
            'resolved' => $resolved,
            'autowiring' => $this->autowiringFlags,
        ];
    }
}
