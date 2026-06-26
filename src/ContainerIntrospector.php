<?php

declare(strict_types=1);

namespace CloudCastle\DI;

/**
 * Снимок и список id определений контейнера (без resolve сервисов).
 */
final readonly class ContainerIntrospector
{
    /**
     * @param array<string, mixed> $definitions
     * @param array<string, true> $autowired
     * @param array<string, string> $aliases
     * @param array<string, list<string>> $tags
     * @param array<string, list<callable>> $decorators
     * @param array<string, mixed> $resolved
     * @param array{
     *     enabled: bool,
     *     parameterName: bool,
     *     property: bool,
     *     method: bool
     * } $autowiringFlags
     */
    public function __construct(
        private bool $frozen,
        private array $definitions,
        private array $autowired,
        private array $aliases,
        private array $tags,
        private array $decorators,
        private array $resolved,
        private array $autowiringFlags,
    ) {
    }

    /**
     * @return list<string>
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
     * }
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
