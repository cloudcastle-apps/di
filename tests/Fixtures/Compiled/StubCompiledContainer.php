<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Fixtures\Compiled;

use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\Exception\NotFoundException;

/**
 * Минимальный compiled-контейнер для unit-тестов {@see AbstractCompiledContainer}.
 */
final class StubCompiledContainer extends AbstractCompiledContainer
{
    /** @var array<string, int> */
    private array $createCounts = [];

    public function __construct(array $contextual = [])
    {
        parent::__construct(
            compiledClassName: self::class,
            aliases: ['alias.id' => 'value', 'alias.only' => 'missing'],
            tags: ['group' => ['missing', 'value'], 'empty' => []],
            definitionIds: ['value', 'null-value'],
            contextual: $contextual,
        );
    }

    protected function create(string $id): mixed
    {
        $this->createCounts[$id] = ($this->createCounts[$id] ?? 0) + 1;

        return match ($id) {
            'value' => 'compiled-value',
            'null-value' => null,
            default => throw new NotFoundException(\sprintf('Сервис "%s" не зарегистрирован.', $id)),
        };
    }

    public function createCount(string $id): int
    {
        return $this->createCounts[$id] ?? 0;
    }
}
