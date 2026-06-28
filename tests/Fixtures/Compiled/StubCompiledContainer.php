<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Compiled;

use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\Exception\NotFoundException;
use CloudCastle\DI\Tests\Fixtures\MemoryPool\ResetCounter;

/**
 * Минимальный compiled-контейнер для unit-тестов {@see AbstractCompiledContainer}.
 */
final class StubCompiledContainer extends AbstractCompiledContainer
{
    /** @var array<string, int> */
    private array $createCounts = [];

    public function __construct(array $contextual = [], ?callable $smartCacheClock = null)
    {
        parent::__construct(
            compiledClassName: self::class,
            aliases: ['alias.id' => 'value', 'alias.only' => 'missing'],
            tags: ['group' => ['missing', 'value'], 'empty' => []],
            definitionIds: ['value', 'null-value', 'counter'],
            contextual: $contextual,
            smartCacheClock: $smartCacheClock,
        );
    }

    protected function create(string $id): mixed
    {
        $this->createCounts[$id] = ($this->createCounts[$id] ?? 0) + 1;

        return match ($id) {
            'value' => 'compiled-value',
            'null-value' => null,
            'counter' => new ResetCounter(value: 3),
            default => throw new NotFoundException(\sprintf('Сервис "%s" не зарегистрирован.', $id)),
        };
    }

    public function createCount(string $id): int
    {
        return $this->createCounts[$id] ?? 0;
    }
}
