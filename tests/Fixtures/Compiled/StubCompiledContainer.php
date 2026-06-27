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
    public function __construct()
    {
        parent::__construct(
            compiledClassName: self::class,
            aliases: ['alias.id' => 'value'],
            tags: ['group' => ['value']],
            definitionIds: ['value'],
        );
    }

    protected function create(string $id): mixed
    {
        return match ($id) {
            'value' => 'compiled-value',
            default => throw new NotFoundException(\sprintf('Сервис "%s" не зарегистрирован.', $id)),
        };
    }
}
